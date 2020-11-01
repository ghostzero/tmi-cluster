<?php

namespace GhostZero\TmiCluster\Events;

use GhostZero\Tmi\Channel;
use GhostZero\Tmi\Client;
use GhostZero\Tmi\Tags;
use GhostZero\TmiCluster\Contracts\Signed;

class IrcMessageEvent implements Signed
{
    public Client $client;
    public Channel $channel;
    public Tags $tags;
    public string $user;
    public string $message;
    public bool $self;

    public function __construct(Client $client, Channel $channel, Tags $tags, string $user, string $message, bool $self)
    {
        $this->client = $client;
        $this->channel = $channel;
        $this->tags = $tags;
        $this->user = $user;
        $this->message = $message;
        $this->self = $self;
    }

    public function signature(): string
    {
        return $this->tags['id'];
    }
}
