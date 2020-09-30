<?php

namespace GhostZero\TmiCluster\Repositories;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Predis\ClientInterface;

class RedisCommandQueue implements CommandQueue
{

    /**
     * The Redis connection instance.
     *
     * @var RedisFactory|ClientInterface
     */
    public $redis;

    /**
     * Create a new command queue instance.
     *
     * @param RedisFactory $redis
     * @return void
     */
    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @inheritDoc
     * @noinspection PhpParamsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function push(string $name, string $command, array $options = [])
    {
        $this->connection()->rpush('commands:'.$name, json_encode([
            'command' => $command,
            'options' => $options,
            'time' => microtime(),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @inheritDoc
     */
    public function pending(string $name): array
    {
        $length = $this->connection()->llen('commands:'.$name);

        if ($length < 1) {
            return [];
        }

        $results = $this->connection()->pipeline(function ($pipe) use ($name, $length) {
            $pipe->lrange('commands:'.$name, 0, $length - 1);

            $pipe->ltrim('commands:'.$name, $length, -1);
        });

        return collect($results[0])->map(function ($result) {
            return json_decode($result, false, 512, JSON_THROW_ON_ERROR);
        })->all();
    }

    /**
     * @inheritDoc
     */
    public function flush(string $name): void
    {
        $this->connection()->del('commands:'.$name);
    }

    /**
     * Get the Redis connection instance.
     *
     * @return Connection|ClientInterface
     */
    protected function connection()
    {
        return $this->redis->connection('tmi-cluster');
    }
}
