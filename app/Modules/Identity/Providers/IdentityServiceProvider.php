<?php

declare(strict_types=1);

namespace App\Modules\Identity\Providers;

use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module-specific bindings
        $this->registerBindings();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/Lang', 'identity');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'identity');
    }

    /**
     * Register module bindings.
     */
    protected function registerBindings(): void
    {
        // SMS Adapter bindings
        $this->app->bind(
            \App\Modules\Identity\Contracts\SMSAdapterInterface::class,
            fn () => $this->app->make(config('identity.sms_driver', \App\Modules\Identity\Adapters\SMS\NullSMSAdapter::class))
        );
    }
}