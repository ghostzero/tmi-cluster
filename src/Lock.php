<?php

namespace GhostZero\TmiCluster;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Limiters\DurationLimiterBuilder;

class Lock
{
    public RedisFactory $redis;

    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    public function with(string $key, callable $callback, int $seconds = 60): void
    {
        if ($this->get($key, $seconds)) {
            try {
                $callback();
            } finally {
                $this->release($key);
            }
        }
    }

    public function exists(string $key): bool
    {
        return $this->connection()->exists($key) === 1;
    }

    public function get(string $key, int $seconds = 60): bool
    {
        $result = $this->connection()->setnx($key, 1);

        if ($result === 1) {
            $this->connection()->expire($key, $seconds);
        }

        return $result === 1;
    }

    public function release(string $key): void
    {
        $this->connection()->del($key);
    }

    public function connection(): Connection
    {
        return $this->redis->connection('tmi-cluster');
    }

    /**
     * Throttle a callback for a maximum number of executions over a given duration.
     *
     * @param string $name
     * @return DurationLimiterBuilder
     */
    public function throttle($name)
    {
        return new DurationLimiterBuilder($this->connection(), 'throttle:' . $name);
    }
}
