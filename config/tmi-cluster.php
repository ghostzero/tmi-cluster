<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TMI Cluster Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where TMI Cluster will be accessible from. If this
    | setting is null, TMI Cluster will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | TMI Cluster Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where TMI Cluster will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => 'tmi-cluster',

    /*
    |--------------------------------------------------------------------------
    | TMI Cluster Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where TMI Cluster will store the
    | meta information required for it to function. It includes the list
    | of supervisors, metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | TMI Cluster Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all TMI Cluster data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of TMI Cluster on the same server so that they don't have problems.
    |
    */

    'prefix' => env('TMI_CLUSTER_PREFIX', 'tmi-cluster:'),

    /*
    |--------------------------------------------------------------------------
    | TMI Cluster Fast Termination
    |--------------------------------------------------------------------------
    |
    | Here you may specify if the supervisor should wait for all its processes
    | to terminate. We recommend to wait before terminate the supervisor.
    |
    | On Docker we have bad experience with the shutdown handler. So there we
    | recommend a fast termination. This will skip the evacuation.
    |
    */

    'fast_termination' => env('TMI_CLUSTER_FAST_TERMINATION', false),

    /*
    |--------------------------------------------------------------------------
    | TMI Client Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the TMI configuration for your TMI Cluster, which
    | will be used by the TMI Cluster. We have gone ahead and set this
    | to a sensible default for you out of the box.
    |
    */

    'tmi' => [
        'options' => ['debug' => false],
        'connection' => [
            'reconnect' => false,
            'rejoin' => true,
        ],
        'identity' => [
            'username' => env('TMI_IDENTITY_USERNAME'),
            'password' => env('TMI_IDENTITY_PASSWORD'),
        ],
        'channels' => []
    ],

    /*
    |--------------------------------------------------------------------------
    | TMI Cluster Auto Scaling Thresholds
    |--------------------------------------------------------------------------
    |
    | Here you can specify all auto scaling thresholds. Depending on the size
    | of your cluster it is recommended to adjust the thresholds. For more
    | information consult our documentation.
    |
    */

    'auto_scale' => [
        'processes' => [
            'min' => 2,
            'max' => 25
        ],
        'thresholds' => [
            'channels' => 50,
            'scale_in' => 50,
            'scale_out' => 70,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | TMI Cluster Auto Cleanup
    |--------------------------------------------------------------------------
    |
    | The Auto Cleanup automatically parts channels that are offline. This
    | feature uses the romanzipp/laravel-twitch library, please configure
    | your Laravel project. Before you enable this function.
    |
    | See: https://github.com/romanzipp/Laravel-Twitch
    |
    */

    'auto_cleanup' => [
        'enabled' => false,
        'interval' => 300,
    ],

];
