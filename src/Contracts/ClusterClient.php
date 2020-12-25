<?php

namespace GhostZero\TmiCluster\Contracts;

use Closure;

abstract class ClusterClient
{
    public const QUEUE_INPUT = 'input';

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
}
