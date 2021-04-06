<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Repositories\DatabaseChannelManager;
use GhostZero\TmiCluster\Tests\TestCase;
use GhostZero\TmiCluster\Tests\TestUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseChannelManagerTest extends TestCase
{
    use RefreshDatabase;

    public function testChannelIsAuthorized(): void
    {
        $user = new TestUser('ghostzero');

        $this->getChannelManager()->authorize($user, [
            'reconnect' => true,
        ]);

        $authorized = $this->getChannelManager()->authorized([
            'ghostzero',
            'tmi_inspector',
        ]);

        self::assertContains('#ghostzero', $authorized);
        self::assertNotContains('#tmi_inspector', $authorized);
    }

    public function testChannelIsNotAuthorizedAfterRevoke(): void
    {
        $user = new TestUser('ghostzero');

        $this->getChannelManager()->authorize($user, [
            'reconnect' => true,
        ]);

        $authorized = $this->getChannelManager()->authorized(['ghostzero']);

        self::assertContains('#ghostzero', $authorized);

        $this->getChannelManager()->revokeAuthorization($user);

        $authorized = $this->getChannelManager()->authorized(['ghostzero']);

        self::assertNotContains('#ghostzero', $authorized);
    }

    private function getChannelManager(): ChannelManager
    {
        return app(DatabaseChannelManager::class);
    }
}