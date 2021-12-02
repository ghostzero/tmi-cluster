<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use Illuminate\Console\Command;

class TmiClusterTerminateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:terminate {supervisor?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a terminate command to a given or all supervisors';

    /**
     * Execute the console command.
     */
    public function handle(SupervisorRepository $repository, CommandQueue $commandQueue): int
    {
        if($id = $this->argument('supervisor')) {
            $supervisors = $repository->all()->whereIn('id', [$id]);
        } else {
            $supervisors = $repository->all();
        }

        foreach ($supervisors as $supervisor) {
            $this->info(sprintf('Terminating %s...', $supervisor->getKey()));
            $commandQueue->push($supervisor->getKey(), CommandQueue::COMMAND_SUPERVISOR_TERMINATE);
        }

        return 0;
    }
}
