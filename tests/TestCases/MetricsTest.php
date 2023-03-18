<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Http\Controllers\MetricsController;
use GhostZero\TmiCluster\Tests\TestCase;
use GhostZero\TmiCluster\Tests\Traits\CreatesSupervisors;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetricsTest extends TestCase
{
    use RefreshDatabase, CreatesSupervisors;

    public function testCommandQueue(): void
    {
        $this->createdLoopedSupervisor(2);
        $this->createdLoopedSupervisor(2);

        $controller = new MetricsController();
        $response = $controller->handle();

        self::assertStringContainsString('TYPE tmi_cluster_avg_usage gauge', $response->getContent());
        self::assertStringContainsString('TYPE tmi_cluster_avg_response_time gauge', $response->getContent());
    }
}
