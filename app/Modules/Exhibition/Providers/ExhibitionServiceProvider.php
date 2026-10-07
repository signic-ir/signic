<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Providers;

use Illuminate\Support\ServiceProvider;

class ExhibitionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Modules\Exhibition\Contracts\LeadServiceInterface::class,
            \App\Modules\Exhibition\Services\LeadService::class
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