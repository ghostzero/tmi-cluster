<?php

namespace GhostZero\TmiCluster\Listeners;

use GhostZero\TmiCluster\Events\PeriodicTimerCalled;

class MonitorClusterClientMemory
{
    public function handle(PeriodicTimerCalled $event): void
    {
        $clusterClient = $event->clusterClient;

        if ($clusterClient->memoryUsage() > $clusterClient->options->getMemory()) {
            $clusterClient->terminate(8);
        }
    }
}