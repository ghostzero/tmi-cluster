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
            $this->fake(CommandQueue::NAME_JOIN_HANDLER, CommandQueue::COMMAND_TMI_JOIN, [
                'channels' => ['channel-b'],
                'staleIds' => ['stale-b'],
            ]),
            $this->fake(CommandQueue::NAME_JOIN_HANDLER, CommandQueue::COMMAND_TMI_JOIN, [
                'channels' => ['channel-b'],
            ]),
            $this->fake(CommandQueue::NAME_JOIN_HANDLER, CommandQueue::COMMAND_TMI_JOIN, [
                'channels' => ['channel-c'],
                'staleIds' => ['stale-c'],
            ]),
        ], ['stale-a'], ['channel-a']);

        self::assertEquals([
            ['stale-a', 'stale-b', 'stale-c'],
            ['channel-a', 'channel-b', 3 => 'channel-c'], // index 2 got removed due unique
        ], $result);
    }

    /**
     * @param string $name
     * @param string $command
     * @param array $options
     * @return mixed
     * @throws JsonException
     */
    private function fake(string $name, string $command, array $options = [])
    {
        // encode json string
        $encoded = json_encode([
            'command' => $command,
            'options' => $options,
            'time' => microtime(),
        ], JSON_THROW_ON_ERROR);

        // decode json string
        return json_decode($encoded, false, 512, JSON_THROW_ON_ERROR);
    }
}