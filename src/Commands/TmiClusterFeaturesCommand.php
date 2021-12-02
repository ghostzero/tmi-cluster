<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\AutoCleanup;
use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Repositories\DatabaseChannelManager;
use Illuminate\Console\Command;

class TmiClusterFeaturesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:features';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available features';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->comment('TMI Cluster:');
        $this->info(sprintf('Framework: %s', config('tmi-cluster.framework')));
        $this->info(sprintf('Fast Termination: %s', (config('tmi-cluster.fast_termination')) ? 'Enabled' : 'Disabled'));

        $this->comment('Connections:');
        $this->info(sprintf('Database: %s', config('database.default')));
        $this->info(sprintf('Redis: %s', config('tmi-cluster.use')));
        $this->info(sprintf('Redis Prefix: %s', config('tmi-cluster.prefix')));

        $this->comment('TMI:');
        $this->info(sprintf('TMI Username: %s', config('tmi-cluster.tmi.identity.username') ?? 'Not set'));
        $this->info(sprintf('TMI Reconnect: %s', (config('tmi-cluster.tmi.connection.reconnect') ? 'Enabled' : 'Disabled')));
        $this->info(sprintf('TMI Rejoin: %s', (config('tmi-cluster.tmi.connection.rejoin') ? 'Enabled' : 'Disabled')));

        $this->comment('You will use the following implementations:');

        $channelManagerName = get_class(app(ChannelManager::class));
        $channelDistributorName = get_class(app(ChannelDistributor::class));

        $this->info(sprintf("Your channel manager is %s", $channelManagerName));
        $this->info(sprintf("Your channel distributor is %s", $channelDistributorName));

        $this->comment('Auto Reconnect:');

        if ($channelManagerName === DatabaseChannelManager::class) {
            $this->info('Your channel manager supports auto reconnect.');
        } else {
            $this->comment('Your channel manager does not support auto reconnect.');
        }

        $this->comment('Auto Cleanup:');

        if (AutoCleanup::shouldCleanup()) {
            $this->info('The auto cleanup feature is enabled.');

            $settings = config('tmi-cluster.channel_manager.auto_cleanup');

            $this->info(sprintf('The auto cleanup interval is %d seconds.', $settings['interval']));
            $this->info(sprintf('The auto cleanup will have a max start delay of %d seconds.', $settings['max_delay']));
        } else {
            $this->comment('The auto cleanup feature is disabled.');
        }

        return 0;
    }
}
