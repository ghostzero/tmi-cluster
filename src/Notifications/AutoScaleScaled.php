<?php

namespace GhostZero\TmiCluster\Notifications;

use GhostZero\TmiCluster\TmiCluster;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class AutoScaleScaled extends Notification
{

    private string $message;
    private string $description;
    private string $event;
    private string $cause;

    public function __construct(string $message, string $description, string $event, string $cause)
    {
        $this->message = $message;
        $this->description = $description;
        $this->event = $event;
        $this->cause = $cause;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject('TMI Cluster: AutoScale Notification')
            ->greeting('Oh no! Something needs your attention.')
            ->line(sprintf('Message: %s', $this->message))
            ->line(sprintf('Description: %s', $this->message))
            ->line(sprintf('Event: %s', $this->message))
            ->line(sprintf('Cause: %s', $this->message));
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
     * @return SlackMessage
     */
    public function toSlack($notifiable)
    {
        return (new SlackMessage)
            ->from('TMI Cluster')
            ->to(TmiCluster::$slackChannel)
            //->image('... add tmi cluster image url')
            ->info()
            ->attachment(function ($attachment) {
                $attachment
                    ->title('TMI Cluster: AutoScale Notification')
                    ->fields([
                        'Message' => $this->message,
                        'Description' => $this->description,
                        'Event' => $this->event,
                        'Cause' => $this->cause,
                    ]);
            });
    }

    /**
     * Get the Nexmo / SMS representation of the notification.
     *
     * @param mixed $notifiable
     * @return NexmoMessage
     */
    public function toNexmo($notifiable)
    {
        return (new NexmoMessage)->content(sprintf(
            '[TMI Cluster] %s. %s.', $this->message, $this->description
        ));
    }

    public function signature(): string
    {
        return sha1($this->message . $this->description . $this->event . $this->cause);
    }
}
