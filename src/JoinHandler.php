<?php

namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Contracts\CommandQueue;
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

        $commands = $commandQueue->pending(CommandQueue::NAME_LOST_AND_FOUND);

        foreach ($commands as $command) {
            if ($command->command !== CommandQueue::COMMAND_TMI_JOIN) {
                continue;
            }

            $staleIds = array_merge($staleIds, $command->options->staleIds);
            $channels = array_merge($channels, $command->options->channels);
        }

        TmiCluster::joinNextServer(array_unique($channels), array_unique($staleIds));
    }
}
