<?php

namespace App\Jobs;

use Ashiqfardus\HorizonRunningJobs\Traits\TracksServer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StuckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksServer;

    public int $timeout = 600;

    public function __construct(public int $id = 0)
    {
        $this->initializeServerTracking();
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        sleep(9999);
    }
}
