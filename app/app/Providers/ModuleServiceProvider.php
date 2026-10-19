<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * Module providers are registered directly in bootstrap/app.php.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerEventListeners();
    }

    /**
     * Register event listeners for domain events.
     */
    protected function registerEventListeners(): void
    {
        $events = $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class);

        // Listen for check-in events
        $events->listen(
            \App\Modules\AccessControl\Events\CheckInEvent::class,
            function ($event) {
                \Illuminate\Support\Facades\Log::info('Attendee checked in', $event->checkInData ?? []);
            }
        );

        // Listen for check-out events
        $events->listen(
            \App\Modules\AccessControl\Events\CheckOutEvent::class,
            function ($event) {
                \Illuminate\Support\Facades\Log::info('Attendee checked out', $event->checkOutData ?? []);
            }
        );
    }
}
