<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'throw'  => false,
        ],
        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],
        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION'),
            'bucket'                  => env('AWS_BUCKET'),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
        ],
        'minio' => [
            'driver'                  => 's3',
            'key'                     => env('MINIO_ACCESS_KEY', 'admin'),
            'secret'                  => env('MINIO_SECRET_KEY', 'minioadmin'),
            'region'                  => 'us-east-1',
            'bucket'                  => env('MINIO_BUCKET', 'signic-uploads'),
            'url'                     => env('MINIO_URL'),
            'endpoint'                => env('MINIO_ENDPOINT', 'http://minio:9000'),
            'use_path_style_endpoint' => true,
            'throw'                   => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
