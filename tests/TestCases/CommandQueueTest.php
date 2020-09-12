<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Tests\TestCase;

class CommandQueueTest extends TestCase
{
    public function testCommandQueue(): void
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commandQueue->push('test', 'command');

        self::assertCount(0, $commandQueue->pending('test2'));
        self::assertNotEmpty($data = $commandQueue->pending('test'));
        self::assertIsArray($data);
        self::assertEquals('command', $data[0]->command);
        self::assertIsArray($data[0]->options);
        self::assertCount(0, $commandQueue->pending('test'));
    }
}
