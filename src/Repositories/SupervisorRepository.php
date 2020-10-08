<?php

namespace GhostZero\TmiCluster\Repositories;

use Exception;
use GhostZero\TmiCluster\Contracts\SupervisorRepository as Repository;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\Supervisor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SupervisorRepository implements Repository
{
    /**
     * @inheritDoc
     */
    public function create(array $attributes = []): Supervisor
    {
        /** @var Models\Supervisor $supervisor */
        $supervisor = Models\Supervisor::query()->create(array_merge([
            'name' => sprintf('%s-%s', gethostname(), Str::random(4)),
            'last_ping_at' => now(),
            'options' => [
                'nice' => 0,
            ]
        ], $attributes));

        return new Supervisor($supervisor);
    }

    /**
     * @inheritDoc
     */
    public function all($columns = ['*']): Collection
    {
        return Models\Supervisor::all($columns)->collect();
    }

    /**
     * @inheritDoc
     */
    public function flushStale(): void
    {
        $this->all()->each(function (Models\Supervisor $supervisor) {
            if ($supervisor->getIsStaleAttribute()) {
                try {
                    $supervisor->delete();
                } catch (Exception $e) {
                    throw $e;
                }
            }
        });
    }
}
