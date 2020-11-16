<?php

namespace GhostZero\TmiCluster;

use romanzipp\Twitch\Twitch;

class AutoCleanup
{
    private Lock $lock;

    public function __construct()
    {
        $this->lock = app(Lock::class);
    }

    public function cleanup(TmiClusterClient $client): void
    {
        if (!$this->shouldCleanup()) {
            return;
        }

        $diff = static::diff($client->getClient()->getChannels());

        foreach ($diff['part'] as $channel => $login) {
            if ($channel === null || $login == null) {
                continue;
            }

            if ($this->lock->exists($this->getKey($client, $channel))) {
                $client->log(sprintf('Auto Cleanup: Part is locked for %s', $channel));
                return;
            }

            $client->log(sprintf('Auto Cleanup: Part %s', $channel));
            $client->getClient()->part($channel);
        }

        $client->log(sprintf('Cleanup complete! Part: %s', count($diff['part'])));
    }

    public function acquireLock(TmiClusterClient $client, string $channel): void
    {
        $this->lock->get($this->getKey($client, $channel), 300);
    }

    private function shouldCleanup()
    {
        return config('tmi-cluster.auto_cleanup.enabled');
    }

    public static function diff(array $collection): array
    {
        $connectedChannels = array_map(static fn($data) => ltrim($data, '#'), $collection);
        $onlineChannels = self::getOnlineChannels($connectedChannels, app(Twitch::class));

        return [
            'connected' => $connectedChannels,
            'join' => array_diff($onlineChannels, $connectedChannels),
            'part' => array_diff($connectedChannels, $onlineChannels),
        ];
    }

    private static function getOnlineChannels(array $connectedChannels, Twitch $twitch): array
    {
        return array_map(static function (array $collection) use ($twitch) {
            $result = $twitch->getStreams(['user_login' => $collection]);

            abort_unless($result->success(), 503, $result->error());

            return array_map(static fn($x) => strtolower($x->user_name), $result->data());
        }, array_chunk($connectedChannels, 100));
    }

    private function getKey(TmiClusterClient $client, string $channel): string
    {
        return sprintf('auto-cleanup:part-%s-%s', $client->getUuid(), $channel);
    }
}
