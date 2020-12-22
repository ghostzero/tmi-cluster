<?php

namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\Process\Process;
use GhostZero\TmiCluster\Support\Arr;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
     * Method to rejoin garbage-collected and lost channels.
     *
     * Given channels & stale ids are from the garbage collector.
     *
     * @param array $staleIds
     * @param array $channels
     * @return array
     */
    public function joinLostChannels(array $channels = [], array $staleIds = []): array
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commands = $commandQueue->pending(CommandQueue::NAME_JOIN_HANDLER);

        [$staleIds, $channels] = Arr::unique($commands, $staleIds, $channels);

        return $this->joinOrQueue($channels, $staleIds, $commandQueue);
    }
}
