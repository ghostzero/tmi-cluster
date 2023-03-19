<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Events\SupervisorLooped;
use GhostZero\TmiCluster\Listeners\MonitorSupervisorMemory;
use GhostZero\TmiCluster\Supervisor;
use GhostZero\TmiCluster\Tests\TestCase;
use Mockery;

class MonitorSupervisorMemoryTest extends TestCase
{
    public function testSupervisorIsTerminatedWhenUsingToMuchMemory(): void
    {
        $monitor = new MonitorSupervisorMemory();

        $supervisor = Mockery::mock(Supervisor::class);

        $supervisor->shouldReceive('memoryUsage')->andReturn(65);
        $supervisor->shouldReceive('terminate')->once()->with(8);

        $monitor->handle(new SupervisorLooped($supervisor));
    }

    public function testSupervisorIsNotTerminatedWhenUsingToMuchMemory(): void
    {
        $monitor = new MonitorSupervisorMemory();

        $supervisor = Mockery::mock(Supervisor::class);

        $supervisor->shouldReceive('memoryUsage')->andReturn(64);
        $supervisor->shouldReceive('terminate')->never();

        $monitor->handle(new SupervisorLooped($supervisor));
    }
}
