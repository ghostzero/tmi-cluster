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

    public function testSupervisorProcess(): void
    {
        /** @var Supervisor $supervisor */
        $supervisor = app(SupervisorRepository::class)->create([]);

        $supervisor->handleOutputUsing(function ($level, $line) {
            fwrite(STDERR, print_r($line, TRUE));
        });

        self::assertStringStartsWith(gethostname(), $supervisor->model->id);

        $supervisor->scale(2);
        $supervisor->loop();

        sleep(1);

        print 'test';

        self::assertCount(2, $supervisor->processes());
    }
}
