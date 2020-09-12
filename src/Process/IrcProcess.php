<?php

namespace App\Process;

use Carbon\CarbonImmutable;
use Closure;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

class IrcProcess
{
    private Process $process;
    private Closure $output;
    private ?CarbonImmutable $restartAgainAt = null;

    public function __construct(Process $process)
    {
        $this->process = $process;
        $this->output = function () {
            //
        };
    }

    public function monitor(): void
    {
        if ($this->process->isRunning() || $this->coolingDown()) {
            return;
        }

        call_user_func($this->output, null);

        $this->restart();
    }

    protected function restart()
    {
        if ($this->process->isStarted()) {
            //event(new WorkerProcessRestarting($this));
            call_user_func($this->output, null, 'WorkerProcessRestarting');
        }

        $this->start($this->output);
    }

    public function terminate()
    {
        $this->sendSignal(SIGTERM);
    }

    public function stop(): void
    {
        if ($this->process->isRunning()) {
            $this->process->stop();
        }
    }

    protected function sendSignal($signal): void
    {
        try {
            $this->process->signal($signal);
        } catch (ExceptionInterface $e) {
            if ($this->process->isRunning()) {
                throw $e;
            }
        }
    }

    public function start(Closure $output)
    {
        $this->handleOutputUsing($output)->cooldown();

        $this->process->start($output);

        return $this;
    }

    protected function cooldown()
    {
        if ($this->coolingDown()) {
            return;
        }

        if ($this->restartAgainAt) {
            $this->restartAgainAt = ! $this->process->isRunning()
                ? CarbonImmutable::now()->addMinute()
                : null;

            if (! $this->process->isRunning()) {
                call_user_func($this->output, null, 'UnableToLaunchProcess');
                // event(new UnableToLaunchProcess($this));
            }
        } else {
            $this->restartAgainAt = CarbonImmutable::now()->addSecond();
        }
    }

    public function coolingDown(): bool
    {
        return isset($this->restartAgainAt) &&
            CarbonImmutable::now()->lt($this->restartAgainAt);
    }

    public function handleOutputUsing(Closure $callback): self
    {
        $this->output = $callback;

        return $this;
    }
}
