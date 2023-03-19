<?php

namespace GhostZero\TmiCluster\Listeners;

use GhostZero\TmiCluster\Events\SupervisorLooped;

class MonitorSupervisorMemory
{
    public function handle(SupervisorLooped $event): void
    {
        $supervisor = $event->supervisor;

        if ($supervisor->memoryUsage() > config('tmi-cluster.memory_limit', 64)) {
            $supervisor->terminate(8);
        }
    }
}