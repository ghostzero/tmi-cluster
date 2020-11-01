<?php

namespace GhostZero\TmiCluster;

use Exception;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Exceptions\NotFoundSupervisorException;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class TmiCluster
{
    /**
     * The Slack notifications webhook URL.
     */
    public static ?string $slackWebhookUrl = null;

    /**
     * The Slack notifications channel.
     */
    public static ?string $slackChannel = null;

    /**
     * The SMS notifications phone number.
     */
    public static ?string $smsNumber = null;

    /**
     * The email address for notifications.
     */
    public static ?string $email = null;

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

    /**
     * @param array $channels a list of channels that needs to be joined
     * @param array $staleIds a list of supervisors or processes ids, that needs to be avoid
     * @param bool $throwNotFoundException
     */
    public static function joinNextServer(array $channels, array $staleIds = [], bool $throwNotFoundException = false): void
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $supervisors = Models\Supervisor::query()
            ->whereTime('last_ping_at', '>', now()->subSeconds(10))
            ->whereNotIn('id', $staleIds)
            ->get()->map(function (Models\Supervisor $supervisor) {
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
                'type' => 'channels',
                'channels' => $channels,
            ]);
        }

        foreach ($channels as $channel) {
            $supervisors = $supervisors->sortBy('channels');
            $nextSupervisor = $supervisors->shift();
            $nextSupervisor['channels'] += 1;
            $supervisors->push($nextSupervisor);

            $commandQueue->push($nextSupervisor['name'], CommandQueue::COMMAND_TMI_JOIN, [
                'channel' => $channel,
                'stale_ids' => $staleIds,
            ]);
        }
    }

    public static function routeMailNotificationsTo(string $email): void
    {
        static::$email = $email;
    }

    public static function routeSlackNotificationsTo(string $url, string $channel = null)
    {
        static::$slackWebhookUrl = $url;
        static::$slackChannel = $channel;
    }

    public static function routeSmsNotificationsTo(string $number): void
    {
        static::$smsNumber = $number;
    }

    public static function routes(): void
    {
        Route::middleware('web')
            ->prefix('tmi-cluster')
            ->namespace('GhostZero\\TmiCluster\\Http\\Controllers')
            ->group(function () {
                Route::get('', 'DashboardController@index');
                Route::get('statistics', 'DashboardController@statistics');
                Route::get('metrics', 'MetricsController@handle');
            });
    }
}
