<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Event::class => \App\Policies\EventPolicy::class,
        \App\Models\Attendee::class => \App\Policies\AttendeePolicy::class,
        \App\Models\Turnstile::class => \App\Policies\TurnstilePolicy::class,
        \App\Models\ScanEvent::class => \App\Policies\ScanEventPolicy::class,
        \App\Models\Exhibitor::class => \App\Policies\ExhibitorPolicy::class,
        \App\Models\Lead::class => \App\Policies\LeadPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('super_admin', fn ($user) => $user->hasRole('super_admin'));
        Gate::define('organizer', fn ($user) => $user->hasRole('organizer'));
        Gate::define('operator', fn ($user) => $user->hasRole('operator'));
        Gate::define('exhibitor', fn ($user) => $user->hasRole('exhibitor'));
        Gate::define('visitor', fn ($user) => $user->hasRole('visitor'));
    }
}