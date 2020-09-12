<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use Illuminate\Console\Command;

class TmiClusterListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all active supervisors.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $supervisors = app(SupervisorRepository::class)
            ->all(['id', 'name'])
            ->toArray();

        $headers = ['ID', 'Name'];

        $this->table($headers, $supervisors);

        return 0;
    }
}
