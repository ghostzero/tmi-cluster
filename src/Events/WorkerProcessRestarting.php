<?php

namespace GhostZero\TmiCluster\Events;

use GhostZero\TmiCluster\Process\Process;

class WorkerProcessRestarting
{
    public Process $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
    }
}
