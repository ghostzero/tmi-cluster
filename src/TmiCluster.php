<?php

namespace GhostZero\TmiCluster;

use Exception;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use Illuminate\Support\Facades\Route;

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
            throw new Exception("Redis connection [{$connection}] has not been configured.");
        }

        config(['database.redis.tmi-cluster' => array_merge($config, [
            'options' => ['prefix' => config('tmi-cluster.prefix') ?: 'tmi-cluster:'],
        ])]);
    }

    public static function joinNextServer(array $channels): void
    {
        $commandQueue = app(CommandQueue::class);

        $servers = SupervisorProcess::query()
            ->whereTime('last_ping_at', '>', now()->subSeconds(10))
            ->get()->map(function (SupervisorProcess $process) {
                return [
                    'name' => "{$process->getKey()}-input",
                    'channels' => count($process->channels),
                ];
            })->sortBy('channels');

        foreach ($channels as $channel) {
            $servers = $servers->sortBy('channels');
            $nextServer = $servers->shift();
            $nextServer['channels'] += 1;
            $servers->push($nextServer);

            $commandQueue->push($nextServer['name'], CommandQueue::COMMAND_TMI_JOIN, ['channel' => $channel]);
        }
    }

    public static function routes(): void
    {
        Route::middleware('web')
            ->prefix('tmi-cluster')
            ->namespace('GhostZero\\TmiCluster\\Http\\Controllers')
            ->group(function () {
                Route::get('', 'DashboardController@index');
                Route::get('statistics', 'DashboardController@statistics');
            });
    }
}
