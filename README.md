# horizon-running-jobs demo

A fresh Laravel 13 app that consumes the sibling `laravel-horizon-running-jobs` package via Composer **path symlink**. Used to exercise the package against real Horizon workers with real Redis.

## Layout

```
Laravel Applications/
├── laravel-horizon-running-jobs/          # the package (edit here)
└── laravel-horizon-running-jobs-demo/     # this demo (test here)
```

Edits in the package are immediately reflected in the demo — no reinstall needed.

## Prerequisites

- PHP 8.3+
- Redis running on `127.0.0.1:6379` (`redis-cli ping` → `PONG`)
- phpredis extension (`php -m | grep redis`)

## Setup

```bash
# 1. Sibling package is linked via composer.json path repo
composer install

# 2. .env is pre-configured: QUEUE_CONNECTION=redis, REDIS_CLIENT=phpredis
php artisan key:generate

# 3. Horizon is already installed. Start it:
php artisan horizon
```

## Exercising the package

In a second terminal:

```bash
# Queue a realistic mix of jobs
php artisan demo:dispatch-workload

# See what's running
php artisan horizon:running-jobs

# Show stats
php artisan horizon:running-jobs --stats

# JSON output (for scripts)
php artisan horizon:running-jobs --json
```

### Visual widget

The package's auto-refreshing JS widget is wired into the demo at `/running-jobs`:

```bash
php artisan serve          # terminal 3 (if not already serving)
open http://localhost:8000/running-jobs
```

The page polls `/api/horizon/running-jobs` every 3 seconds and renders the running-jobs table. Widget source: `public/vendor/horizon-running-jobs/widget.js` (published from the package's `resources/js/`).

## Dummy jobs

| Job            | Queue    | Duration        | Purpose                        |
|----------------|----------|-----------------|--------------------------------|
| `FastJob`      | default  | 500ms           | Baseline — churns through fast |
| `MediumJob`    | emails   | 5-10s           | Typical workload               |
| `SlowReportJob`| reports  | 30-60s          | Long-running detection         |
| `FlakyJob`     | default  | 2-5s, 50% fail  | Retry path                     |
| `MemoryHogJob` | reports  | ~10s, 200MB     | Memory pressure test           |
| `StuckJob`     | reports  | sleep(9999)     | Zombie / orphan simulation     |

## Scenarios

### Observe long-running jobs

```bash
php artisan demo:dispatch-workload --slow=5
php artisan horizon:running-jobs --stats
```

The 30-60s SlowReportJobs will show up and trigger the long-running-threshold warning (configurable in `config/horizon-running-jobs.php`).

### Simulate an orphaned reserved job

```bash
php artisan demo:simulate-orphan
# Dispatches a StuckJob, waits for a worker to reserve it, then SIGKILLs the worker.
# Job remains in queues:reports:reserved but no live worker owns it.
```

Inspect raw Redis to confirm:

```bash
redis-cli ZRANGE queues:reports:reserved 0 -1 WITHSCORES
```

Once the package's `--orphaned` flag ships (Phase 4 of the implementation plan), this is the scenario that validates it.

### Distributed mode on one machine

`config/horizon.php` uses `gethostname()` as the supervisor key, and `config/horizon-running-jobs.php` sets `server_identifier` to the same. Single machine = one supervisor identity = distributed filtering is a no-op locally. For true multi-supervisor testing, run on two hosts (or two VMs / containers) pointing at the same Redis.

## What this demo validates

Every phase of the package's implementation plan gets exercised here before merge:

- **Phase 1 bug fixes** — run the command, observe corrected behavior.
- **Phase 3 supervisor inspection** — two Horizon instances → `horizon:supervisors` lists both.
- **Phase 4 orphaned jobs** — `demo:simulate-orphan` → `horizon:running-jobs --orphaned`.
- **Phase 5 queue depth** — `demo:dispatch-workload --fast=500` → `horizon:queues`.
