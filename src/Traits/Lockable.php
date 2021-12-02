<?php

namespace GhostZero\TmiCluster\Traits;

use Illuminate\Cache\RedisLock;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\Connection;
use Predis\ClientInterface;

trait Lockable
{
    /**
     * Get the Redis connection instance.
     *
     * @return Connection|ClientInterface
     */
    protected function connection()
    {
        return app(Factory::class)->connection('tmi-cluster');
    }

    protected function lock(string $name, int $seconds = 0, ?string $owner = null): RedisLock
    {
        return new RedisLock($this->connection(), $name, $seconds, $owner);
    }
}