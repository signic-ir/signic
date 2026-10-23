<?php

return [
    'dsn' => env('SENTRY_DSN'),

    'environment' => env('APP_ENV', 'production'),

    'release' => env('SENTRY_RELEASE'),

    'capture_exceptions' => true,

    'before_send' => null,

    'register_error_handler' => true,

    'register_exception_handler' => true,

    'register_error_reporting_handler' => true,

    'tracing' => [
        'enabled' => env('SENTRY_TRACING', false),
        'sample_rate' => (float) env('SENTRY_TRACING_SAMPLE_RATE', 1.0),
    ],

    'integrations' => [
        \Illuminate\Queue\Queue::class,
        \Illuminate\Cache\Repositories\Repository::class,
        \Illuminate\Database\DatabaseManager::class,
    ],
];
