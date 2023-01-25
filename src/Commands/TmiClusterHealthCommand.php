<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Support\Health;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class TmiClusterHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:health
                                {--host-only : Only check all supervisors on the same host}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if the TMI cluster is healthy.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var SupervisorRepository $repository */
        $repository = app(SupervisorRepository::class);

        $supervisors = $this->option('host-only')
            ? $this->getHostSupervisors($repository)
            : $repository->all();

        if (Health::isOperational($supervisors)) {
            $this->info('TMI cluster is healthy.');
            return 0;
        }

        $this->error('TMI cluster is unhealthy.');
        return 1;
    }

    private function getHostSupervisors(SupervisorRepository $repository): Collection
    {
        return $repository->all()->filter(function (Supervisor $supervisor) {
            return str_starts_with(sprintf('%s-', $supervisor->getKey()), gethostname());
        });
    }
}
