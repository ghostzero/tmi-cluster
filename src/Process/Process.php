<?php

namespace GhostZero\TmiCluster\Process;

use Carbon\CarbonImmutable;
use Closure;
use GhostZero\TmiCluster\Contracts\Pausable;
use GhostZero\TmiCluster\Contracts\Restartable;
use GhostZero\TmiCluster\Contracts\Terminable;
use GhostZero\TmiCluster\Events\UnableToLaunchProcess;
use GhostZero\TmiCluster\Events\WorkerProcessRestarting;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process as SystemProcess;

class Process implements Pausable, Restartable, Terminable
{
    public SystemProcess $systemProcess;
    private string $uuid;
    private Closure $output;
    private ?CarbonImmutable $restartAgainAt = null;

    private const HEALTHY_EXIT_CODES = [
        5, // IRC Client disconnected (restart).
        6, // Exit via CommandQueue (restart).
    ];

    public function __construct(SystemProcess $systemProcess, string $uuid)
    {
        $this->systemProcess = $systemProcess;
        $this->uuid = $uuid;
        $this->output = function () {
            //
        };
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function isRunning(): bool
    {
        return $this->systemProcess->isRunning();
    }

    public function monitor(): void
    {
        if ($this->systemProcess->isRunning() || $this->coolingDown()) {
            return;
        }

        $this->restart();
    }

    public function restart(): void
    {
        if ($this->systemProcess->isStarted()) {
            event(new WorkerProcessRestarting($this));
        }

        $this->start();
    }

    public function terminate($status = 0): void
    {
        $this->sendSignal(SIGINT);
    }

    public function stop(): void
    {
        if ($this->systemProcess->isRunning()) {
            $this->systemProcess->stop();
        }
    }

    protected function sendSignal($signal): void
    {
        try {
            $this->systemProcess->signal($signal);
        } catch (ExceptionInterface $e) {
            if ($this->systemProcess->isRunning()) {
                throw $e;
            }
        }
    }

    public function start(): Process
    {
        $this->cooldown();

        $this->systemProcess->start($this->output);

        return $this;
    }

    public function pause(): void
    {
        $this->sendSignal(SIGUSR2);
    }

    public function continue(): void
    {
        $this->sendSignal(SIGCONT);
    }

    protected function cooldown()
    {
        if ($this->coolingDown()) {
            return;
        }

        if ($this->restartAgainAt) {
            $this->restartAgainAt = !$this->systemProcess->isRunning()
                ? CarbonImmutable::now()->addMinute()
                : null;

            if (!$this->systemProcess->isRunning()) {
                event(new UnableToLaunchProcess($this));
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


    public function shouldMarkForTermination(): bool
    {
        if ($exitCode = $this->systemProcess->getExitCode()) {
            return !in_array($this->systemProcess->getExitCode(), self::HEALTHY_EXIT_CODES);
        }

        return false;
    }
}
