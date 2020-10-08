<?php

namespace GhostZero\TmiCluster\Events;

class IrcCommandEvent
{
    public string $string;
    public array $payload;

    public function __construct(string $string, array $payload)
    {
        $this->string = $string;
        $this->payload = $payload;
    }
}
