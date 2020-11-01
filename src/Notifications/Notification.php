<?php

namespace GhostZero\TmiCluster\Notifications;

use GhostZero\TmiCluster\TmiCluster;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;

abstract class Notification extends BaseNotification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return array_filter([
            TmiCluster::$slackWebhookUrl ? 'slack' : null,
            TmiCluster::$smsNumber ? 'nexmo' : null,
            TmiCluster::$email ? 'mail' : null,
        ]);
    }

    abstract public function signature(): string;
}
