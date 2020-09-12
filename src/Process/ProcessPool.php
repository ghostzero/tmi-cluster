<?php

namespace GhostZero\TmiCluster\Process;

use Carbon\CarbonImmutable;
use Closure;
use Countable;
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

    public function __construct(ProcessOptions $options, Closure $output)
    {
        $this->options = $options;
        $this->output = $output;
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

    public function scale($processes): void
    {
        $processes = max(0, (int) $processes);

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
            ->each(function ($process) {
                $process['process']->terminate();
            });
    }

    public function markForTermination(Process $process)
    {
        $this->terminatingProcesses[] = [
            'process' => $process, 'terminatedAt' => CarbonImmutable::now(),
        ];
    }

    /**
     * Remove the given number of processes from the process array.
     *
     * @param  int  $count
     * @return void
     */
    protected function removeProcesses($count)
    {
        array_splice($this->processes, 0, $count);

        $this->processes = array_values($this->processes);
    }

    protected function start(): self
    {
        $this->processes[] = $this->createProcess()->handleOutputUsing(function ($type, $line) {
            call_user_func($this->output, $type, $line);
        });

        return $this;
    }

    protected function createProcess(): Process
    {
        Log::info($this->options->toWorkerCommand($uuid = Str::uuid()));

        return new Process(SystemProcess::fromShellCommandline(
            $this->options->toWorkerCommand($uuid), $this->options->getWorkingDirectory()
        )->setTimeout(null)->disableOutput());
    }

    public function restart(): void
    {
        $count = count($this->processes);

        $this->scale(0);

        $this->scale($count);
    }
}
