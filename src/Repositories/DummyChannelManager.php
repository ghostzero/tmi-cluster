<?php

namespace GhostZero\TmiCluster\Repositories;

use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Contracts\Invitable;

class DummyChannelManager implements ChannelManager
{
    /**
     * @inheritDoc
     */
    public function authorize(Invitable $channel, array $options = []): self
    {
        // no implementation needed

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function revokeAuthorization(Invitable $channel): self
    {
        // no implementation needed

        return $this;
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
    public function acknowledged(array $channels): self
    {
        // no implementation needed

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function stale(array $channels): array
    {
        return [];
    }
}