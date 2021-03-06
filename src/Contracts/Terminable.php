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
    public function terminate($status = 0): void;
}
