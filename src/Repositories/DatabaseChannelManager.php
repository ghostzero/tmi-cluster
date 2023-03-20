<?php

namespace GhostZero\TmiCluster\Repositories;

use GhostZero\Tmi\Channel as TmiChannel;
use GhostZero\TmiCluster\AutoCleanup;
use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Contracts\Invitable;
use GhostZero\TmiCluster\Models\Channel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DatabaseChannelManager implements ChannelManager
{
    /**
     * Determines if the channel should be reconnected.
     */
    public const OPTION_RECONNECT = 'reconnect';

    /**
     * There is limit 65,535 (2^16-1) placeholders.
     * We will chunk channels in 50K operations.
     */
    protected const MAX_CHANNELS_PER_EXECUTION = 50000;

    /**
     * @inheritDoc
     */
    public function authorize(Invitable $channel, array $options = []): self
    {
        Channel::query()->updateOrCreate(['id' => $this->getKey($channel->getTwitchLogin())], array_merge([
            'revoked' => false,
        ], $options));

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function revokeAuthorization(Invitable $channel): self
    {
        Channel::query()
            ->whereKey($this->getKey($channel->getTwitchLogin()))
            ->update([
                'revoked' => true,
            ]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function authorized(array $channels): array
    {
        return Collection::make($channels)
            ->chunk(self::MAX_CHANNELS_PER_EXECUTION)
            ->map(function (Collection $channels) {
                return Channel::query()
                    ->whereIn('id', $channels->map(fn(string $channel) => $this->getKey($channel)))
                    ->where(['revoked' => false])
                    ->pluck('id')
                    ->toArray();
            })
            ->collapse()
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function acknowledged(array $channels): self
    {
        Collection::make($channels)
            ->chunk(self::MAX_CHANNELS_PER_EXECUTION)
            ->each(function (Collection $channels) {
                Channel::query()
                    ->whereIn('id', $channels->map(fn(string $channel) => $this->getKey($channel)))
                    ->update([
                        'acknowledged_at' => now(),
                    ]);
            });

        return $this;
    }

    private function getKey(string $channel): string
    {
        return TmiChannel::sanitize($channel);
    }

    /**
     * @inheritDoc
     */
    public function stale(array $channels): array
    {
        return Channel::query()
            ->whereIn('id', array_map(fn($channel) => $this->getKey($channel), $channels))
            ->where(function (Builder $query) {
                $query->where(['revoked' => true]);

                if (AutoCleanup::shouldCleanup()) {
                    $staleAfter = Carbon::now()
                        ->subHours(config('tmi-cluster.channel_manager.channel.stale', 168))
                        ->format('Y-m-d H:i:s');

                    $query->orWhere('acknowledged_at', '<', $staleAfter);
                }
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Returns all channels that are not revoked and have reconnected equals true.
     *
     * @param array $channels channels that are currently connected
     */
    public function disconnected(array $channels): array
    {
        $potentialChannels = Channel::query()
            ->where(['revoked' => false])
            ->where(['reconnect' => true])
            ->pluck('id')
            ->toArray();

        return array_diff($potentialChannels, array_map(fn($channel) => $this->getKey($channel), $channels));
    }
}