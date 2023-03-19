<?php

namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Contracts;

trait ServiceBindings
{
    /**
     * All the service bindings for our TMI Cluster.
     *
     * @var array
     */
    public array $serviceBindings = [
        // General services...
        AutoScale::class,
        AutoCleanup::class,
        AutoReconnect::class,
        TmiClusterClient::class,

        // Repository services...
        Contracts\SupervisorRepository::class => Repositories\SupervisorRepository::class,
        Contracts\CommandQueue::class => Repositories\RedisCommandQueue::class,
        Contracts\ChannelDistributor::class => Repositories\RedisChannelDistributor::class,
        Contracts\SupervisorJoinHandler::class => Repositories\RedisChannelDistributor::class,
        Contracts\ChannelManager::class => Repositories\DatabaseChannelManager::class,
    ];
}
