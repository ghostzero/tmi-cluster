<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use GhostZero\TmiCluster\Tests\TestCase;
use GhostZero\TmiCluster\Tests\Traits\CreatesSupervisors;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaleTest extends TestCase
{
    use RefreshDatabase, CreatesSupervisors;

    public function testCountHealthySupervisors(): void
    {
        $staleSeconds = config('tmi-cluster.supervisor.stale', 300);

        self::assertGreaterThan(0, $staleSeconds);

        $this->createSupervisor(now()->subSeconds($staleSeconds), SupervisorProcess::STATE_CONNECTED);
        $this->createSupervisor(now()->subSeconds($staleSeconds), SupervisorProcess::STATE_DISCONNECTED);
        $this->createSupervisor(now()->subSeconds($staleSeconds - 10), SupervisorProcess::STATE_DISCONNECTED);
        $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);
        $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);
        $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);

        $stale = $this->repository()->all()
            ->filter(fn(Models\Supervisor $supervisor) => $supervisor->is_stale);

        $healthy = $this->repository()->all()
            ->filter(fn(Models\Supervisor $supervisor) => !$supervisor->is_stale);

        self::assertEquals(2, $stale->count());
        self::assertEquals(4, $healthy->count());
    }

    public function repository(): SupervisorRepository
    {
        return app(SupervisorRepository::class);
    }
}