<?php

namespace GhostZero\TmiCluster\Commands;

use Illuminate\Console\Command;

class TmiClusterInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all TMI Cluster assets & configurations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->comment('Publishing TMI Cluster assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'tmi-cluster-assets', '--force' => true]);

        $this->comment('Publishing TMI Cluster configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'tmi-cluster-config']);

        $this->info('TMI Cluster scaffolding installed successfully.');

        return 0;
    }
}
