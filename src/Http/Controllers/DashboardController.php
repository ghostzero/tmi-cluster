<?php

namespace GhostZero\TmiCluster\Http\Controllers;

use GhostZero\TmiCluster\AutoScale;
use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    public function index()
    {
        return view('tmi-cluster::dashboard.index');
    }

    public function health(): Response
    {
        if ($this->isOperational(Supervisor::query()->get())) {
            return response('', 204);
        }

        return response('', 503);
    }

    public function statistics(Request $request): array
    {
        /** @var AutoScale $autoScale */
        $autoScale = app(AutoScale::class);

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
            $tokens = explode('-', $supervisor->getKey());
            $supervisor->id_short = $tokens[count($tokens) - 1];
            $supervisor->processes->transform(function (SupervisorProcess $process) {
                $process->id_short = explode('-', $process->getKey())[0];
                $process->last_ping_at_in_seconds = $process->last_ping_at->diffInSeconds();
                return $process;
            });
        });

        $time = round(microtime(true) * 1000);

        return [
            'time' => $time,
            'operational' => $this->isOperational($supervisors),
            'supervisors' => $supervisors,
            'irc_messages' => $ircMessages,
            'irc_commands' => $ircCommands,
            'irc_messages_per_second' => $this->getDataPerSecond($request, 'irc_messages', $ircMessages, $time),
            'irc_commands_per_second' => $this->getDataPerSecond($request, 'irc_commands', $ircCommands, $time),
            'channels' => $supervisors
                ->sum(function (Supervisor $supervisor) {
                    return $supervisor->processes->sum(function (SupervisorProcess $process) {
                        return $process->metrics['channels'] ?? 0;
                    });
                }),
            'processes' => $supervisors->sum(fn(Supervisor $supervisor) => count($supervisor->processes)),
            'auto_scale' => array_merge(config('tmi-cluster.auto_scale'), [
                'minimum_scale' => $autoScale->getMinimumScale(),
            ]),
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

    private function isOperational(Collection $supervisors): bool
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
