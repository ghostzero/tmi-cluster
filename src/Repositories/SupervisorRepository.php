<?php

namespace GhostZero\TmiCluster\Repositories;

use DomainException;
use Exception;
use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use GhostZero\TmiCluster\Contracts\SupervisorRepository as Repository;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\Supervisor;
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
            'id' => $this->generateUniqueSupervisorKey(),
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
        $staleIds = [];

        $this->all()->each(function (Models\Supervisor $supervisor) use (&$channels, &$staleIds) {
            if ($supervisor->is_stale) {
                try {
                    $staleIds[] = $supervisor->getKey();
                    $supervisor->processes()->each(function (Models\SupervisorProcess $process) use (&$channels, &$staleIds) {
                        $staleIds[] = $process->getKey();
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

        app(ChannelDistributor::class)->flushStale($channels, $staleIds);
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

    private function generateUniqueSupervisorKey(int $length = 4): string
    {
        $key = sprintf('%s-%s', gethostname(), Str::random($length));

        $exists = Models\Supervisor::query()
            ->whereKey($key)
            ->exists();

        if ($exists) {
            throw new DomainException('A Supervisor with this name already exists.');
        }

        return $key;
    }
}
