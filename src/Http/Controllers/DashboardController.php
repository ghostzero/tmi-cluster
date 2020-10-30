<?php

namespace GhostZero\TmiCluster\Http\Controllers;

use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;

class DashboardController extends Controller
{
    public function index()
    {
        return view('tmi-cluster::dashboard.index');
    }

    public function statistics(): array
    {
        $supervisors = Supervisor::query()->get();

        return [
            'supervisors' => $supervisors,
            'irc_messages' => $supervisors
                ->sum(function (Supervisor $supervisor) {
                    return $supervisor->processes->sum(function (SupervisorProcess $process) {
                        return $process->metrics['irc_messages'] ?? 0;
                    });
                }),
            'channels' => $supervisors
                ->sum(function (Supervisor $supervisor) {
                    return $supervisor->processes->sum(function (SupervisorProcess $process) {
                        return $process->metrics['channels'] ?? 0;
                    });
                }),
            'processes' => $supervisors
                ->sum(fn(Supervisor $supervisor) => count($supervisor->processes)),
        ];
    }
}
