<?php

namespace GhostZero\TmiCluster\Repositories;

use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Contracts\Invitable;
use GhostZero\TmiCluster\Models\Channel;

class DatabaseChannelManager implements ChannelManager
{
    /**
     * @inheritDoc
     */
    public function authorize(Invitable $channel, array $options): void
    {
        Channel::query()->updateOrCreate(['id' => $channel->getTwitchLogin()], array_merge([
            'revoked' => false,
        ], $options));
    }

    /**
     * @inheritDoc
     */
    public function revokeAuthorization(Invitable $channel): void
    {
        Channel::query()->whereKey($channel->getTwitchLogin())->update([
            'revoked' => true,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function authorized(array $channels): array
    {
        return Channel::query()
            ->whereIn('id', $channels)
            ->where(['revoked' => false])
            ->pluck('id')
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function acknowledged(array $channels): void
    {
        Channel::query()->whereIn('id', $channels)->update([
            'acknowledged_at' => now(),
        ]);
    }
}