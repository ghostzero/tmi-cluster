<?php

namespace GhostZero\TmiCluster;

use Closure;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Contracts\Pausable;
use GhostZero\TmiCluster\Contracts\Restartable;
use GhostZero\TmiCluster\Contracts\SupervisorJoinHandler;
use GhostZero\TmiCluster\Contracts\Terminable;
use GhostZero\TmiCluster\Events\SupervisorLooped;
use GhostZero\TmiCluster\Process\Process;
use GhostZero\TmiCluster\Process\ProcessOptions;
use GhostZero\TmiCluster\Process\ProcessPool;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use Throwable;

class Supervisor implements Pausable, Restartable, Terminable
{
    use ListensForSignals;

    public bool $working = true;
    public Models\Supervisor $model;
    public array $processPools = [];
    private AutoScale $autoScale;
    private AutoReconnect $autoReconnect;
    private CommandQueue $commandQueue;
    private Closure $output;

    public function __construct(Models\Supervisor $model)
    {
        $this->model = $model;
        $this->processPools = $this->createProcessPools();
        $this->autoScale = app(AutoScale::class);
        $this->autoReconnect = app(AutoReconnect::class);
        $this->commandQueue = app(CommandQueue::class);
        $this->commandQueue->flush($this->model->getKey());
        $this->output = static function () {
            //
        };
    }

    public function monitor(): void
    {
        $this->listenForSignals();

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
            $this->processPendingSignals();

            $this->processPendingCommands();

            // If the supervisor is working, we will perform any needed scaling operations and
            // monitor all of these underlying worker processes to make sure they are still
            // processing queued jobs. If they have died, we will restart them each here.
            if ($this->working) {
                $this->autoScale();
                $this->autoReconnect();

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
            $this->output(null, $e->getTraceAsString());
            app(ExceptionHandler::class)->report($e);
        }
    }

    private function autoScale(): void
    {
        $this->autoScale->scale($this);
    }

    private function autoReconnect(): void
    {
        $this->autoReconnect->handle($this);
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

    public function pause(): void
    {
        $this->working = false;

        $this->pools()->each(fn(ProcessPool $x) => $x->pause());
    }

    public function continue(): void
    {
        $this->working = true;

        $this->pools()->each(fn(ProcessPool $x) => $x->continue());
    }

    public function restart(): void
    {
        $this->working = true;

        $this->pools()->each(fn(ProcessPool $x) => $x->restart());
    }

    public function terminate(int $status = 0): void
    {
        $this->working = false;

        // We will mark this supervisor as terminating so that any user interface can
        // correctly show the supervisor's status. Then, we will scale the process
        // pools down to zero workers to gracefully terminate them all out here.
        $this->pools()->each(function (ProcessPool $pool) {
            $pool->processes()->each(function (Process $process) {
                $process->terminate();
            });
        });

        if ($this->shouldWait()) {
            while ($this->pools()->map(fn(ProcessPool $x) => $x->runningProcesses())->collapse()->count()) {
                sleep(1);
            }
        }

        // cleanup database before the clean stale process kills our models
        $this->model->processes()->forceDelete();
        $this->model->forceDelete();

        $this->exit($status);
    }

    protected function shouldWait(): bool
    {
        return !config('tmi-cluster.fast_termination');
    }

    protected function exit($status = 0): void
    {
        $this->exitProcess($status);
    }

    protected function exitProcess($status = 0): void
    {
        exit((int)$status);
    }

    private function processPendingCommands(): void
    {
        $commands = $this->commandQueue->pending($this->model->getKey());
        $channelsToJoin = [];

        foreach ($commands as $command) {
            switch ($command->command) {
                case CommandQueue::COMMAND_SUPERVISOR_SCALE_OUT:
                    $this->autoScale->scaleOut($this);
                    break;
                case CommandQueue::COMMAND_SUPERVISOR_SCALE_IN:
                    $this->autoScale->scaleIn($this);
                    break;
                case CommandQueue::COMMAND_SUPERVISOR_TERMINATE:
                    $this->terminate();
                    break;
                case CommandQueue::COMMAND_TMI_JOIN:
                    $channelsToJoin[] = $command->options->channel;
                    break;
            }
        }

        if (!empty($channelsToJoin)) {
            app(SupervisorJoinHandler::class)->handle($this, $channelsToJoin);
        }
    }

    public function memoryUsage(): float
    {
        return memory_get_usage() / 1024 / 1024;
    }
}
