<?php

namespace GhostZero\TmiCluster\Http\Controllers;

use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('tmi-cluster::dashboard.index');
    }

    public function statistics(Request $request): array
    {
        $supervisors = Supervisor::query()->get();

        $ircMessages = $supervisors
            ->sum(function (Supervisor $supervisor) {
                return $supervisor->processes->sum(function (SupervisorProcess $process) {
                    return $process->metrics['irc_messages'] ?? 0;
                });
            });

        $ircCommands = $supervisors
            ->sum(function (Supervisor $supervisor) {
                return $supervisor->processes->sum(function (SupervisorProcess $process) {
                    return $process->metrics['irc_commands'] ?? 0;
                });
            });

        $supervisors->map(function (Supervisor $supervisor) {
            $supervisor->processes->transform(function (SupervisorProcess $supervisor) {
                $supervisor->id_short = explode('-', $supervisor->getKey())[0];
                $supervisor->last_ping_at_in_seconds = $supervisor->last_ping_at->diffInSeconds();
                return $supervisor;
            });
        });

        $time = round(microtime(true) * 1000);

        return [
            'time' => $time,
            'supervisors' => $supervisors,
            'irc_messages' => $ircMessages,
            'irc_messages_per_second' => $this->getDataPerSecond($request, 'irc_messaged', $ircMessages, $time),
            'irc_commands_per_second' => $this->getDataPerSecond($request, 'irc_commands', $ircCommands, $time),
            'channels' => $supervisors
                ->sum(function (Supervisor $supervisor) {
                    return $supervisor->processes->sum(function (SupervisorProcess $process) {
                        return $process->metrics['channels'] ?? 0;
                    });
                }),
            'processes' => $supervisors->sum(fn(Supervisor $supervisor) => count($supervisor->processes)),
        ];
    }

    public function getDataPerSecond(Request $request, string $field, int $current, float $time): float
    {
        if ($request->has(['time', $field])) {
            $lastIrcMessages = $request->get($field, $current);
            $lastTime = (int)$request->get('time', $time);
            $timeDiff = $time - $lastTime;
            $diff = $current - $lastIrcMessages;

            return round($diff / ($timeDiff / 1000));
        }

        return 0;
    }
}
