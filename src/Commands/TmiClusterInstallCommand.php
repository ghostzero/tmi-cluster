<?php

namespace GhostZero\TmiCluster\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

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
    protected $description = 'Install all of the TmiCluster resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing TMI Cluster Assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'tmi-cluster-assets']);

        $this->comment('Publishing TMI Cluster Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'tmi-cluster-config']);

        $this->info('TMI Cluster scaffolding installed successfully.');
    }
}
