<?php

namespace GhostZero\TmiCluster\Traits;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Exceptions\NotFoundSupervisorException;
use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;

trait TmiClusterHelpers
{
    public static function sendMessage(string $channel, $message): void
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $channel = self::channelName($channel);
        $commandQueue->push(CommandQueue::NAME_ANY_SUPERVISOR, CommandQueue::COMMAND_TMI_WRITE, [
            'raw_command' => "PRIVMSG {$channel} :{$message}",
        ]);
    }

    /**
     * @param array $channels a list of channels that needs to be joined
     * @param array $staleIds a list of supervisors or processes ids, that needs to be avoid
     * @param bool $throwNotFoundException
     */
    public static function joinNextServer(array $channels, array $staleIds = [], bool $throwNotFoundException = false): array
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);
        $result = ['lost' => [], 'migrated' => []];

        $supervisors = Supervisor::query()
            ->whereTime('last_ping_at', '>', now()->subSeconds(10))
            ->whereNotIn('id', $staleIds)
            ->get()->map(function (Supervisor $supervisor) {
                return [
                    'name' => $supervisor->getKey(),
                    'channels' => $supervisor->processes->sum(function (SupervisorProcess $process) {
                        return count($process->channels);
                    }),
                ];
            })->sortBy('channels');

        if ($supervisors->isEmpty()) {
            if ($throwNotFoundException) {
                // we let handle the parent call the decision
                throw NotFoundSupervisorException::fromJoinNextServer();
            }

            // we didn't get any server, that is ready to join our channels
            // so we move them to our lost and found channel queue
            $commandQueue->push(CommandQueue::NAME_LOST_AND_FOUND, CommandQueue::COMMAND_TMI_JOIN, [
                'channels' => $channels,
                'staleIds' => $staleIds,
            ]);

            $result['lost'] = $channels;

            return $result;
        }

        foreach ($channels as $channel) {
            $nextSupervisor = $supervisors->sortBy('channels')->shift();
            $nextSupervisor['channels'] += 1;
            $supervisors->push($nextSupervisor);

            $commandQueue->push($nextSupervisor['name'], CommandQueue::COMMAND_TMI_JOIN, [
                'channel' => $channel,
                'staleIds' => $staleIds,
            ]);

            $result['migrated'][$channel] = $nextSupervisor['name'];
        }

        return $result;
    }

    private static function channelName(string $channel): string
    {
        if ($channel[0] !== '#') {
            $channel = "#$channel";
        }

        return $channel;
    }
}
