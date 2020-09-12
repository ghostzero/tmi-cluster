<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\Tmi\Client;
use GhostZero\Tmi\ClientOptions;
use GhostZero\TmiCluster\Tests\TestCase;

class TmiClientTest extends TestCase
{
    public function testIrcClient(): void
    {
        $client = new Client(new ClientOptions(config('tmi-cluster.tmi')));

        $client->on('names', function (string $channel) use ($client) {
            $this->assertEquals('#ghostzero', $channel);
            $client->close();
        });

        $client->connect();
    }
}
