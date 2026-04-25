<?php

namespace App\Console\Commands;

use App\Jobs\FastJob;
use App\Jobs\FlakyJob;
use App\Jobs\MediumJob;
use App\Jobs\MemoryHogJob;
use App\Jobs\SlowReportJob;
use App\Jobs\StuckJob;
use Illuminate\Console\Command;

class DispatchWorkload extends Command
{
    protected $signature = 'demo:dispatch-workload
                            {--fast=20 : Number of FastJob instances}
                            {--medium=10 : Number of MediumJob instances}
                            {--slow=3 : Number of SlowReportJob instances}
                            {--flaky=5 : Number of FlakyJob instances}
                            {--memory=1 : Number of MemoryHogJob instances}
                            {--stuck=0 : Number of StuckJob instances (use with caution)}';

    protected $description = 'Dispatch a mixed workload of demo jobs to exercise horizon:running-jobs';

    public function handle(): int
    {
        $counts = [
            'FastJob' => (int) $this->option('fast'),
            'MediumJob' => (int) $this->option('medium'),
            'SlowReportJob' => (int) $this->option('slow'),
            'FlakyJob' => (int) $this->option('flaky'),
            'MemoryHogJob' => (int) $this->option('memory'),
            'StuckJob' => (int) $this->option('stuck'),
        ];

        for ($i = 1; $i <= $counts['FastJob']; $i++) {
            FastJob::dispatch($i);
        }
        for ($i = 1; $i <= $counts['MediumJob']; $i++) {
            MediumJob::dispatch($i);
        }
        for ($i = 1; $i <= $counts['SlowReportJob']; $i++) {
            SlowReportJob::dispatch($i);
        }
        for ($i = 1; $i <= $counts['FlakyJob']; $i++) {
            FlakyJob::dispatch($i);
        }
        for ($i = 1; $i <= $counts['MemoryHogJob']; $i++) {
            MemoryHogJob::dispatch($i);
        }
        for ($i = 1; $i <= $counts['StuckJob']; $i++) {
            StuckJob::dispatch($i);
        }

        $total = array_sum($counts);
        $this->info("Dispatched {$total} jobs:");
        foreach ($counts as $class => $count) {
            if ($count > 0) {
                $this->line("  - {$class}: {$count}");
            }
        }

        $this->newLine();
        $this->comment('Now run: php artisan horizon:running-jobs');
        $this->comment('Or watch: watch -n 1 php artisan horizon:running-jobs --stats');

        return self::SUCCESS;
    }
}
