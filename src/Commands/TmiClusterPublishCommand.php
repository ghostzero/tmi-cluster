<?php

namespace GhostZero\TmiCluster\Commands;

use Illuminate\Console\Command;

class TmiClusterPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all TMI Cluster assets';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing TMI Cluster assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'tmi-cluster-assets', '--force' => true]);

        $this->info('TMI Cluster assets published successfully.');
    }
}
