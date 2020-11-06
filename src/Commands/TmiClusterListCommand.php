<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;
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

        $supervisors = $repository->all()
            ->map(function (Supervisor $supervisor) {
                return [
                    $supervisor->getKey(),
                    $supervisor->getKey(),
                    $supervisor->last_ping_at->diffInSeconds(),
                    $supervisor->processes()->count(),
                ];
            });

        $this->table([
            'ID', 'Name', 'Last Ping', 'Processes'
        ], $supervisors);


        $processes = SupervisorProcess::all()
            ->map(function (SupervisorProcess $row) {
                return [
                    $row->getKey(),
                    $row->supervisor->getKey(),
                    $row->state,
                    $row->last_ping_at ? $row->last_ping_at->diffInSeconds() : 'N/A',
                    count($row->channels),
                ];
            });

        $this->table([
            'ID', 'Supervisor', 'State', 'Last Ping', 'Channels'
        ], $processes);

        return 0;
    }
}
