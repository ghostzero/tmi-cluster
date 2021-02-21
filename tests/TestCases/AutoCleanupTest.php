<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\AutoCleanup;
use GhostZero\TmiCluster\Tests\TestCase;
use GhostZero\TmiCluster\Twitch\Twitch;

class AutoCleanupTest extends TestCase
{
    public function testAutoCleanup(): void
    {
        if (!Twitch::isApiAvailable()) {
            self::markTestSkipped('The dependency romanzipp/laravel-twitch is not installed.');
        }

        $diff = AutoCleanup::diff(app(Twitch::class), ['ghostzero', 'own3d_music']);

        self::assertEquals([
            'connected' => ['ghostzero', 'own3d_music'],
            'part' => ['ghostzero'],
        ], $diff);
    }
}