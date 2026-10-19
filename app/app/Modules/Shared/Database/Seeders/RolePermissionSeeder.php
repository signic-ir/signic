<?php

declare(strict_types=1);

namespace App\Modules\Shared\Database\Seeders;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\Role;
use App\Modules\Shared\Services\RoleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * The permissions of the platform.
     *
     * @var array<string, string>
     */
    protected array $permissions = [
        'attendee.create' => 'Create new attendees',
        'attendee.view' => 'View attendee information',
        'exhibitor.lead.create' => 'Create leads for exhibitors',
        'exhibitor.lead.view' => 'View exhibitor leads',
        'turnstile.operate' => 'Operate turnstile check-in/out',
        'user.manage' => 'Manage system users',
    ];

    /**
     * The roles of the platform.
     *
     * @var array<string, string>
     */
    protected array $roles = [
        'super_admin' => 'Full system access',
        'event_organizer' => 'Manage events and attendees',
        'exhibitor' => 'Manage exhibitor leads',
        'staff' => 'Event operations staff',
    ];

    /**
     * The permissions granted to every role.
     *
     * @var array<string, array<int, string>>
     */
    protected array $rolePermissions = [
        'event_organizer' => ['attendee.create', 'attendee.view'],
        'exhibitor' => ['exhibitor.lead.create', 'exhibitor.lead.view'],
        'staff' => ['turnstile.operate', 'attendee.view'],
    ];

    public function run(): void
    {
        foreach ($this->permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web', 'description' => $description, 'is_system' => true]
            );
        }

        foreach ($this->roles as $name => $description) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                [
                    'guard_name' => 'web',
                    'description' => $description,
                    'is_system' => true,
                    'inherits_all' => $name === RoleService::SUPER_ADMIN,
                ]
            );

            if ($name === RoleService::SUPER_ADMIN && ! $role->inherits_all) {
                $role->forceFill(['inherits_all' => true])->save();
            }
        }

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->all();

            $role->permissions()->syncWithoutDetaching($permissionIds);
            $role->unsetRelation('permissions');
        }

        // The super admin inherits the permissions of every other role.
        app(RoleService::class)->syncSuperAdmin();

        $this->seedAdministrator();
    }

    /**
     * Make sure an administrator exists to operate the platform.
     */
    protected function seedAdministrator(): void
    {
        $phone = '+15551234567';

        if (User::where('phone', $phone)->exists()) {
            return;
        }

        $admin = User::create([
            'name' => 'System Administrator',
            'phone' => $phone,
            'email' => 'admin@signic.dev',
            'password' => Hash::make('admin123'),
            'status' => 'active',
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);

        $admin->assignRole(RoleService::SUPER_ADMIN);
    }
}
