<?php

namespace App\Jobs;

use Ashiqfardus\HorizonRunningJobs\Traits\TracksServer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class FlakyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksServer;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public int $id = 0)
    {
        $this->initializeServerTracking();
    }

    public function handle(): void
    {
        sleep(random_int(2, 5));

        if (random_int(1, 100) <= 50) {
            throw new RuntimeException('FlakyJob: simulated failure');
        }
    }
}
