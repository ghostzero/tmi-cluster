<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use Carbon\CarbonInterface;
use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use GhostZero\TmiCluster\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class JoinHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function testChannelGotRejectedDueMissingServers(): void
    {
        $result = $this->getChannelDistributor()->joinNow(['ghostzero'], []);

        self::assertEquals(['rejected' => ['ghostzero'], 'resolved' => [], 'ignored' => []], $result);
    }

    public function testChannelGotRejectedWithUnhealthyServers(): void
    {
        $this->createSupervisor(now()->subMinute(), SupervisorProcess::STATE_CONNECTED);
        $this->createSupervisor(now(), SupervisorProcess::STATE_INITIALIZE);

        $result = $this->getChannelDistributor()->joinNow(['ghostzero'], []);

        self::assertEquals(['rejected' => ['ghostzero'], 'resolved' => [], 'ignored' => []], $result);
    }

    public function testChannelGotResolvedDueActiveServer(): void
    {
        $uuid = $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);

        $result = $this->getChannelDistributor()->joinNow(['ghostzero'], []);

        self::assertEquals(['rejected' => [], 'resolved' => ['ghostzero' => $uuid], 'ignored' => []], $result);
    }

    public function testChannelGotIgnoredDueAlreadyJoined(): void
    {
        $uuid = $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);

        $result = $this->getChannelDistributor()->joinNow(['ghostzero'], []);

        self::assertEquals(['rejected' => [], 'resolved' => [], 'ignored' => ['ghostzero' => $uuid]], $result);

        $uuid = $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);

        $result = $this->getChannelDistributor()->joinNow(['ghostzero'], []);

        self::assertEquals(['rejected' => [], 'resolved' => [], 'ignored' => [
            'ghostzero' => $uuid,
        ]], $result);
    }

    private function getChannelDistributor(): ChannelDistributor
    {
        return app(ChannelDistributor::class);
    }

    private function createSupervisor(CarbonInterface $lastPingAt, string $state, array $channels = []): string
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