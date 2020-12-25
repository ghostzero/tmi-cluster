<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use GhostZero\TmiCluster\Tests\TestCase;
use GhostZero\TmiCluster\Tests\Traits\CreatesSupervisors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use phpDocumentor\Reflection\Types\Self_;

class StaleTest extends TestCase
{
    use RefreshDatabase, CreatesSupervisors;

    public function testCountHealthySupervisors(): void
    {
        $this->createSupervisor(now()->subMinute(), SupervisorProcess::STATE_CONNECTED);
        $this->createSupervisor(now()->subMinute(), SupervisorProcess::STATE_DISCONNECTED);
        $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);
        $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);
        $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);

        $stale = $this->repository()->all()
            ->filter(fn(Models\Supervisor $supervisor) => $supervisor->is_stale);

        $healthy = $this->repository()->all()
            ->filter(fn(Models\Supervisor $supervisor) => !$supervisor->is_stale);

        self::assertEquals(2, $stale->count());
        self::assertEquals(3, $healthy->count());
    }

    public function repository(): SupervisorRepository
    {
        return app(SupervisorRepository::class);
    }
}