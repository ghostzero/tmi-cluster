<?php


namespace GhostZero\TmiCluster\Tests\Traits;


use Carbon\CarbonInterface;
use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use Illuminate\Support\Str;

trait CreatesSupervisors
{
    protected function createSupervisor(CarbonInterface $lastPingAt, string $state, array $channels = []): string
    {
        /** @var Supervisor $supervisor */
        $supervisor = Supervisor::query()->forceCreate([
            'id' => Str::uuid(),
            'last_ping_at' => $lastPingAt,
            'metrics' => [],
            'options' => ['nice' => 0]
        ]);

        /** @var SupervisorProcess $process */
        $process = $supervisor->processes()->forceCreate([
            'id' => Str::uuid(),
            'supervisor_id' => $supervisor->getKey(),
            'state' => $state,
            'channels' => $channels,
            'last_ping_at' => $lastPingAt,
        ]);

        return (string)$process->getKey();
    }

    public function createdLoopedSupervisor(int $scale): \GhostZero\TmiCluster\Supervisor
    {
        /** @var \GhostZero\TmiCluster\Supervisor $supervisor */
        $supervisor = app(SupervisorRepository::class)->create();

        self::assertStringStartsWith(gethostname(), $supervisor->model->getKey());

        $supervisor->scale($scale);
        $supervisor->loop();

        self::assertCount($scale, $supervisor->processes());

        return $supervisor;
    }
}