<?php

namespace GhostZero\TmiCluster\Listeners;

use GhostZero\TmiCluster\Lock;
use GhostZero\TmiCluster\TmiCluster;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendNotification
{
    public function handle($event): void
    {
        $notification = $event->toNotification();

        if ($notification === null) {
            return;
        }

        /** @var Lock $lock */
        $lock = app(Lock::class);

        if (!$lock->get('notification:' . $notification->signature(), 300)) {
            $event->skiped = true;
            return;
        }

        try {
            Notification::route('slack', TmiCluster::$slackWebhookUrl)
                ->route('nexmo', TmiCluster::$smsNumber)
                ->route('mail', TmiCluster::$email)
                ->notify($notification);

            $event->sent = true;
        } catch (Throwable $exception) {
            $event->sent = false;
        }
    }
}
