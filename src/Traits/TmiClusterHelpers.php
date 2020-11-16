<?php

namespace GhostZero\TmiCluster\Traits;

use GhostZero\TmiCluster\Contracts\CommandQueue;

trait TmiClusterHelpers
{
    public static function sendMessage(string $channel, $message): void
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $channel = self::channelName($channel);
        $commandQueue->push(CommandQueue::NAME_ANY_SUPERVISOR, CommandQueue::COMMAND_TMI_WRITE, [
            'raw_command' => "PRIVMSG {$channel} :{$message}",
        ]);
    }

    /**
     * @param array $channels a list of channels that needs to be joined
     * @param array $staleIds a list of supervisors or processes ids, that needs to be avoid
     */
    public static function joinNextServer(array $channels, array $staleIds = []): void
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commandQueue->push(CommandQueue::NAME_JOIN_HANDLER, CommandQueue::COMMAND_TMI_JOIN, [
            'channels' => array_map(static fn($channel) => self::channelName($channel), $channels),
            'staleIds' => $staleIds,
        ]);
    }

    private static function channelName(string $channel): string
    {
        if ($channel[0] !== '#') {
            $channel = "#$channel";
        }

        return $channel;
    }
}
