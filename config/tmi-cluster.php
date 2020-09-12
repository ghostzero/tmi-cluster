<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TMI Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the tmi configuration for your tmi cluster, which
    | will be used by the tmi cluster. We have gone ahead and set this
    | to a sensible default for you out of the box.
    |
    */

    'tmi' => [
        'options' => ['debug' => false],
        'connection' => [
            'secure' => true,
            'reconnect' => true,
            'rejoin' => true,
        ],
        'identity' => [
            'username' => env('TMI_IDENTITY_USERNAME', 'ghostzero'),
            'password' => env('TMI_IDENTITY_PASSWORD', 'oauth:...'),
        ],
        'channels' => ['ghostzero', 'twitchcologne']
    ],
];
