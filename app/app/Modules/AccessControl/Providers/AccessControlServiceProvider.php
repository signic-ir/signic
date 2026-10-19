<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Providers;

use Illuminate\Support\ServiceProvider;

class AccessControlServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Modules\AccessControl\Contracts\TurnstileServiceInterface::class,
            \App\Modules\AccessControl\Services\TurnstileService::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}