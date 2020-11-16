<?php

namespace GhostZero\TmiCluster;

use romanzipp\Twitch\Twitch;
use Throwable;

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

        try {
            $diff = static::diff($client->getClient()->getChannels());
        } catch (Throwable $exception) {
            $client->log(sprintf('Auto Cleanup: Failed to diff. Reason: ' . $exception->getMessage()));
            return;
        }

        $locked = 0;
        foreach ($diff['part'] as $channel => $login) {
            if ($channel === null || $login == null) {
                continue;
            }

            if ($this->lock->exists($this->getKey($client, $channel))) {
                $locked++;
                continue;
            }

            $client->getClient()->part($channel);
        }

        $client->log(sprintf('Cleanup complete! Needs Part: %s, Locked: %s', count($diff['part']), $locked));
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
            'part' => array_diff($connectedChannels, $onlineChannels),
        ];
    }

    private static function getOnlineChannels(array $connectedChannels, Twitch $twitch): array
    {
        $resolved = array_map(static function (array $collection) use ($twitch) {
            $result = $twitch->getStreams(['user_login' => $collection]);

            abort_unless($result->success(), 503, $result->error());

            return array_map(static fn($x) => dd($x), $result->data());
        }, array_chunk($connectedChannels, 100));

        return array_merge(...$resolved);
    }

    private function getKey(TmiClusterClient $client, string $channel): string
    {
        return sprintf('auto-cleanup:part-%s-%s', $client->getUuid(), $channel);
    }
}
