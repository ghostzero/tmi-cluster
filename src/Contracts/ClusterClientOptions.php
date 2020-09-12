<?php

namespace GhostZero\TmiCluster\Contracts;

class ClusterClientOptions
{
    private string $uuid;
    private string $supervisor;
    private bool $stopWhenEmpty;
    private int $memory;
    private bool $force;

    public function __construct(
        string $uuid,
        string $supervisor,
        bool $stopWhenEmpty,
        int $memory,
        bool $force
    )
    {
        $this->uuid = $uuid;
        $this->supervisor = $supervisor;
        $this->stopWhenEmpty = $stopWhenEmpty;
        $this->memory = $memory;
        $this->force = $force;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getSupervisor(): string
    {
        return $this->supervisor;
    }

    public function isStopWhenEmpty(): bool
    {
        return $this->stopWhenEmpty;
    }

    public function getMemory(): int
    {
        return $this->memory;
    }

    public function isForce(): bool
    {
        return $this->force;
    }
}
