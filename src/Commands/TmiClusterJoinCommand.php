<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TmiClusterJoinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:join {channel*}
                                {--A|authorize : Authorize all given channels}';

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

        if ($this->option('authorize')) {
            $result = Artisan::call('tmi-cluster:authorize', [
                'channel' => $channels,
            ]);

            if ($result === 0) {
                $this->info(Artisan::output());
            } else {
                $this->error(Artisan::output());
            }
        }

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
