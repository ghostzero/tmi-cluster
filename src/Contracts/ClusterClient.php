<?php

namespace GhostZero\TmiCluster\Contracts;

use Closure;

interface ClusterClient
{
    /**
     * Starts a new TMI Client process.
     *
     * @param ClusterClientOptions $options
     * @param Closure $output
     * @return self
     */
    public static function make(ClusterClientOptions $options, Closure $output): self;

    public function connect(): void;
}
