<?php

namespace App\Console\Commands;

use App\Jobs\StuckJob;
use Illuminate\Console\Command;

class SimulateOrphan extends Command
{
    private const STUCK_JOB_TIMEOUT = 30;

    protected $signature = 'demo:simulate-orphan
                            {--count=1 : Number of orphan scenarios to create}';

    protected $description = 'Dispatch StuckJob(s), wait for Horizon to reserve them, then kill the entire Horizon process tree (master + supervisor + workers) to simulate a server crash';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        $this->info("Dispatching {$count} StuckJob(s) to the reports queue...");
        for ($i = 1; $i <= $count; $i++) {
            // A short timeout, so the orphan becomes releasable within a minute:
            // the package only releases jobs that have run past their timeout.
            StuckJob::dispatch($i, self::STUCK_JOB_TIMEOUT);
        }

        $this->info('Waiting 6 seconds for Horizon to reserve them...');
        sleep(6);

        $masterPids = $this->findHorizonMasterPids();
        $supervisorPids = $this->findHorizonSupervisorPids();

        if (empty($masterPids) && empty($supervisorPids)) {
            $this->error('No Horizon processes found. Is Horizon running?');
            return self::FAILURE;
        }

        // Order matters: kill the master FIRST. With the master alive, it
        // detects supervisor death and respawns one with the same name —
        // turning the demo into a flaky race. Killing the master first means
        // the supervisor and its workers stay dead.
        if (! empty($masterPids)) {
            $this->info('Found master PIDs: ' . implode(', ', $masterPids));
            $this->warn('Killing Horizon master(s) to prevent supervisor respawn...');
            foreach ($masterPids as $pid) {
                posix_kill((int) $pid, SIGKILL);
            }
        }

        if (! empty($supervisorPids)) {
            $this->info('Found supervisor PIDs: ' . implode(', ', $supervisorPids));
            $this->warn('Killing supervisor(s)...');
            foreach ($supervisorPids as $pid) {
                posix_kill((int) $pid, SIGKILL);
            }
        }

        // Workers spawned by Horizon use Symfony Process, which puts each worker
        // in its own process group — so killing the supervisor's group leaves
        // them running. Kill them explicitly.
        $workerPids = $this->findHorizonWorkerPids();
        if (! empty($workerPids)) {
            $this->info('Found worker PIDs: ' . implode(', ', $workerPids));
            $this->warn('Killing workers...');
            foreach ($workerPids as $pid) {
                posix_kill((int) $pid, SIGKILL);
            }
        }

        // Remove the now-dead supervisor entries from Horizon's live ZSET so
        // the orphan is immediately visible (rather than waiting the 90-second
        // heartbeat window for the entries to expire on their own).
        $this->removeDeadSupervisorEntries($supervisorPids);

        $this->info('Horizon process tree killed. The reserved StuckJob(s) are now orphaned.');
        $this->newLine();
        $this->info('About 15s from now (once Horizon\'s record of the killed master expires), list the orphaned job(s):');
        $this->comment('  php artisan horizon:running-jobs --orphaned');
        $this->newLine();
        $this->info(sprintf(
            'Release them once they have run past their %ds timeout (about %ds after dispatch):',
            self::STUCK_JOB_TIMEOUT,
            self::STUCK_JOB_TIMEOUT + 10
        ));
        $this->comment('  php artisan horizon:release --orphaned');
        $this->newLine();
        $this->line('Or inspect the raw Redis entry directly:');
        $this->comment('  redis-cli ZRANGE laravel-database-queues:reports:reserved 0 -1 WITHSCORES');

        return self::SUCCESS;
    }

    protected function findHorizonMasterPids(): array
    {
        // Match command lines ending in "artisan horizon" (no subcommand) — the master.
        $output = shell_exec("pgrep -f 'artisan horizon\$' 2>/dev/null") ?: '';

        return array_values(array_filter(array_map('trim', explode("\n", $output))));
    }

    protected function findHorizonSupervisorPids(): array
    {
        $output = shell_exec("pgrep -f 'artisan horizon:supervisor' 2>/dev/null") ?: '';

        return array_values(array_filter(array_map('trim', explode("\n", $output))));
    }

    protected function findHorizonWorkerPids(): array
    {
        $output = shell_exec("pgrep -f 'artisan horizon:work' 2>/dev/null") ?: '';

        return array_values(array_filter(array_map('trim', explode("\n", $output))));
    }

    protected function removeDeadSupervisorEntries(array $pids): void
    {
        try {
            // Horizon's own connection has the `laravel_horizon:` key prefix
            // baked in, so ZSET / HGETALL operations target the right keys
            // automatically.
            $redis = app('redis')->connection('horizon');
            $members = $redis->zrange('supervisors', 0, -1);

            foreach ($members as $member) {
                $hash = $redis->hgetall("supervisor:{$member}");
                $pid = $hash['pid'] ?? null;
                if ($pid !== null && in_array((string) $pid, $pids, strict: true)) {
                    $redis->zrem('supervisors', $member);
                    $this->line("  Removed supervisor entry: {$member}");
                }
            }
        } catch (\Throwable $e) {
            $this->warn('Could not clean supervisor ZSET: ' . $e->getMessage());
        }
    }
}
