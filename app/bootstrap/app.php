<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
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
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
