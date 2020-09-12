<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\ClusterClient;
use GhostZero\TmiCluster\Contracts\ClusterClientOptions;
use Illuminate\Console\Command;

class TmiClusterProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:process
                            {uuid : The unique identifier of the tmi client}
                            {--supervisor= : The name of the supervisor the client belongs to}
                            {--stop-when-empty : Stop when the channels list is empty}
                            {--memory=128 : The memory limit in megabytes}
                            {--force : Force the tmi client to run even in maintenance mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        return app(ClusterClient::class)
            ->connect($this->clusterClientOptions());
    }

    /**
     * Returns the given cluster client configuration.
     *
     * @return ClusterClientOptions
     */
    private function clusterClientOptions(): ClusterClientOptions
    {
        return new ClusterClientOptions(
            $this->argument('uuid'),
            $this->option('supervisor'),
            $this->option('stop-when-empty'),
            $this->option('memory'),
            $this->option('force')
        );
    }
}
