<?php

namespace GhostZero\TmiCluster;

trait EventMap
{
    /**
     * All of the TMI Cluster event / listener mappings.
     *
     * @var array
     */
    protected $events = [
        Events\ProcessScaled::class => [
            Listeners\SendNotification::class,
        ],
    ];
}
