<?php

namespace GhostZero\TmiCluster\Repositories;

use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Contracts\Invitable;

class DummyChannelManager implements ChannelManager
{
    /**
     * @inheritDoc
     */
    public function authorize(Invitable $channel, array $options): void
    {
        // no implementation needed
    }

    /**
     * @inheritDoc
     */
    public function revokeAuthorization(Invitable $channel): void
    {
        // no implementation needed
    }

    /**
     * @inheritDoc
     */
    public function authorized(array $channels): array
    {
        return $channels;
    }

    /**
     * @inheritDoc
     */
    public function acknowledged(array $channels): void
    {
        // no implementation needed
    }
}