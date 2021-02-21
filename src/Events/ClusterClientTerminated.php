<?php

namespace GhostZero\TmiCluster\Events;

use GhostZero\TmiCluster\Contracts\ClusterClient;

class ClusterClientTerminated
{
    public ClusterClient $clusterClient;

    public function __construct(ClusterClient $clusterClient)
    {
        $this->clusterClient = $clusterClient;
    }
}
