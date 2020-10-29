<?php

namespace GhostZero\TmiCluster\Contracts;

interface Pausable
{
    /**
     * Pause the process.
     *
     * @return void
     */
    public function pause(): void;

    /**
     * Instruct the process to continue working.
     *
     * @return void
     */
    public function continue(): void;
}
