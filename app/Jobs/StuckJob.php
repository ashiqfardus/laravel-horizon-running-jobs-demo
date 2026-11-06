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

    /**
     * @param  int  $timeout  Seconds before the worker gives up on the job.
     *                        Orphan release only picks up jobs past it.
     */
    public function __construct(public int $id = 0, public int $timeout = 600)
    {
        $this->initializeServerTracking();
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        sleep(9999);
    }
}
