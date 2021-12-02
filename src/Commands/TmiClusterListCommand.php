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
    protected $description = 'List all active supervisors';

    /**
     * Execute the console command.
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
            ->map(function (SupervisorProcess $process) {
                return [
                    $process->getKey(),
                    $process->supervisor ? $process->supervisor->getKey() : 'N/A',
                    $process->state,
                    $process->last_ping_at ? $process->last_ping_at->diffInSeconds() : 'N/A',
                    count($process->channels),
                    $process->memory_usage,
                ];
            });

        $this->table([
            'ID', 'Supervisor', 'State', 'Last Ping', 'Channels', 'Memory Usage'
        ], $processes);

        return 0;
    }
}
