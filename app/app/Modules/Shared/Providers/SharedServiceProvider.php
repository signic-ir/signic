<?php

declare(strict_types=1);

namespace App\Modules\Shared\Providers;

use App\Modules\Shared\Console\Commands\SyncRolesCommand;
use App\Modules\Shared\Services\RoleService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SharedServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/shared.php', 'shared');

        $this->app->singleton(RoleService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([SyncRolesCommand::class]);
        }

        $this->renderPermissionDenied();
    }

    /**
     * Render authorization failures with a message naming the permission.
     *
     * The default Laravel message ("This action is unauthorized.") does not
     * tell the caller that a permission was missing, which is what the API
     * clients of the platform expect to read.
     */
    protected function renderPermissionDenied(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Permission denied. This action is unauthorized.',
            ], 403);
        });
    }
}
