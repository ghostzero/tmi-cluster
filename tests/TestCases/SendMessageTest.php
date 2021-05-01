<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Repositories\DatabaseChannelManager;
use GhostZero\TmiCluster\Tests\TestCase;
use GhostZero\TmiCluster\TmiCluster;
use GhostZero\TmiCluster\TwitchLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SendMessageTest extends TestCase
{
    use RefreshDatabase;

    public function testCannotSendMessageIfRestrictedAndUnauthorized(): void
    {
        $this->shouldRestrictMessages();

        TmiCluster::sendMessage('ghostzero', 'Hello World!');

        self::assertMessageNotSent();
    }

    public function testCanSendMessageIfRestrictedAndAuthorized(): void
    {
        $this->shouldRestrictMessages();

        $this->useChannelManager(DatabaseChannelManager::class)
            ->authorize(new TwitchLogin('ghostzero'));

        TmiCluster::sendMessage('ghostzero', 'Hello World!');

        self::assertMessageSent('ghostzero', 'Hello World!');
    }

    public function testCanSendMessageIfUnrestricted(): void
    {
        $this->shouldRestrictMessages(false);

        TmiCluster::sendMessage('ghostzero', 'Hello World!');

        self::assertMessageSent('ghostzero', 'Hello World!');
    }

    private static function assertMessageNotSent()
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commands = $commandQueue->pending(CommandQueue::NAME_ANY_SUPERVISOR);

        self::assertEmpty($commands);
    }

    private static function assertMessageSent(string $channel, string $message)
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commands = $commandQueue->pending(CommandQueue::NAME_ANY_SUPERVISOR);

        self::assertNotEmpty($commands);

        self::assertEquals(
            sprintf('PRIVMSG #%s :%s', $channel, $message),
            $commands[0]->options->raw_command
        );
    }

    private function shouldRestrictMessages(bool $restrict = true): void
    {
        config()->set('tmi-cluster.channel_manager.channel.restrict_messages', $restrict);
    }
}