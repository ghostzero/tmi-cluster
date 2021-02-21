<?php

namespace GhostZero\TmiCluster\Events;

use GhostZero\TmiCluster\Contracts\ClusterClient;

class ClusterClientRegistered
{
    public ClusterClient $clusterClient;

    public function __construct(ClusterClient $clusterClient)
    {
        $this->clusterClient = $clusterClient;
    }
}
