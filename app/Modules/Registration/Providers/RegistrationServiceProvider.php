<?php

declare(strict_types=1);

namespace App\Modules\Registration\Providers;

use Illuminate\Support\ServiceProvider;

class RegistrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerBindings();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }

    protected function registerBindings(): void
    {
        // Bind QR service
        $this->app->bind(
            \App\Modules\Registration\Contracts\QRServiceInterface::class,
            \App\Modules\Registration\Services\QRService::class
        );
    }
}