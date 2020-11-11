<?php

namespace GhostZero\TmiCluster;

use Illuminate\Support\Collection;
use romanzipp\Twitch\Twitch;

class AutoCleanup
{
    public function cleanup(TmiClusterClient $client)
    {
        if (!$this->shouldCleanup()) {
            return;
        }

        $diff = $this->diff(new Collection($client->getClient()->getChannels()));

        foreach ($diff['join'] as $channel) {
            $client->log(sprintf('Auto Cleanup: Join %s', $channel));
            $client->getClient()->join($channel);
        }

        foreach ($diff['part'] as $channel) {
            $client->log(sprintf('Auto Cleanup: Part %s', $channel));
            $client->getClient()->part($channel);
        }

        $client->log(sprintf('Cleanup complete! Join: %s, Part: %s', count($diff['join']), count($diff['part'])));
    }

    private function shouldCleanup()
    {
        return config('tmi-cluster.auto_cleanup.enabled');
    }

    private function diff(Collection $collection)
    {
        $connectedChannels = $collection->map(fn($data) => ltrim($data, '#'));

        $onlineChannelsArray = $this
            ->getOnlineChannels($connectedChannels, app(Twitch::class))
            ->toArray();

        $connectedChannelsArray = $connectedChannels->toArray();

        $needPart = array_diff($connectedChannelsArray, $onlineChannelsArray);
        $needJoin = array_diff($onlineChannelsArray, $connectedChannelsArray);

        return [
            'connected' => $connectedChannelsArray,
            'join' => array_values($needJoin),
            'part' => array_values($needPart),
        ];
    }

    private function getOnlineChannels(Collection $connectedChannels, Twitch $twitch): Collection
    {
        return $connectedChannels
            ->chunk(100)
            ->map(function (Collection $collection) use ($twitch) {
                $result = $twitch->getStreams(['user_login' => $collection->toArray()]);

                abort_unless($result->success(), 503, $result->error());

                return collect($result->data())
                    ->map(fn($x) => strtolower($x->user_name));
            })
            ->collapse();
    }
}
