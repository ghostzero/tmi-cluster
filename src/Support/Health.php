<?php

namespace GhostZero\TmiCluster\Support;

use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use Illuminate\Support\Collection;

class Health
{
    /**
     * Time in seconds after which a supervisor is considered unhealthy.
     */
    private const INRESPONSIVE_THRESHOLD = 60;

    public static function isOperational(Collection $supervisors, bool $atLeastOneProcess = false): bool
    {
        $operational = $supervisors
            ->sum(function (Supervisor $supervisor) {
                return $supervisor->processes->filter(function (SupervisorProcess $process) {
                    return self::isInConnectedState($process) || self::isInInitialState($process);
                })->count();
            });

        $count = $supervisors
            ->sum(function (Supervisor $supervisor) {
                return $supervisor->processes->count();
            });

        if ($atLeastOneProcess) {
            return $operational > 0;
        }

        return $count > 0 && $operational === $count;
    }

    /**
     * Consider a process in initial state if state is in initialize and ping is within the threshold.
     */
    private static function isInInitialState(SupervisorProcess $process): bool
    {
        return $process->state === SupervisorProcess::STATE_INITIALIZE
            && $process->last_ping_at->diffInSeconds() < self::INRESPONSIVE_THRESHOLD;
    }

    /**
     * Consider a process in connected state if state is in connected and ping is within the threshold.
     */
    private static function isInConnectedState(SupervisorProcess $process): bool
    {
        return $process->state === SupervisorProcess::STATE_CONNECTED
            && $process->last_ping_at->diffInSeconds() < self::INRESPONSIVE_THRESHOLD;
    }
}
