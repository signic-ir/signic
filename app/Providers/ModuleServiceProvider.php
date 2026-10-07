<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Load module service providers
        $this->loadModuleServiceProviders();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerEventListeners();
    }

    /**
     * Load service providers from each module
     */
    protected function loadModuleServiceProviders(): void
    {
        $modules = [
            'Identity',
            'Registration',
            'AccessControl',
            'Exhibition',
            'Shared',
        ];

        foreach ($modules as $module) {
            $moduleProviderPath = base_path("app/Modules/{$module}/Providers/{$module}ServiceProvider.php");

            if (file_exists($moduleProviderPath)) {
                $this->app->register($moduleProviderPath);
            }
        }
    }

    /**
     * Register event listeners for domain events.
     */
    protected function registerEventListeners(): void
    {
        $events = $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class);

        // Listen for registration events
        $events->listen(
            \App\Modules\Registration\Events\AttendeeRegistered::class,
            function ($event) {
                \Log::info('Attendee registered', [
                    'attendee_id' => $event->attendee->id,
                    'user_id' => $event->registeredBy->id,
                ]);

                // Dispatch print job
                \App\Modules\Registration\Jobs\PrintBadgeJob::dispatch($event->attendee);
            }
        );

        // Listen for check-in events
        $events->listen(
            \App\Modules\AccessControl\Events\CheckInEvent::class,
            function ($event) {
                \Log::info('Attendee checked in', $event->checkInData);
            }
        );

        // Listen for check-out events
        $events->listen(
            \App\Modules\AccessControl\Events\CheckOutEvent::class,
            function ($event) {
                \Log::info('Attendee checked out', $event->checkOutData);
            }
        );
    }
}