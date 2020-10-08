<?php

namespace GhostZero\TmiCluster\Contracts;

use Closure;

interface ClusterClient
{
    /**
     * Starts a new TMI Client process.
     *
     * @param ClusterClientOptions $options
     * @return self
     */
    public static function make(ClusterClientOptions $options): self;

    public function connect(): void;

    public function handleOutputUsing(Closure $output): self;
}
