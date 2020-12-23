<?php

namespace GhostZero\TmiCluster\Repositories;

use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Contracts\SupervisorJoinHandler;
use GhostZero\TmiCluster\Lock;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\Process\Process;
use GhostZero\TmiCluster\Supervisor;
use GhostZero\TmiCluster\Traits\JoinQueuedChannels;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Support\Collection;
use stdClass;

class RedisChannelManager implements SupervisorJoinHandler, ChannelDistributor
{
    use JoinQueuedChannels;

    private Lock $lock;

    public function __construct()
    {
        $this->lock = app(Lock::class);
    }

    public function handle(Supervisor $supervisor, array $channels): void
    {
        $uuids = $supervisor->processes()->map(fn(Process $process) => $process->getUuid());

        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        foreach ($channels as $channel) {
            $uuid = $uuids->shuffle()->shift();
            $commandQueue->push(sprintf('%s-input', $uuid), CommandQueue::COMMAND_TMI_JOIN, [
                'channel' => $channel,
            ]);
            $this->lock->release($this->getKey($channel));
        }
    }

    /**
     * This will be called if we need to join a channel.
     *
     * @param array $channels
     * @param array $staleIds
     * @param CommandQueue $commandQueue
     * @return array
     * @throws LimiterTimeoutException
     */
    private function joinOrQueue(array $channels, array $staleIds, CommandQueue $commandQueue): array
    {
        $result = ['rejected' => [], 'resolved' => [], 'ignored' => []];

        $processes = Models\SupervisorProcess::query()
            ->whereTime('last_ping_at', '>', now()->subSeconds(10))
            ->whereIn('state', [Models\SupervisorProcess::STATE_CONNECTED])
            ->whereNotIn('id', $staleIds)
            ->get()
            ->map(function (Models\SupervisorProcess $process) {
                return (object)[
                    'id' => $process->getKey(),
                    'channels' => $process->channels,
                    'channel_sum' => count($process->channels),
                ];
            })->sortBy('channel_sum');

        if ($processes->isEmpty()) {
            $result = $this->reject($result, $channels, $staleIds, $commandQueue);

            return $this->result($result);
        }

        $take = min(
            config('tmi-cluster.throttle.join.take', 100),
            config('tmi-cluster.throttle.join.allow', 2000)
        );

        foreach (array_chunk($channels, $take) as $chunk) {
            /** @var Lock $lock */
            $lock = app(Lock::class);

            /** @noinspection PhpUnhandledExceptionInspection */
            $result = $lock->throttle('throttle:join-handler')
                ->block(config('tmi-cluster.throttle.join.block', 0))
                ->allow(config('tmi-cluster.throttle.join.allow', 2000))
                ->every(config('tmi-cluster.throttle.join.every', 10))
                ->take($take)
                ->then(
                    fn() => $this->resolve($result, $chunk, $staleIds, $commandQueue, $processes),
                    fn() => $this->reject($result, $chunk, $staleIds, $commandQueue)
                );
        }

        return $this->result($result);
    }

    private function reject(array $result, array $channels, array $staleIds, CommandQueue $commandQueue): array
    {
        // we didn't get any server, that is ready to join our channels
        // so we move them to our lost and found channel queue
        $commandQueue->push(CommandQueue::NAME_JOIN_HANDLER, CommandQueue::COMMAND_TMI_JOIN, [
            'channels' => $channels,
            'staleIds' => $staleIds,
        ]);

        $result['rejected'][] = $channels;

        return $result;
    }

    private function resolve(array $result, array $channels, array $staleIds, CommandQueue $commandQueue, Collection $processes): array
    {
        foreach ($channels as $channel) {
            if ($process = $this->getProcess($processes, $channel)) {
                if($process instanceof stdClass) {
                    $result['ignored'][$channel] = $this->increment($process, $channel);
                } else {
                    $result['ignored'][$channel] = $process;
                }
                continue;
            }

            $nextProcess = $processes->sortBy('channel_sum')->shift();
            $this->increment($nextProcess, $channel);
            $processes->push($nextProcess);

            // acquire lock to prevent double join
            $this->lock->connection()->set($this->getKey($channel), $nextProcess->id, 'EX', 60, 'NX');

            $commandQueue->push($nextProcess->id, CommandQueue::COMMAND_TMI_JOIN, [
                'channel' => $channel,
                'staleIds' => $staleIds,
            ]);

            $result['resolved'][$channel] = $nextProcess->id;
        }

        return $result;
    }

    private function result(array $result): array
    {
        $result['rejected'] = array_merge(...$result['rejected']);

        return $result;
    }

    private function getKey(string $channel)
    {
        return sprintf('channel-manager:join-%s', $channel);
    }

    private function getProcess(Collection $processes, string $channel)
    {
        if ($id = $this->lock->connection()->get($this->getKey($channel))) {
            if ($process = $processes->where('id', '===', $id)->first()) {
                return $process;
            }

            return $id;
        }

        return $processes->whereIn('channels', [$channel])->first();
    }

    private function increment(stdClass $process, $channel): string
    {
        $process->channel_sum += 1;
        $process->channels[] = $channel;

        return $process->id;
    }
}