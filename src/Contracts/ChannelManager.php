<?php

namespace GhostZero\TmiCluster\Contracts;

interface ChannelManager
{
    /**
     * Allows the ChannelDistributor to join an Invitable when join is called.
     *
     * @param Invitable $channel
     * @param array $options
     * @return ChannelManager
     */
    public function authorize(Invitable $channel, array $options = []): self;

    /**
     * Revokes a previous given authorization.
     *
     * @param Invitable $channel
     * @return ChannelManager
     */
    public function revokeAuthorization(Invitable $channel): self;

    /**
     * Returns all channels that has given a authorization.
     *
     * @param string[] $channels
     * @return string[]
     */
    public function authorized(array $channels): array;

    /**
     * Marks all given channels as active. This will be used for the auto-cleanup.
     *
     * @param string[] $channels
     * @return ChannelManager
     */
    public function acknowledged(array $channels): self;

    /**
     * Returns all given channels that has been marked as stale.
     *
     * @param string[] $channels
     * @return array
     */
    public function stale(array $channels): array;
}