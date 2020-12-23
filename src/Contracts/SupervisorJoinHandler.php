<?php

namespace GhostZero\TmiCluster\Contracts;

use GhostZero\TmiCluster\Supervisor;

interface SupervisorJoinHandler
{
    public function handle(Supervisor $supervisor, array $channels): void;
}