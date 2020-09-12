<?php

namespace GhostZero\TmiCluster;

use Exception;

class TmiCluster
{

    /**
     * Configure the Redis databases that will store TMI Cluster data.
     *
     * @param  string  $connection
     * @return void
     * @throws Exception
     */
    public static function use(string $connection): void
    {
        if (is_null($config = config("database.redis.{$connection}"))) {
            throw new Exception("Redis connection [{$connection}] has not been configured.");
        }

        config(['database.redis.tmi-cluster' => array_merge($config, [
            'options' => ['prefix' => config('tmi-cluster.prefix') ?: 'tmi-cluster:'],
        ])]);
    }
}
