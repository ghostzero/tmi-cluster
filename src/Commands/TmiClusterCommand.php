<?php

declare(ticks=1);

namespace GhostZero\TmiCluster\Commands;

use DomainException;
use GhostZero\TmiCluster\Contracts\SupervisorRepository;
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
     */
    public function handle(): int
    {
        try {
            /** @var Supervisor $supervisor */
            $supervisor = app(SupervisorRepository::class)->create();
        } catch (DomainException $e) {
            $this->error('A supervisor with this name is already running.');

            return 13;
        }

        $this->info("Supervisor {$supervisor->model->getKey()} started successfully!");

        return $this->start($supervisor);
    }

    private function start(Supervisor $supervisor): int
    {
        if ($supervisor->model->options['nice']) {
            proc_nice($supervisor->model->options['nice']);
        }

        $supervisor->handleOutputUsing(function ($type, $line) {
            $this->info($line);
        });

        $supervisor->scale(config('tmi-cluster.auto_scale.processes.min'));

        $supervisor->monitor();

        return 0;
    }
}
