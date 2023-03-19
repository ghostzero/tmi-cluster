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
        return app_path('..');
    }

    public function getSupervisor(): string
    {
        return $this->supervisor;
    }

    public function getTimeout(): int
    {
        return $this->options['timeout'] ?? config('tmi-cluster.process.timeout', 60);
    }

    public function getMemory(): int
    {
        return $this->options['memory'] ?? config('tmi-cluster.process.memory', 128);
    }
}
