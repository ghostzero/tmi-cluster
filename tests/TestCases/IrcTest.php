<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\Tmi\Client;
use GhostZero\Tmi\ClientOptions;
use GhostZero\TmiCluster\Tests\TestCase;

class IrcTest extends TestCase
{
    public function testIrcClient(): void
    {
        /** @var array $options */
        $options = config('tmi-cluster.tmi');

        $client = new Client(new ClientOptions($options));

        $client->on('names', function (string $channel) use($client) {
            $this->assertEquals('#ghostzero', $channel);
            $client->close();
        });

        $client->connect();
    }
}
