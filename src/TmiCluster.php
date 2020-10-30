<?php

namespace GhostZero\TmiCluster;

use Exception;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Http\Controllers;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class TmiCluster
{
    /**
     * Configure the Redis databases that will store TMI Cluster data.
     *
     * @param string $connection
     * @return void
     * @throws Exception
     */
    public static function use(string $connection): void
    {
        if (is_null($config = config("database.redis.{$connection}"))) {
            throw new RuntimeException("Redis connection [{$connection}] has not been configured.");
        }

        config(['database.redis.tmi-cluster' => array_merge($config, [
            'options' => ['prefix' => config('tmi-cluster.prefix') ?: 'tmi-cluster:'],
        ])]);
    }

    public static function joinNextServer(array $channels): void
    {
        $commandQueue = app(CommandQueue::class);

        $supervisor = Models\Supervisor::query()
            ->whereTime('last_ping_at', '>', now()->subSeconds(10))
            ->get()->map(function (Models\Supervisor $supervisor) {
                return [
                    'name' => $supervisor->getKey(),
                    'channels' => $supervisor->processes->sum(function (SupervisorProcess $process) {
                        return count($process->channels);
                    }),
                ];
            })->sortBy('channels');

        foreach ($channels as $channel) {
            $supervisor = $supervisor->sortBy('channels');
            $nextSupervisor = $supervisor->shift();
            $nextSupervisor['channels'] += 1;
            $supervisor->push($nextSupervisor);

            $commandQueue->push($nextSupervisor['name'], CommandQueue::COMMAND_TMI_JOIN, ['channel' => $channel]);
        }
    }

    public static function routes(): void
    {
        Route::group([
            'domain' => config('tmi-cluster.domain', null),
            'prefix' => config('tmi-cluster.path'),
            'middleware' => config('tmi-cluster.middleware', 'web'),
        ], function () {
            Route::get('', [Controllers\DashboardController::class, 'index']);
            Route::get('statistics', [Controllers\DashboardController::class, 'statistics']);
            Route::get('metrics', [Controllers\MetricsController::class, 'handle']);
        });
    }
}
