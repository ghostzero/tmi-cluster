<?php

namespace GhostZero\TmiCluster\Events;

use GhostZero\TmiCluster\Process\Process;

class WorkerProcessRestarting
{
    private Process $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
    }

}
