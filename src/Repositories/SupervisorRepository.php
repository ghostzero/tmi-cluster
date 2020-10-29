<?php

namespace GhostZero\TmiCluster\Repositories;

use Exception;
use GhostZero\TmiCluster\Contracts\SupervisorRepository as Repository;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\Supervisor;
use GhostZero\TmiCluster\TmiCluster;
use Illuminate\Support\Collection;
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
            'id' => sprintf('%s-%s', gethostname(), Str::random(4)),
            'last_ping_at' => now(),
            'metrics' => [],
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
        $channels = [];

        $this->all()->each(function (Models\Supervisor $supervisor) use (&$channels) {
            if ($supervisor->is_stale) {
                try {
                    $supervisor->processes()->each(function ($process) use (&$channels) {
                        $channels = array_merge($channels, $process->channels);
                        $this->deleteStaleProcess($process);
                    });
                    $supervisor->delete();
                } catch (Exception $e) {
                    throw $e;
                }
            } else {
                $supervisor->processes()->each(fn($process) => $this->deleteIfStaleProcess($process));
            }
        });

        TmiCluster::joinNextServer($channels);
    }

    private function deleteStaleProcess($process): void
    {
        $process->delete();
    }

    private function deleteIfStaleProcess(Models\SupervisorProcess $process): void
    {
        if ($process->is_stale) {
            $this->deleteStaleProcess($process);
        }
    }
}
