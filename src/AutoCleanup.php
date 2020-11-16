<?php

namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Twitch\Twitch;
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

        /** @var Twitch $twitch */
        $twitch = app(Twitch::class);

        try {
            $diff = static::diff($twitch, $client->getClient()->getChannels());
        } catch (Throwable $exception) {
            $client->log(sprintf('Auto Cleanup: Failed to diff. Reason: ' . $exception->getMessage()));
            return;
        }

        $locked = 0;
        foreach ($diff['part'] as $channel => $login) {
            if (empty($channel) || empty($login)) {
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

    public static function diff(Twitch $twitch, array $connectedChannels): array
    {
        $connectedChannels = array_map(static fn($data) => ltrim($data, '#'), $connectedChannels);
        $onlineChannels = self::getOnlineChannels($twitch, $connectedChannels);

        return [
            'connected' => $connectedChannels,
            'part' => array_udiff($connectedChannels, $onlineChannels, 'strcasecmp'),
        ];
    }

    private static function getOnlineChannels(Twitch $twitch, array $connectedChannels): array
    {
        $resolved = array_map(static function (array $collection) use ($twitch) {
            $result = $twitch->getStreams(['user_login' => $collection]);

            abort_unless($result->success(), 503, $result->error());

            return array_map(static fn($x) => $x->user_name, $result->data());
        }, array_chunk($connectedChannels, 100));

        return array_merge(...$resolved);
    }

    private function getKey(TmiClusterClient $client, string $channel): string
    {
        return sprintf('auto-cleanup:part-%s-%s', $client->getUuid(), $channel);
    }
}
