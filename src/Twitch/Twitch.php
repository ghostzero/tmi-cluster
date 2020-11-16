<?php

namespace GhostZero\TmiCluster\Twitch;

use GhostZero\TmiCluster\Twitch\Concerns\AuthenticationTrait;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use romanzipp\Twitch\Twitch as Base;

class Twitch extends Base
{
    use AuthenticationTrait;

    private Repository $redisRepository;

    public function __construct(RedisFactory $redis)
    {
        parent::__construct();

        if ($clientId = config('tmi-cluster.helix.client_id')) {
            $this->setClientId($clientId);
        }

        if ($clientSecret = config('tmi-cluster.helix.client_secret')) {
            $this->setClientSecret($clientSecret);
        }

        $this->redisRepository = new Repository(new RedisStore($redis, '', 'tmi-cluster'));
    }

}