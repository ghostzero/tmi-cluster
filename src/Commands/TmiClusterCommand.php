<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use Exception;
use GhostZero\TmiCluster\Supervisor;
use Illuminate\Console\Command;

class TmiClusterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts a new tmi cluster supervisor.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /** @var Supervisor $supervisor */
        $supervisor = app(SupervisorRepository::class)->create();

        try {
            $supervisor->ensureNoDuplicateSupervisors();
        } catch (Exception $e) {
            $this->error('A supervisor with this name is already running.');

            return 13;
        }

        $this->info('TMI Cluster started successfully!');

        return $this->start($supervisor);
    }

    private function start(Supervisor $supervisor): int
    {
        if ($supervisor->model->options['nice']) {
            proc_nice($supervisor->model->options['nice']);
        }

        $supervisor->handleOutputUsing(function ($type, $line) {
            $this->output->write($line);
        });

        $supervisor->scale(3);

        $supervisor->monitor();

        return 0;
    }
}
