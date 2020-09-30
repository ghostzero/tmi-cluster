<?php

namespace GhostZero\TmiCluster\Events;

use GhostZero\Tmi\Channel;
use GhostZero\Tmi\Tags;

class MessageEvent
{
    public Channel $channel;
    public Tags $tags;
    public string $user;
    public string $message;

    public function __construct(Channel $channel, Tags $tags, string $user, string $message)
    {
        $this->channel = $channel;
        $this->tags = $tags;
        $this->user = $user;
        $this->message = $message;
    }
}
