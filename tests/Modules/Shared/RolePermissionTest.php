<?php

namespace Modules\Shared;

use App.Modules\Shared\Models\Role;
use App.Modules\Shared\Models\Permission;
use PHPUnit\Framework\TestCase;

class RolePermissionTest extends TestCase
{
    public function test_role_model_has_name_and_guard(): void
    {
        $role = new Role();
        $this->assertIsString($role->name);
        $this->assertIsString($role->guard_name);
    }

    public function test_permission_model_has_name_and_guard(): void
    {
        $permission = new Permission();
        $this->assertIsString($permission->name);
        $this->assertIsString($permission->guard_name);
    }

    public function test_role_can_have_many_permissions(): void
    {
        $role = new Role();
        $permissions = $role->permissions();
        $this->assertNotNull($permissions);
    }
}