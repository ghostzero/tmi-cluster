<?php

namespace GhostZero\TmiCluster\Repositories;

use DomainException;
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

        $this->all()
            ->filter(fn(Models\Supervisor $supervisor) => $supervisor->is_stale)
            ->each(fn(Models\Supervisor $supervisor) => $supervisor->delete());

        Models\SupervisorProcess::query()->get()
            ->filter(fn(Models\SupervisorProcess $process) => $process->is_stale)
            ->each(function (Models\SupervisorProcess $process) use (&$channels, &$staleIds) {
                $staleIds[] = $process->getKey();
                $channels[] = $process->channels;
                $process->delete();
            });

        app(ChannelDistributor::class)->flushStale(array_merge(...$channels), $staleIds);
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
