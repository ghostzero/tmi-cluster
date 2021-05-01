<?php

namespace GhostZero\TmiCluster\Repositories;

use GhostZero\Tmi\Channel as TmiChannel;
use GhostZero\TmiCluster\AutoCleanup;
use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Contracts\Invitable;
use GhostZero\TmiCluster\Models\Channel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DatabaseChannelManager implements ChannelManager
{
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
        return Channel::query()
            ->whereIn('id', array_map(fn($channel) => $this->getKey($channel), $channels))
            ->where(['revoked' => false])
            ->pluck('id')
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function acknowledged(array $channels): self
    {
        Channel::query()
            ->whereIn('id', array_map(fn($channel) => $this->getKey($channel), $channels))
            ->update([
                'acknowledged_at' => now(),
            ]);

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
}