<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\TwitchLogin;
use Illuminate\Console\Command;

class TmiClusterAuthorizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:authorize {channel*}
                                {--R|revoke : Revoke current authorization}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all TMI Cluster assets & configurations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $channels = $this->argument('channel');

        if ($this->option('revoke')) {
            $this->revokeAuthorization($channels);

            $this->info('Channels authorization revoked successfully.');
        } else {
            $this->authorize($channels);

            $this->info('Channels authorized successfully.');
        }

        return 0;
    }

    private function authorize($channels): void
    {
        foreach ($channels as $channel) {
            $this->getChannelManager()->authorize(new TwitchLogin($channel));
        }
    }

    private function revokeAuthorization(array $channels)
    {
        foreach ($channels as $channel) {
            $this->getChannelManager()->revokeAuthorization(new TwitchLogin($channel));
        }
    }

    private function getChannelManager(): ChannelManager
    {
        return app(ChannelManager::class);
    }
}
