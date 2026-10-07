<?php

declare(strict_types=1);

namespace App\Modules\Shared\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Modules\Identity\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view-users',
            'create-users',
            'update-users',
            'delete-users',

            // Event management
            'view-events',
            'create-events',
            'update-events',
            'delete-events',

            // Attendee management
            'view-attendees',
            'create-attendees',
            'update-attendees',
            'delete-attendees',

            // Access control
            'manage-turnstiles',
            'view-scans',

            // Exhibition
            'view-exhibitors',
            'create-exhibitors',
            'update-exhibitors',
            'delete-exhibitors',
            'view-leads',
            'create-leads',
            'update-leads',
            'delete-leads',
            'export-leads',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $organizer = Role::create([
            'name' => 'organizer',
            'guard_name' => 'web',
        ]);

        $operator = Role::create([
            'name' => 'operator',
            'guard_name' => 'web',
        ]);

        $exhibitor = Role::create([
            'name' => 'exhibitor',
            'guard_name' => 'web',
        ]);

        $visitor = Role::create([
            'name' => 'visitor',
            'guard_name' => 'web',
        ]);

        // Assign permissions to roles
        $superAdmin->givePermissionTo(Permission::all());

        $organizer->givePermissionTo([
            'view-events',
            'create-events',
            'update-events',
            'view-attendees',
            'create-attendees',
            'update-attendees',
            'view-exhibitors',
            'create-exhibitors',
            'view-leads',
            'create-leads',
            'update-leads',
        ]);

        $operator->givePermissionTo([
            'manage-turnstiles',
            'view-scans',
            'view-attendees',
        ]);

        $exhibitor->givePermissionTo([
            'view-exhibitors',
            'create-exhibitors',
            'update-exhibitors',
            'view-leads',
            'create-leads',
            'update-leads',
            'export-leads',
        ]);

        $visitor->givePermissionTo([
            'view-events',
            'view-attendees',
        ]);

        // Create sample users if none exist
        if (User::count() === 0) {
            $admin = User::create([
                'name' => 'System Administrator',
                'phone' => '+15551234567',
                'email' => 'admin@signic.dev',
                'password' => Hash::make('admin123'),
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]);

            $admin->assignRole('super_admin');
        }
    }
}