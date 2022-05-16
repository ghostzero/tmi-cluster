<?php

namespace GhostZero\TmiCluster\Facades;

use GhostZero\TmiCluster\TmiCluster as TmiClusterService;
use Illuminate\Support\Facades\Facade;

/**
 * @see \GhostZero\TmiCluster\TmiCluster::routes
 * @method static void routes($callback = null, array $options = [])
 * @see \GhostZero\TmiCluster\Traits\TmiClusterHelpers::sendMessage
 * @method static void sendMessage(string $channel, $message)
 * @see \GhostZero\TmiCluster\TmiCluster::use
 * @method static void use(string $connection)
 * @see \GhostZero\TmiCluster\Traits\TmiClusterHelpers::joinNextServer
 * @method static void joinNextServer(array $channels, array $staleIds = [])
 * @see \GhostZero\TmiCluster\TmiCluster::routeSlackNotificationsTo
 * @method static void routeSlackNotificationsTo(string $url, string $channel = null)
 * @see \GhostZero\TmiCluster\TmiCluster::routeSmsNotificationsTo
 * @method static void routeSmsNotificationsTo(string $number)
 * @see \GhostZero\TmiCluster\TmiCluster::routeMailNotificationsTo
 * @method static void routeMailNotificationsTo(string $email)
 */
class TmiCluster extends Facade
{
    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return TmiClusterService::class;
    }
}
