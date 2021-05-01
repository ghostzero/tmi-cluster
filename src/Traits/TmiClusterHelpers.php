<?php

namespace GhostZero\TmiCluster\Traits;

use GhostZero\Tmi\Channel;
use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Contracts\CommandQueue;

trait TmiClusterHelpers
{
    public static function sendMessage(string $channel, $message): void
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $channel = Channel::sanitize($channel);

        if (config('tmi-cluster.channel_manager.channel.restrict_messages')) {
            /** @var ChannelManager $channelManager */
            $channelManager = app(ChannelManager::class);

            if (!$channelManager->authorized([$channel])) {
                return;
            }
        }

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
        app(ChannelDistributor::class)->join($channels, $staleIds);
    }
}
