<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Models\Supervisor;
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
        /** @var SupervisorRepository $repository */
        $repository = app(SupervisorRepository::class);

        $repository->flushStale();

        $supervisors = $repository->all()
            ->map(function (Supervisor $supervisor) {
                return [
                    $supervisor->getKey(),
                    $supervisor->name,
                    $supervisor->last_ping_at->diffInSeconds(),
                    $supervisor->processes()->count(),
                ];
            });

        $headers = ['ID', 'Name', 'Last Ping', 'Processes'];

        $this->table($headers, $supervisors);

        return 0;
    }
}
