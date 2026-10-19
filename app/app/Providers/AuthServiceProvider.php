<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * Policies are registered per-module in their own ServiceProviders.
     */
    protected $policies = [
        \App\Modules\Identity\Models\User::class        => \App\Modules\Identity\Policies\UserModelPolicy::class,
        \App\Modules\AccessControl\Models\Turnstile::class => \App\Modules\AccessControl\Policies\TurnstilePolicy::class,
        \App\Modules\Exhibition\Models\Exhibitor::class => \App\Modules\Exhibition\Policies\ExhibitorPolicy::class,
        \App\Modules\Exhibition\Models\Lead::class      => \App\Modules\Exhibition\Policies\LeadPolicy::class,
        \App\Modules\Registration\Models\Attendee::class => \App\Modules\Registration\Policies\AttendeePolicy::class,
    ];

    /**
     * The permissions of the platform exposed as gates.
     *
     * @var array<int, string>
     */
    protected array $permissionGates = [
        'attendee.create',
        'attendee.view',
        'exhibitor.lead.create',
        'exhibitor.lead.view',
        'turnstile.operate',
        'user.manage',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('super_admin', fn ($user) => $user->hasRole('super_admin'));
        Gate::define('organizer',   fn ($user) => $user->hasRole('organizer'));
        Gate::define('operator',    fn ($user) => $user->hasRole('operator'));
        Gate::define('exhibitor',   fn ($user) => $user->hasRole('exhibitor'));
        Gate::define('visitor',     fn ($user) => $user->hasRole('visitor'));

        foreach ($this->permissionGates as $permission) {
            Gate::define($permission, fn ($user) => $user->hasPermissionTo($permission));
        }

        // A system administrator passes every ability check.
        Gate::before(fn ($user) => $user->isSystem() ? true : null);
    }
}
