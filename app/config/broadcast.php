<?php

return [

    'default' => env('BROADCAST_CONNECTION', 'log'),
    'connections' => [
        'log' => [
            'driver' => 'log',
        ],
        'pusher' => [
            'driver' => 'pusher',
            'app_id' => env('PUSHER_APP_ID'),
            'app_key' => env('PUSHER_APP_KEY'),
            'app_secret' => env('PUSHER_APP_SECRET'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true,
            ],
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
        ],
    ],

];