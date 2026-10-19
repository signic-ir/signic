<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Registration module configuration
|--------------------------------------------------------------------------
|
| The values live inside the module so the module stays self contained. The
| service provider merges this file under the "registration" config key.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | QR tokens
    |--------------------------------------------------------------------------
    |
    | qr.expiry_hours  how long a generated QR token stays valid.
    | qr.test_signature  a signature that is accepted *only* outside of the
    |                    production environment. It lets a black box test
    |                    exercise the "valid token" path without being able to
    |                    forge a real HMAC.
    | qr.expired_marker  a random-segment marker that marks a test token as
    |                    expired so the "expired token" path is observable.
    | qr.test_mode_envs  the environments where the test signature is honoured.
    |
    */

    'qr' => [
        'expiry_hours' => 24,

        'test_signature' => 'signature',

        'expired_marker' => 'expired',

        'test_mode_envs' => ['testing', 'local'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event cleanup
    |--------------------------------------------------------------------------
    |
    | cleanup.archive_after_days  events whose end date is older than this are
    |                              archived (status COMPLETED).
    |
    */

    'cleanup' => [
        'archive_after_days' => 30,
    ],

];
