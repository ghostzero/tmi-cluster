<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Contracts\ClusterClientOptions;
use GhostZero\TmiCluster\Events\PeriodicTimerCalled;
use GhostZero\TmiCluster\Listeners\MonitorClusterClientMemory;
use GhostZero\TmiCluster\Tests\TestCase;
use GhostZero\TmiCluster\TmiClusterClient;
use Mockery;

class MonitorClusterClientMemoryTest extends TestCase
{
    public function testTmiClusterIsTerminatedWhenUsingToMuchMemory(): void
    {
        $monitor = new MonitorClusterClientMemory();

        $clusterClient = Mockery::mock(TmiClusterClient::class);
        $clusterClient->options = new ClusterClientOptions(
            'uuid',
            'supervisor',
            false,
            64,
            false
        );

        $clusterClient->shouldReceive('memoryUsage')->andReturn(192);
        $clusterClient->shouldReceive('terminate')->once()->with(8);

        $monitor->handle(new PeriodicTimerCalled($clusterClient));
    }

    public function testTmiClusterIsNotTerminatedWhenUsingToMuchMemory(): void
    {
        $monitor = new MonitorClusterClientMemory();

        $clusterClient = Mockery::mock(TmiClusterClient::class);
        $clusterClient->options = new ClusterClientOptions(
            'uuid',
            'supervisor',
            false,
            64,
            false
        );

        $clusterClient->shouldReceive('memoryUsage')->andReturn(64);
        $clusterClient->shouldReceive('terminate')->never();

        $monitor->handle(new PeriodicTimerCalled($clusterClient));
    }
}
