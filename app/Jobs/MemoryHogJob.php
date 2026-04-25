<?php

namespace App\Jobs;

use Ashiqfardus\HorizonRunningJobs\Traits\TracksServer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MemoryHogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksServer;

    public int $timeout = 60;

    public function __construct(public int $id = 0, public int $megabytes = 200)
    {
        $this->initializeServerTracking();
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        $chunks = [];
        for ($i = 0; $i < $this->megabytes; $i++) {
            $chunks[] = str_repeat('x', 1024 * 1024);
            usleep(50_000);
        }
        sleep(5);
    }
}
