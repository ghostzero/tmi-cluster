<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Contracts\ClusterClient;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use GhostZero\TmiCluster\Repositories\DatabaseChannelManager;
use GhostZero\TmiCluster\Repositories\DummyChannelManager;
use GhostZero\TmiCluster\Repositories\RedisChannelDistributor;
use GhostZero\TmiCluster\Tests\TestCase;
use GhostZero\TmiCluster\Tests\Traits\CreatesSupervisors;
use GhostZero\TmiCluster\TwitchLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChannelDistributorTest extends TestCase
{
    use RefreshDatabase, CreatesSupervisors;

    public function testAsyncChannelJoin(): void
    {
        $this->useChannelManager(DatabaseChannelManager::class)
            ->authorize(new TwitchLogin('test1'))
            ->authorize(new TwitchLogin('test2'))
            ->authorize(new TwitchLogin('test3'))
            ->authorize(new TwitchLogin('test4'));

        $this->getChannelDistributor()->join(['test1', 'test2', 'test_unauthorized']);
        $this->getChannelDistributor()->join(['test3']);

        $uuid = $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);

        $result = $this->getChannelDistributor()->joinNow(['test4'], []);

        self::assertEquals([
            'rejected' => [],
            'resolved' => [
                '#test1' => $uuid,
                '#test2' => $uuid,
                '#test3' => $uuid,
                '#test4' => $uuid,
            ],
            'ignored' => [],
            'unauthorized' => [
                '#test_unauthorized'
            ],
        ], $result);
    }

    public function testChannelGotRejectedDueMissingServers(): void
    {
        $this->useChannelManager(DummyChannelManager::class);

        $result = $this->getChannelDistributor()->joinNow(['ghostzero'], []);

        self::assertEquals([
            'rejected' => ['#ghostzero'],
            'resolved' => [],
            'ignored' => [],
            'unauthorized' => [],
        ], $result);
    }

    public function testChannelGotRejectedWithUnhealthyServers(): void
    {
        $this->useChannelManager(DummyChannelManager::class);

        $this->createSupervisor(now()->subSeconds(5), SupervisorProcess::STATE_CONNECTED);
        $this->createSupervisor(now(), SupervisorProcess::STATE_INITIALIZE);

        $result = $this->getChannelDistributor()->joinNow(['ghostzero'], []);

        self::assertEquals([
            'rejected' => ['#ghostzero'],
            'resolved' => [],
            'ignored' => [],
            'unauthorized' => [],
        ], $result);
    }

    public function testChannelGotResolvedDueActiveServer(): void
    {
        $this->useChannelManager(DummyChannelManager::class);

        $uuid = $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);

        $result = $this->getChannelDistributor()->joinNow(['ghostzero'], []);

        self::assertEquals([
            'rejected' => [],
            'resolved' => ['#ghostzero' => $uuid],
            'ignored' => [],
            'unauthorized' => [],
        ], $result);
    }

    public function testChannelGotIgnoredDueAlreadyJoined(): void
    {
        $this->useChannelManager(DummyChannelManager::class);

        $uuid = $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);

        $result = $this->getChannelDistributor()->joinNow(['ghostzero'], []);

        self::assertEquals([
            'rejected' => [],
            'resolved' => ['#ghostzero' => $uuid],
            'ignored' => [],
            'unauthorized' => [],
        ], $result);

        $uuid2 = $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);

        self::assertNotEquals($uuid, $uuid2);

        $result = $this->getChannelDistributor()->joinNow(['ghostzero', 'test', 'test2', 'test3', 'test4'], []);

        self::assertEquals([
            'rejected' => [],
            'resolved' => [
                '#test' => $uuid2, // because the second process has the lowest channels amount
                '#test2' => $uuid, // because the first process has the lowest channels amount
                '#test3' => $uuid2, // because the second process has the lowest channels amount
                '#test4' => $uuid, // because the first process has the lowest channels amount
            ],
            'ignored' => [
                '#ghostzero' => $uuid, // because they got already joined in the first server
            ],
            'unauthorized' => [],
        ], $result);

        $this->assertGotQueued($result, 5);
    }

    public function testChannelGotResolvedAfterInactiveSupervisor(): void
    {
        $this->useChannelManager(DummyChannelManager::class);

        // join server normally
        $uuid = $this->createSupervisor(now()->subSeconds(2), SupervisorProcess::STATE_CONNECTED, [
            'ghostdemouser', // this channel got already connected
        ]);
        $result = $this->getChannelDistributor()->joinNow(['ghostzero'], []);
        self::assertEquals([
            'rejected' => [],
            'resolved' => ['#ghostzero' => $uuid],
            'ignored' => [],
            'unauthorized' => [],
        ], $result);

        // let's kill all servers and re-join all lost channels into some fresh servers
        sleep(1);
        $newUuid = $this->createSupervisor(now(), SupervisorProcess::STATE_CONNECTED);
        $result = $this->getChannelDistributor()->flushStale([
            '#ghostdemouser'
        ], [$uuid]); // this simulates our flush stale

        // check if our previous server got restored
        self::assertEquals([
            'rejected' => [],
            'resolved' => [
                '#ghostzero' => $newUuid, // got restored from our lost queue
                '#ghostdemouser' => $newUuid, // got restored from the flush stale
            ],
            'ignored' => [],
            'unauthorized' => [],
        ], $result);
    }

    private function getChannelDistributor(): ChannelDistributor
    {
        return app(RedisChannelDistributor::class);
    }

    private function useChannelManager(string $concrete): ChannelManager
    {
        $this->app->singleton(ChannelManager::class, $concrete);

        return app(ChannelManager::class);
    }

    private function assertGotQueued(array $result, int $expectedCount): void
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

    private function getPendingCommands(array $processIds): array
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commands = [];
        foreach ($processIds as $processId) {
            $commands[] = $commandQueue->pending(ClusterClient::getQueueName($processId, ClusterClient::QUEUE_INPUT));
        }
        $commands = array_merge(...$commands);
        return $commands;
    }
}