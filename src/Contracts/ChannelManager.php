<?php

namespace GhostZero\TmiCluster\Contracts;

interface ChannelManager
{
    /**
     * Allows the ChannelDistributor to join an Invitable when join is called.
     *
     * @param Invitable $channel
     * @param array $options
     */
    public function authorize(Invitable $channel, array $options): void;

    /**
     * Revokes a previous given authorization.
     *
     * @param Invitable $channel
     */
    public function revokeAuthorization(Invitable $channel): void;

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
     */
    public function acknowledged(array $channels): void;
}