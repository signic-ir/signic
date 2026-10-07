<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    /**
     |---------------------------------------------------------------------------
    | Application Route Filters
    |---------------------------------------------------------------------------
     |
     | Here are the route filters that you may apply to your routes.
     | These are in addition to any filters you may have applied
     | in the Laravel default filter code.
     |
     */

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    protected function mapRouter(Router $router): void
    {
        $router->aliasMiddleware('auth', \App\Http\Middleware\Authenticate::class);

        //
    }

    /**
     * Define the routes for the application.
     */
    protected function mapRoutes(): void
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace('App\Http\Controllers')
            ->group(base_path('routes/web.php'));

        // Map module routes
        $this->mapModuleRoutes();
    }

    /**
     * Define the API routes for the application.
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace('App\Http\Controllers\Api')
            ->group(base_path('routes/api.php'));
    }

    /**
     * Map routes for each module
     */
    protected function mapModuleRoutes(): void
    {
        $modules = [
            'Identity',
            'Registration',
            'AccessControl',
            'Exhibition',
            'Shared',
        ];

        foreach ($modules as $module) {
            $moduleRoutesPath = base_path("app/Modules/{$module}/Routes/{$module}Route.php");

            if (file_exists($moduleRoutesPath)) {
                require $moduleRoutesPath;
            }
        }
    }
}