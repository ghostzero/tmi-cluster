<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use Carbon\CarbonInterface;
use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use GhostZero\TmiCluster\Repositories\RedisChannelManager;
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

        self::assertEquals(['rejected' => [], 'resolved' => ['ghostzero' => $uuid], 'ignored' => []], $result);

        $uuid2 = $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);

        self::assertNotEquals($uuid, $uuid2);

        $result = $this->getChannelDistributor()->joinNow(['ghostzero', 'test', 'test2', 'test3', 'test4'], []);

        self::assertEquals(['rejected' => [], 'resolved' => [
            'test' => $uuid2, // because the second process has the lowest channels amount
            'test2' => $uuid, // because the first process has the lowest channels amount
            'test3' => $uuid2, // because the second process has the lowest channels amount
            'test4' => $uuid, // because the first process has the lowest channels amount
        ], 'ignored' => [
            'ghostzero' => $uuid, // because they got already joined in the first server
        ]], $result);

        $this->assertGotQueued($result, 5);
    }

    private function getChannelDistributor(): ChannelDistributor
    {
        return app(RedisChannelManager::class);
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

    private function assertGotQueued(array $result, int $expectedCount)
    {
        $processIds = array_unique(array_merge(
            array_values($result['rejected']),
            array_values($result['resolved']),
            array_values($result['ignored'])
        ));

        self::assertNotEmpty($processIds);
        $commands = $this->getPendingCommands($processIds);
        self::assertCount($expectedCount, $commands);
    }

    /**
     * @param array $processIds
     * @return array
     */
    private function getPendingCommands(array $processIds): array
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commands = [];
        foreach ($processIds as $processId) {
            $commands[] = $commandQueue->pending($processId);
        }
        $commands = array_merge(...$commands);
        return $commands;
    }
}