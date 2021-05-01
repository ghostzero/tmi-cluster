<?php


namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Contracts\Invitable;

class TwitchLogin implements Invitable
{
    private string $login;

    public function __construct(string $login)
    {
        $this->login = $login;
    }

    public function getTwitchLogin(): ?string
    {
        return $this->login;
    }
}