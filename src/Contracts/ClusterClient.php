<?php

namespace GhostZero\TmiCluster\Contracts;

use Closure;
use GhostZero\Tmi\Client;

abstract class ClusterClient
{
    public const QUEUE_INPUT = 'input';
    public ClusterClientOptions $options;
    public Closure $output;

    public function __construct(ClusterClientOptions $options, Closure $output)
    {
        $this->options = $options;
        $this->output = $output;
    }

    /**
     * Starts a new TMI Client process.
     *
     * @param ClusterClientOptions $options
     * @param Closure $output
     * @return self
     */
    abstract public static function make(ClusterClientOptions $options, Closure $output): self;

    public static function getQueueName(string $id, string $name): string
    {
        return $id . '-' . $name;
    }

    abstract public function connect(): void;

    abstract public function pause(): void;

    abstract public function continue(): void;

    abstract public function restart(): void;

    abstract public function terminate(int $status = 0): void;

    abstract public function getTmiClient(): Client;

    abstract public function log(string $message): void;

    abstract public function getUuid();

    public function memoryUsage(): float
    {
        return memory_get_usage() / 1024 / 1024;
    }
}
