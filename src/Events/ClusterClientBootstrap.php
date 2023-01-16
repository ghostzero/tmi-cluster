<?php

namespace GhostZero\TmiCluster\Events;

use GhostZero\TmiCluster\Contracts\ClusterClientOptions;

class ClusterClientBootstrap
{
    public ClusterClientOptions $options;

    public function __construct(ClusterClientOptions $options)
    {
        $this->options = $options;
    }
}
