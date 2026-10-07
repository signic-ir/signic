<?php
/**
 * Laravel - A PHP Framework For Web Artisans
 */

// Load autoloader
require __DIR__.'/../vendor/autoload.php';

// Instantiate the application
$app = Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\ModuleServiceProvider::class,
        \App\Providers\AuthServiceProvider::class,
        \App\Providers\EventServiceProvider::class,
        \App\Providers\RouteServiceProvider::class,
        \App\Modules\Identity\Providers\IdentityServiceProvider::class,
        \App\Modules\Registration\Providers\RegistrationServiceProvider::class,
        \App\Modules\AccessControl\Providers\AccessControlServiceProvider::class,
        \App\Modules\Exhibition\Providers\ExhibitionServiceProvider::class,
        \App\Modules\Shared\Providers\SharedServiceProvider::class,
        \App\Modules\Shared\Providers\SharedServiceProvider::class,
    ])
    ->create();

// Bind the kernel
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

// Handle the request
$request = Illuminate\Http\Request::capture();

$response = $app->handle($request);

$response->send();

// Terminate
$app->terminate($request, $response);
