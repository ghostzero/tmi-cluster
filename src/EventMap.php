<?php

namespace GhostZero\TmiCluster;

trait EventMap
{
    /**
     * All of the Horizon event / listener mappings.
     *
     * @var array
     */
    protected $events = [
        Events\AutoScaleScaled::class => [
            Listeners\SendNotification::class,
        ],
    ];
}
