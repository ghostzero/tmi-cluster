<?php

namespace GhostZero\TmiCluster\Process;

use Carbon\CarbonImmutable;
use Closure;
use Countable;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use GhostZero\TmiCluster\Supervisor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process as SystemProcess;

class ProcessPool implements Countable
{
    private array $processes = [];
    private array $terminatingProcesses = [];
    private ProcessOptions $options;
    private Closure $output;
    /**
     * @var Supervisor
     */
    private Supervisor $supervisor;

    public function __construct(ProcessOptions $options, Closure $output, Supervisor $supervisor)
    {
        $this->options = $options;
        $this->output = $output;
        $this->supervisor = $supervisor;
    }

    public function count(): int
    {
        return count($this->processes);
    }

    public function monitor(): void
    {
        $this->processes()->each(fn(Process $x) => $x->monitor());
    }

    public function processes(): Collection
    {
        return new Collection($this->processes);
    }

    public function runningProcesses(): Collection
    {
        return $this->processes()->filter(function ($process) {
            return $process->process->isRunning();
        });
    }

    public function scale($processes): void
    {
        $processes = max(0, (int)$processes);

        if ($processes === count($this->processes)) {
            return;
        }

        if ($processes > count($this->processes)) {
            $this->scaleUp($processes);
        } else {
            $this->scaleDown($processes);
        }
    }

    protected function scaleUp($processes): void
    {
        $difference = $processes - count($this->processes);

        for ($i = 0; $i < $difference; $i++) {
            $this->start();
        }
    }

    protected function scaleDown($processes): void
    {
        $difference = count($this->processes) - $processes;

        // Here we will slice off the correct number of processes that we need to terminate
        // and remove them from the active process array. We'll be adding them the array
        // of terminating processes where they'll run until they are fully terminated.
        $terminatingProcesses = array_slice(
            $this->processes, 0, $difference
        );

        collect($terminatingProcesses)->each(function ($process) {
            $this->markForTermination($process);
        })->all();

        $this->removeProcesses($difference);

        // Finally we will call the terminate method on each of the processes that need get
        // terminated so they can start terminating. Terminating is a graceful operation
        // so any jobs they are already running will finish running before these quit.
        collect($this->terminatingProcesses)
            ->each(function (array $process) {
                $process['process']->terminate();
            });
    }

    public function markForTermination(Process $process): void
    {
        $this->terminatingProcesses[] = [
            'process' => $process, 'terminatedAt' => CarbonImmutable::now(),
        ];
    }

    protected function removeProcesses(int $count): void
    {
        array_splice($this->processes, 0, $count);

        $this->processes = array_values($this->processes);
    }

    protected function start(): self
    {
        $process = $this->createProcess();

        $process->handleOutputUsing(function ($type, $line) use($process) {
            call_user_func($this->output, $type, $process->getUuid() . ' | ' . $line);
        });

        $this->processes[] = $process;

        return $this;
    }

    protected function createProcess(): Process
    {
        $model = SupervisorProcess::query()->forceCreate([
            'id' => Str::uuid(),
            'supervisor_id' => $this->supervisor->model->getKey(),
            'state' => SupervisorProcess::STATE_INITIALIZE,
            'channels' => [],
            'last_ping_at' => now(),
        ]);

        return new Process(SystemProcess::fromShellCommandline(
            $this->options->toWorkerCommand($model->getKey()), $this->options->getWorkingDirectory()
        )->setTimeout(null)->disableOutput(), $model->getKey());
    }

    public function restart(): void
    {
        $count = count($this->processes);

        $this->scale(0);

        $this->scale($count);
    }
}
