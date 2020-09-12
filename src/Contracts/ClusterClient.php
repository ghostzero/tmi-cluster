<?php

namespace GhostZero\TmiCluster\Contracts;

interface ClusterClient
{
    /**
     * Starts a new TMI Client process.
     *
     * @param ClusterClientOptions $options
     * @return int
     */
    public function connect(ClusterClientOptions $options): int;
}
