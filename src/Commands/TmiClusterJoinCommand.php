<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use Illuminate\Console\Command;

class TmiClusterJoinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:join {channel}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Join a channel in one irc process instance.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $channels = [$this->argument('channel')];
        $result = $this->getChannelDistributor()->joinNow($channels);

        print_r($result);

        return 1;
    }

    private function getChannelDistributor(): ChannelDistributor
    {
        return app(ChannelDistributor::class);
    }
}
