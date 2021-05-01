<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Supervisor;
use GhostZero\TmiCluster\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TmiClusterTest extends TestCase
{
    use RefreshDatabase;

    public function testSupervisor(): void
    {
        /** @var Supervisor $supervisor */
        $supervisor = app(SupervisorRepository::class)->create([]);

        self::assertStringStartsWith(gethostname(), $supervisor->model->getKey());

        $supervisor->scale(2);
        $supervisor->loop();

        self::assertCount(2, $supervisor->processes());
    }
}
