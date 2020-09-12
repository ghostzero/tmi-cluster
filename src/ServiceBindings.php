<?php

namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Contracts;

trait ServiceBindings
{
    /**
     * All of the service bindings for our TMI Cluster.
     *
     * @var array
     */
    public $serviceBindings = [
        // General services...
        Contracts\ClusterClient::class => TmiClusterClient::class,

        // Repository services...
        Contracts\SupervisorRepository::class => Repositories\SupervisorRepository::class,
        Contracts\CommandQueue::class => Repositories\RedisCommandQueue::class,
    ];
}
