<?php

declare(strict_types=1);

namespace App\Modules\Shared\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * The role that inherits the permissions of every other role.
     */
    public const SUPER_ADMIN = 'super_admin';

    /**
     * The role every other role may be attached to as its parent.
     */
    public const SUPER_ADMIN_DESCRIPTION = 'Full system access';

    /**
     * Guards the super admin synchronisation against re-entrancy.
     */
    protected bool $syncing = false;

    /**
     * Resolve the super admin role, creating it when it is missing.
     */
    public function superAdmin(): Role
    {
        $role = Role::firstOrCreate(
            ['name' => self::SUPER_ADMIN],
            [
                'guard_name' => 'web',
                'description' => self::SUPER_ADMIN_DESCRIPTION,
                'is_system' => true,
                'inherits_all' => true,
            ]
        );

        if (! $role->inherits_all) {
            $role->forceFill(['inherits_all' => true])->save();
        }

        return $role;
    }

    /**
     * Give the super admin role every permission known to the system.
     *
     * The pivot rows are kept in sync so a super admin holds every
     * permission, including the ones created after the role itself.
     */
    public function syncSuperAdmin(): Role
    {
        if ($this->syncing) {
            return $this->superAdmin();
        }

        $this->syncing = true;

        try {
            $role = $this->superAdmin();

            $role->permissions()->syncWithoutDetaching(Permission::query()->pluck('id')->all());

            $role->unsetRelation('permissions');

            return $role;
        } finally {
            $this->syncing = false;
        }
    }

    /**
     * The permissions a role holds by default.
     *
     * @return array<int, string>
     */
    public function defaultPermissionNames(Role $role): array
    {
        return (array) config('shared.role_permissions.' . $role->name, []);
    }

    /**
     * Grant a role the permissions it holds by default.
     */
    public function applyDefaultPermissions(Role $role): Role
    {
        $names = $this->defaultPermissionNames($role);

        if ($names === []) {
            return $role;
        }

        $permissionIds = Permission::whereIn('name', $names)->pluck('id')->all();

        if ($permissionIds === []) {
            return $role;
        }

        $role->permissions()->syncWithoutDetaching($permissionIds);
        $role->unsetRelation('permissions');

        return $role;
    }

    /**
     * Grant a permission to every role that holds it by default.
     */
    public function applyDefaultPermissionsFor(Permission $permission): void
    {
        $mapping = (array) config('shared.role_permissions', []);

        if ($mapping === []) {
            return;
        }

        $roles = Role::whereIn('name', array_keys($mapping))->get();

        foreach ($roles as $role) {
            if (! in_array($permission->name, $this->defaultPermissionNames($role), true)) {
                continue;
            }

            $role->permissions()->syncWithoutDetaching([$permission->id]);
            $role->unsetRelation('permissions');
        }
    }

    /**
     * React to a permission that entered the system.
     */
    public function permissionCreated(Permission $permission): void
    {
        $this->applyDefaultPermissionsFor($permission);

        $this->syncSuperAdmin();
    }

    /**
     * The permissions a role grants, including the inherited ones.
     *
     * A role flagged as `inherits_all` grants every permission of the
     * system, which is how the super admin inherits from all other roles.
     *
     * @return Collection<int, Permission>
     */
    public function effectivePermissions(Role $role): Collection
    {
        if ($role->inherits_all) {
            return Permission::query()->orderBy('id')->get();
        }

        $permissionIds = DB::table('permission_role')
            ->whereIn('role_id', $this->ancestorIds($role))
            ->pluck('permission_id')
            ->all();

        return Permission::query()->whereIn('id', $permissionIds)->orderBy('id')->get();
    }

    /**
     * The ids of a role and of every role it inherits from.
     *
     * @return array<int, int>
     */
    public function ancestorIds(Role $role): array
    {
        $ids = [(int) $role->id];
        $current = $role;
        $guard = 0;

        while ($current->parent_role_id && $guard < 20) {
            $parent = Role::find($current->parent_role_id);

            if (! $parent || in_array((int) $parent->id, $ids, true)) {
                break;
            }

            $ids[] = (int) $parent->id;
            $current = $parent;
            $guard++;
        }

        return $ids;
    }

    /**
     * The names of every permission a user effectively holds.
     *
     * Direct grants are combined with the permissions of every role the user
     * holds, including the ones inherited through the role hierarchy.
     *
     * @return array<int, string>
     */
    public function effectivePermissionNames(User $user): array
    {
        $names = $user->permissions->pluck('name')->all();

        foreach ($user->effectiveRoles() as $role) {
            $names = array_merge($names, $this->effectivePermissions($role)->pluck('name')->all());
        }

        return array_values(array_unique($names));
    }

    /**
     * Determine whether a user may perform an action guarded by a permission.
     */
    public function userCan(User $user, string $permission): bool
    {
        if ($user->hasPermissionTo($permission)) {
            return true;
        }

        if ($user->isSystem()) {
            return true;
        }

        foreach ($user->effectiveRoles() as $role) {
            if ($role->inherits_all) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attach a permission to a role.
     *
     * Returns true when the permission was not attached before.
     */
    public function assignPermission(Role $role, Permission $permission): bool
    {
        $attached = $role->permissions()->where('permissions.id', $permission->id)->exists();

        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $role->unsetRelation('permissions');

        $this->syncSuperAdmin();

        return ! $attached;
    }

    /**
     * Detach a permission from a role.
     */
    public function removePermission(Role $role, Permission $permission): void
    {
        $role->permissions()->detach($permission->id);
        $role->unsetRelation('permissions');
    }

    /**
     * Find a role by its numeric id or by its name.
     */
    public function findRole(string|int $identifier): ?Role
    {
        $identifier = (string) $identifier;

        if (ctype_digit($identifier)) {
            $role = Role::find((int) $identifier);

            if ($role) {
                return $role;
            }
        }

        $role = Role::where('name', $identifier)->first();

        if ($role) {
            return $role;
        }

        $normalized = $this->normalize($identifier);

        return Role::query()->get()->first(
            fn (Role $candidate) => $this->normalize($candidate->name) === $normalized
        );
    }

    /**
     * Find a permission by its numeric id or by its name.
     */
    public function findPermission(string|int $identifier): ?Permission
    {
        $identifier = (string) $identifier;

        if (ctype_digit($identifier)) {
            $permission = Permission::find((int) $identifier);

            if ($permission) {
                return $permission;
            }
        }

        $permission = Permission::where('name', $identifier)->first();

        if ($permission) {
            return $permission;
        }

        $normalized = $this->normalize($identifier);

        return Permission::query()->get()->first(
            fn (Permission $candidate) => $this->normalize($candidate->name) === $normalized
        );
    }

    /**
     * Find a user by its numeric id, its phone number or its email address.
     *
     * The identifier "me" resolves to the caller.
     */
    public function findUser(string|int $identifier, ?User $caller = null): ?User
    {
        $identifier = (string) $identifier;

        if (in_array(strtolower($identifier), ['me', 'self', 'current'], true)) {
            return $caller;
        }

        if (ctype_digit($identifier)) {
            $user = User::find((int) $identifier);

            if ($user) {
                return $user;
            }
        }

        $user = User::where('phone', $identifier)->first();

        if ($user) {
            return $user;
        }

        $user = User::where('email', $identifier)->first();

        if ($user) {
            return $user;
        }

        $digits = preg_replace('/\D+/', '', $identifier);

        if ($digits === '') {
            return null;
        }

        return User::query()
            ->where('phone', 'like', '%' . $digits)
            ->get()
            ->first(fn (User $candidate) => preg_replace('/\D+/', '', (string) $candidate->phone) === $digits);
    }

    /**
     * Normalize a name so `attendee.create` and `attendee-create` match.
     */
    protected function normalize(string $value): string
    {
        return strtolower(str_replace(['-', ' ', '.'], '_', trim($value)));
    }
}
