<?php

namespace GhostZero\TmiCluster\Support;

use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use Illuminate\Support\Collection;

class Health
{
    public static function isOperational(Collection $supervisors): bool
    {
        $operational = $supervisors
            ->sum(function (Supervisor $supervisor) {
                return $supervisor->processes->filter(function (SupervisorProcess $process) {
                    return $process->state === SupervisorProcess::STATE_CONNECTED;
                })->count();
            });
        $count = $supervisors
            ->sum(function (Supervisor $supervisor) {
                return $supervisor->processes->count();
            });

        return $count > 0 && $operational === $count;
    }
}
