<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;

// Trust proxy hops so `$request->ip()` keys the `api` and `otp` rate
// limiters on the real client. Behind ArvanCloud + nginx, the default
// (trusting nothing) collapses every user into one bucket. Defaults to
// private ranges + localhost; append ArvanCloud's public edge IP range.
TrustProxies::at([
    '127.0.0.1', '::1',
    '10.0.0.0', '10.255.255.255',
    '172.16.0.0', '172.31.255.255',
    '192.168.0.0', '192.168.255.255',
]);

$app = Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\EventServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Modules\Identity\Providers\IdentityServiceProvider::class,
        App\Modules\Registration\Providers\RegistrationServiceProvider::class,
        App\Modules\AccessControl\Providers\AccessControlServiceProvider::class,
        App\Modules\Exhibition\Providers\ExhibitionServiceProvider::class,
        App\Modules\Shared\Providers\SharedServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth'           => App\Http\Middleware\Authenticate::class,
            'guest'          => App\Http\Middleware\RedirectIfAuthenticated::class,
            'throttle'       => Illuminate\Routing\Middleware\ThrottleRequests::class,
            'can'            => Illuminate\Auth\Middleware\Authorize::class,
            'signed'         => Illuminate\Routing\Middleware\ValidateSignature::class,
        ]);
        $middleware->throttleApi('api');
        $middleware->prepend([
            App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

// Set the namespace explicitly for this non-standard project structure
$app->namespace = 'App\\';

return $app;