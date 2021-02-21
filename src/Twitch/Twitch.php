<?php

namespace GhostZero\TmiCluster\Twitch;

use romanzipp\Twitch\Twitch as TwitchApi;

class Twitch
{
    protected $clientId = null;
    protected $clientSecret = null;

    public function __construct()
    {
        if ($clientId = config('tmi-cluster.helix.client_id')) {
            $this->setClientId($clientId);
        }

        if ($clientSecret = config('tmi-cluster.helix.client_secret')) {
            $this->setClientSecret($clientSecret);
        }
    }

    public static function isApiAvailable(): bool
    {
        return class_exists(TwitchApi::class);
    }

    private function setClientId(string $clientId)
    {
        $this->clientId = $clientId;
    }

    private function setClientSecret(string $clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    public function getStreams(array $parameters): Result
    {
        $result = $this->getTwitchApi()->getStreams($parameters);
        return new Result($result->success(), $result->data(), $result->error());
    }

    private function getTwitchApi(): TwitchApi
    {
        $twitchApi = new TwitchApi();

        if ($this->clientId) {
            $twitchApi->setClientId($this->clientId);
        }

        if ($this->clientSecret) {
            $twitchApi->setClientSecret($this->clientSecret);
        }

        return $twitchApi;
    }
}