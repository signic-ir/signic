<?php

declare(strict_types=1);

namespace App\Modules\Shared\Tests;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\Role;
use App\Modules\Shared\Services\RoleService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

class CoreServiceTest extends BaseTestCase
{
    use DatabaseTransactions;

    /**
     * Boot the application for the test.
     *
     * The tests run against an isolated in-memory database so that they
     * never touch the database of the platform.
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The schema of the testing database is built once per run. Only the
        // migrations of the Identity and of the Shared module are applied.
        Artisan::call('migrate', [
            '--force' => true,
            '--path' => [
                'app/Modules/Identity/Database/Migrations',
                'app/Modules/Shared/Database/Migrations',
            ],
        ]);
    }

    public function test_a_role_holds_the_permissions_granted_to_it(): void
    {
        $role = Role::create(['name' => 'event_organizer', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'attendee.create', 'guard_name' => 'web']);

        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $this->assertTrue($role->fresh()->permissions->contains('id', $permission->id));
    }

    public function test_a_user_inherits_the_permissions_of_their_role(): void
    {
        $user = $this->user('+989120000000');

        $user->assignRole('event_organizer');

        $permission = Permission::firstOrCreate(
            ['name' => 'attendee.create'],
            ['guard_name' => 'web']
        );

        app(RoleService::class)->assignPermission(
            Role::where('name', 'event_organizer')->firstOrFail(),
            $permission
        );

        $this->assertTrue($user->fresh()->hasPermissionTo('attendee.create'));
    }

    public function test_a_super_admin_holds_every_permission_of_the_system(): void
    {
        $user = $this->user('+989120000001');

        $user->assignRole('super_admin');

        Permission::create(['name' => 'exhibitor.lead.create', 'guard_name' => 'web']);

        $this->assertTrue($user->fresh()->hasPermissionTo('exhibitor.lead.create'));
    }

    public function test_a_user_is_denied_a_permission_their_role_does_not_hold(): void
    {
        $user = $this->user('+989120000002');

        $user->assignRole('staff');

        Permission::create(['name' => 'exhibitor.lead.create', 'guard_name' => 'web']);

        $this->assertFalse($user->fresh()->hasPermissionTo('exhibitor.lead.create'));
    }

    protected function user(string $phone): User
    {
        return User::create([
            'phone' => $phone,
            'name' => 'Test User',
            'status' => 'active',
        ]);
    }
}
