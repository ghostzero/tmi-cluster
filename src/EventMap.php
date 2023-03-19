<?php

namespace GhostZero\TmiCluster;

trait EventMap
{
    /**
     * All the TMI Cluster event / listener mappings.
     *
     * @var array
     */
    protected array $events = [
        Events\ProcessScaled::class => [
            Listeners\SendNotification::class,
        ],

        Events\SupervisorLooped::class => [
            Listeners\MonitorSupervisorMemory::class,
        ],

        Events\PeriodicTimerCalled::class => [
            Listeners\MonitorClusterClientMemory::class,
        ],
    ];
}
