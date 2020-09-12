<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use Illuminate\Console\Command;

class TmiClusterPurgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:purge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge all stale supervisors.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        app(SupervisorRepository::class)->flushStale();

        $this->info('Stale supervisors have been purged.');

        return 0;
    }
}
