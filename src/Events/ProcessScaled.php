<?php

namespace GhostZero\TmiCluster\Events;

use GhostZero\TmiCluster\Notifications\AutoScaleScaled as AutoScaleScaledNotification;

class ProcessScaled
{
    public string $message;
    public string $description;
    public string $event;
    public string $cause;
    public bool $sent = false;
    public bool $skipped = false;

    public function __construct(string $message, string $description, string $event, string $cause)
    {
        $this->message = $message;
        $this->description = $description;
        $this->event = $event;
        $this->cause = $cause;
    }

    public function toNotification(): AutoScaleScaledNotification
    {
        return new AutoScaleScaledNotification(
            $this->message, $this->description, $this->event, $this->cause
        );
    }
}
