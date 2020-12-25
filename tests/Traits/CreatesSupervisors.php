<?php


namespace GhostZero\TmiCluster\Tests\Traits;


use Carbon\CarbonInterface;
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
}