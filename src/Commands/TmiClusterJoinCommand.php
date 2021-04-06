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
    protected $signature = 'tmi-cluster:join {channel*}';

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
        $channels = $this->argument('channel');
        $results = $this->getChannelDistributor()->joinNow($channels);

        foreach ($results as $type => $channels) {
            if (empty($channels)) {
                continue;
            }

            $this->info(sprintf('%s:', ucfirst($type)));
            foreach ($channels as $channel) {
                $this->info(sprintf("\t%s", $channel));
            }
        }

        return 0;
    }

    private function getChannelDistributor(): ChannelDistributor
    {
        return app(ChannelDistributor::class);
    }
}
