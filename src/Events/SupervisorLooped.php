<?php

namespace GhostZero\TmiCluster\Events;

use GhostZero\TmiCluster\Supervisor;

class SupervisorLooped
{
    public Supervisor $supervisor;

    public function __construct(Supervisor $supervisor)
    {
        $this->supervisor = $supervisor;
    }
}
