<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Support\Arr;
use GhostZero\TmiCluster\Tests\TestCase;
use JsonException;

class ArrayTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testJoinServer(): void
    {
        $result = Arr::unique([
            $this->fake([
                'channels' => ['channel-b'],
                'staleIds' => ['stale-b'],
                'acknowledge' => true,
            ]),
            $this->fake([
                'channels' => ['channel-b'],
                'acknowledge' => true,
            ]),
            $this->fake([
                'channels' => ['channel-c'],
                'staleIds' => ['stale-c'],
                'acknowledge' => false,
            ]),
        ], ['stale-a'], ['channel-a'], true);

        self::assertEquals([
            ['stale-a', 'stale-b', 'stale-c'],
            ['channel-a', 'channel-b', 3 => 'channel-c'], // index 2 got removed due unique
            ['channel-a', 'channel-b']
        ], $result);
    }

    /**
     * @param array $options
     * @return mixed
     * @throws JsonException
     */
    private function fake(array $options = [])
    {
        // encode json string
        $encoded = json_encode([
            'command' => CommandQueue::COMMAND_TMI_JOIN,
            'options' => $options,
            'time' => microtime(),
        ], JSON_THROW_ON_ERROR);

        // decode json string
        return json_decode($encoded, false, 512, JSON_THROW_ON_ERROR);
    }
}