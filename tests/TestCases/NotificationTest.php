<?php


namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Events\ProcessScaled;
use GhostZero\TmiCluster\Listeners\SendNotification;
use GhostZero\TmiCluster\Tests\TestCase;
use Illuminate\Support\Str;

class NotificationTest extends TestCase
{
    public function testWebhookNotification(): void
    {
        $event = new ProcessScaled(
            'Message',
            Str::uuid(),
            'Event',
            'Cause'
        );

        (new SendNotification())->handle($event);

        self::assertNotTrue($event->skipped);
        self::assertTrue($event->sent);
    }
}
