<?php

namespace GhostZero\TmiCluster;

/**
 * This class handles the channel reconnection.
 *
 * Class JoinHandler
 * @package GhostZero\TmiCluster
 */
class JoinHandler
{
    public function join(Supervisor $supervisor, string $channel): void
    {
        // todo implement irc join
        // if some process space available, join
        // if supervisor & processes full
        //   if spin up is available
        //      spin up process, re-queue channel
        //   else
        //      force join
    }
}
