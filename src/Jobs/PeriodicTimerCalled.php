<?php

namespace GhostZero\TmiCluster\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PeriodicTimerCalled
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
}
