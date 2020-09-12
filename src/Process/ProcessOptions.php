<?php

namespace GhostZero\TmiCluster\Process;

class ProcessOptions
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function toWorkerCommand(): string
    {
        return CommandString::fromOptions($this);
    }

    public function getWorkingDirectory(): string
    {
        return __DIR__;
    }
}
