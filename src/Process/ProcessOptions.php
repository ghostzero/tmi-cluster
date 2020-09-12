<?php

namespace GhostZero\TmiCluster\Process;

class ProcessOptions
{
    private array $options;
    private string $supervisor;

    public function __construct(string $supervisor, array $options)
    {
        $this->supervisor = $supervisor;
        $this->options = $options;
    }

    public function toWorkerCommand(string $uuid): string
    {
        return CommandString::fromOptions($this, $uuid);
    }

    public function getWorkingDirectory(): string
    {
        return __DIR__;
    }

    public function getSupervisor(): string
    {
        return $this->supervisor;
    }
}
