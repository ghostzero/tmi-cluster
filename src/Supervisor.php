<?php

namespace GhostZero\TmiCluster;

use Closure;
use DomainException;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Events\SupervisorLooped;
use GhostZero\TmiCluster\Process\ProcessOptions;
use GhostZero\TmiCluster\Process\ProcessPool;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use Throwable;

class Supervisor
{
    public array $processPools = [];

    public bool $working = true;

    private Closure $output;

    public Models\Supervisor $model;

    public function __construct(Models\Supervisor $model)
    {
        $this->model = $model;
        $this->processPools = $this->createProcessPools();
        $this->output = static function () {
            //
        };
    }

    public function ensureNoDuplicateSupervisors(): void
    {
        $exists = $this->model->newModelQuery()
            ->where(['name' => $this->model->name])
            ->whereKeyNot($this->model->getKey())
            ->exists();

        if ($exists) {
            throw new DomainException('A Supervisor with this name already exists.');
        }
    }

    public function monitor(): void
    {
        $this->ensureNoDuplicateSupervisors();

        $this->model->save();

        while (true) {
            sleep(1);

            $this->loop();
        }
    }

    public function handleOutputUsing(Closure $output): void
    {
        $this->output = $output;
    }

    public function scale(int $int): void
    {
        $this->pools()->each(fn(ProcessPool $x) => $x->scale($int));
    }

    public function loop(): void
    {
        try {
            // todo process pending commands
            $this->processPendingCommands();

            // If the supervisor is working, we will perform any needed scaling operations and
            // monitor all of these underlying worker processes to make sure they are still
            // processing queued jobs. If they have died, we will restart them each here.
            if ($this->working) {
                $this->autoScale();

                $this->pools()->each(fn(ProcessPool $x) => $x->monitor());
            }

            // Update all model data here
            $this->model->forceFill([
                'last_ping_at' => now(),
                'metrics' => [
                    // todo add some fancy metrics
                ]
            ]);

            // Next, we'll persist the supervisor state to storage so that it can be read by a
            // user interface. This contains information on the specific options for it and
            // the current number of worker processes per queue for easy load monitoring.
            $this->model->save();

            event(new SupervisorLooped($this));
        } catch (Throwable $e) {
            $this->output(null, $e->getMessage());
            app(ExceptionHandler::class)->report($e);
        }
    }

    private function autoScale(): void
    {
        app(AutoScale::class)->scale($this);
    }

    private function createProcessPools(): array
    {
        $options = new ProcessOptions($this->model->getKey(), $this->model->options);
        return [$this->createSingleProcessPool($options)];
    }

    private function createSingleProcessPool(ProcessOptions $options): ProcessPool
    {
        return new ProcessPool($options, function ($type, $line) {
            $this->output($type, $line);
        }, $this);
    }

    public function output($type, $line): void
    {
        call_user_func($this->output, $type, $line);
    }

    private function pools(): Collection
    {
        return new Collection($this->processPools);
    }

    public function processes(): Collection
    {
        return $this->pools()->map(fn(ProcessPool $x) => $x->processes())->collapse();
    }

    private function processPendingCommands(): void
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);
        $commands = $commandQueue->pending($this->model->getKey());

        foreach ($commands as $command) {
            switch ($command->command) {
                case CommandQueue::COMMAND_TMI_JOIN:
                    $this->handleJoin($command->options->channel);
                    break;
            }
        }
    }

    private function handleJoin($channel): void
    {
        // todo implement irc join
        // if processes full force join
        // if some space available, join
        // if no space available, spin up, re-queue channel
    }
}
