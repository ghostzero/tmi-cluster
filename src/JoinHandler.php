<?php

namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\Process\Process;
use Illuminate\Database\Eloquent\Collection;

/**
 * This class handles the channel reconnection.
 *
 * Class JoinHandler
 * @package GhostZero\TmiCluster
 */
class JoinHandler
{
    public function join(Supervisor $supervisor, array $channels): void
    {
        $uuids = $supervisor->processes()->map(fn(Process $process) => $process->getUuid());

        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        foreach ($channels as $channel) {
            $uuid = $uuids->shuffle()->shift();
            $commandQueue->push(sprintf('%s-input', $uuid), CommandQueue::COMMAND_TMI_JOIN, [
                'channel' => $channel,
            ]);
        }

        // todo improve irc join
        // if some process space available, join
        // if supervisor & processes full
        //   if spin up is available
        //      spin up process, re-queue channel
        //   else
        //      force join
    }

    /**
     * Method to rejoin gabage-collected and lost channels.
     *
     * Given channels & stale ids are from the gabage collector.
     *
     * @param array $staleIds
     * @param array $channels
     */
    public function joinLostChannels(array $channels = [], array $staleIds = [])
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commands = $commandQueue->pending(CommandQueue::NAME_JOIN_HANDLER);

        foreach ($commands as $command) {
            if ($command->command !== CommandQueue::COMMAND_TMI_JOIN) {
                continue;
            }

            $staleIds = array_merge($staleIds, $command->options->staleIds);
            $channels = array_merge($channels, $command->options->channels);
        }

        $this->joinOrQueue(array_unique($channels), array_unique($staleIds), $commandQueue);
    }

    private function joinOrQueue(array $channels, array $staleIds, CommandQueue $commandQueue): array
    {
        $result = ['queued' => [], 'migrated' => []];

        $supervisors = Models\Supervisor::query()
            ->whereTime('last_ping_at', '>', now()->subSeconds(10))
            ->whereNotIn('id', $staleIds)
            ->get()->map(function (Models\Supervisor $supervisor) {
                return [
                    'name' => $supervisor->getKey(),
                    'channels' => $supervisor->processes->sum(function (Models\SupervisorProcess $process) {
                        return count($process->channels);
                    }),
                ];
            })->sortBy('channels');

        if ($supervisors->isEmpty()) {
            $this->reject($result, $channels, $staleIds, $commandQueue);

            return $this->result($result);
        }

        $take = config('tmi-cluster.throttle.join.take', 100);

        foreach (array_chunk($channels, $take) as $channels) {
            /** @var Lock $lock */
            $lock = app(Lock::class);

            /** @noinspection PhpUnhandledExceptionInspection */
            $lock->throttle('throttle:join-handler')
                ->block(config('tmi-cluster.throttle.join.block', 0))
                ->allow(config('tmi-cluster.throttle.join.allow', 2000))
                ->every(config('tmi-cluster.throttle.join.every', 10))
                ->take($take)
                ->then(
                    fn() => $this->resolve($result, $channels, $staleIds, $commandQueue, $supervisors),
                    fn() => $this->reject($result, $channels, $staleIds, $commandQueue)
                );
        }

        return $this->result($result);
    }

    private function reject(array &$result, array $channels, array $staleIds, CommandQueue $commandQueue): void
    {
        // we didn't get any server, that is ready to join our channels
        // so we move them to our lost and found channel queue
        $commandQueue->push(CommandQueue::NAME_JOIN_HANDLER, CommandQueue::COMMAND_TMI_JOIN, [
            'channels' => $channels,
            'staleIds' => $staleIds,
        ]);

        $result['queued'][] = $channels;
    }

    private function resolve(array &$result, array $channels, array $staleIds, CommandQueue $commandQueue, Collection $supervisors): void
    {
        $migrated = [];
        foreach ($channels as $channel) {
            $nextSupervisor = $supervisors->sortBy('channels')->shift();
            $nextSupervisor['channels'] += 1;
            $supervisors->push($nextSupervisor);

            $commandQueue->push($nextSupervisor['name'], CommandQueue::COMMAND_TMI_JOIN, [
                'channel' => $channel,
                'staleIds' => $staleIds,
            ]);

            $migrated[$channel] = $nextSupervisor['name'];
        }

        $result['migrated'][] = $migrated;
    }

    private function result(array $result): array
    {
        $result['queued'] = array_merge(...$result['queued']);
        $result['migrated'] = array_merge(...$result['migrated']);

        return $result;
    }
}
