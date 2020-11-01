<?php

namespace GhostZero\TmiCluster\Events;

use GhostZero\Tmi\Client;
use GhostZero\Tmi\Tags;
use GhostZero\TmiCluster\Contracts\Signed;
use Illuminate\Support\Str;

class IrcCommandEvent implements Signed
{
    public Client $client;
    public string $event;
    public array $payload;

    public function __construct(Client $client, string $string, array $payload)
    {
        $this->client = $client;
        $this->event = $string;
        $this->payload = $payload;
    }

    public function signature(): string
    {
        foreach ($this->payload as $payload) {
            if ($payload instanceof Tags) {
                return $payload['id'];
            }
        }
        return Str::uuid();
    }
}
