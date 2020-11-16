<?php

namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\Process\Process;

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
            // we didn't get any server, that is ready to join our channels
            // so we move them to our lost and found channel queue
            $commandQueue->push(CommandQueue::NAME_JOIN_HANDLER, CommandQueue::COMMAND_TMI_JOIN, [
                'channels' => $channels,
                'staleIds' => $staleIds,
            ]);

            $result['queued'] = $channels;

            return $result;
        }

        foreach ($channels as $channel) {
            $nextSupervisor = $supervisors->sortBy('channels')->shift();
            $nextSupervisor['channels'] += 1;
            $supervisors->push($nextSupervisor);

            $commandQueue->push($nextSupervisor['name'], CommandQueue::COMMAND_TMI_JOIN, [
                'channel' => $channel,
                'staleIds' => $staleIds,
            ]);

            $result['migrated'][$channel] = $nextSupervisor['name'];
        }

        return $result;
    }
}
