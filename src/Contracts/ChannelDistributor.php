<?php

namespace GhostZero\TmiCluster\Contracts;

interface ChannelDistributor
{
    /**
     * Join some given channels into the cluster. Dispatches a job to join the channel later.
     *
     * @param array $channels a list of channels that needs to be joined
     * @param array $staleIds a list of supervisors or processes ids, that needs to be avoid
     */
    public function join(array $channels, array $staleIds = []): void;

    /**
     * Join some given channels directly into the cluster.
     *
     * @param array $channels a list of channels that needs to be joined
     * @param array $staleIds a list of supervisors or processes ids, that needs to be avoid
     * @return array
     */
    public function joinNow(array $channels, array $staleIds = []): array;
}