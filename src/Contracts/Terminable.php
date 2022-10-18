<?php

namespace GhostZero\TmiCluster\Contracts;

interface Terminable
{
    /**
     * Terminate the process.
     *
     * @param int $status
     * @return void
     */
    public function terminate(int $status = 0): void;
}
