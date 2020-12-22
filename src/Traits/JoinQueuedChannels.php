<?php

namespace GhostZero\TmiCluster\Traits;

use GhostZero\Tmi\Channel;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Support\Arr;

trait JoinQueuedChannels
{
    /**
     * @inheritdoc
     */
    public function join(array $channels, array $staleIds = []): void
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commandQueue->push(CommandQueue::NAME_JOIN_HANDLER, CommandQueue::COMMAND_TMI_JOIN, [
            'channels' => array_map(static fn($channel) => Channel::sanitize($channel), $channels),
            'staleIds' => $staleIds,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function joinNow(array $channels, array $staleIds = []): array
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commands = $commandQueue->pending(CommandQueue::NAME_JOIN_HANDLER);

        [$staleIds, $channels] = Arr::unique($commands, $staleIds, $channels);

        return $this->joinOrQueue($channels, $staleIds, $commandQueue);
    }
}