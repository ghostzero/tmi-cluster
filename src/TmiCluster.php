<?php

namespace GhostZero\TmiCluster;

use Exception;
use GhostZero\TmiCluster\Traits\TmiClusterHelpers;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class TmiCluster
{
    use TmiClusterHelpers;

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
