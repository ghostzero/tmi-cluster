<?php

namespace GhostZero\TmiCluster\Contracts;

use Exception;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\Supervisor;
use Illuminate\Support\Collection;

interface SupervisorRepository
{
    /**
     * Create a new Supervisor instance.
     *
     * @param array $attributes
     * @return Supervisor
     */
    public function create(array $attributes = []): Supervisor;

    /**
     * Get information on all of supervisors.
     *
     * @param string[] $columns
     * @return Models\Supervisor[]|Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Flush all stale supervisors from database.
     * @throws Exception
     */
    public function flushStale(): void;
}
