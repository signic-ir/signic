<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Controllers;

use App\Modules\Shared\Http\Resources\RoleResource;
use App\Modules\Shared\Models\Role;
use App\Modules\Shared\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    public function __construct(protected RoleService $roles) {}

    /**
     * Show a user together with the roles and the permissions they hold.
     */
    public function show(Request $request, string $user): JsonResponse
    {
        $model = $this->roles->findUser($user, $request->user());

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $roles = $model->roles()->orderBy('roles.id')->get()
            ->map(fn (Role $role) => RoleResource::make($role)->resolve($request))
            ->all();

        $permissions = $this->roles->effectivePermissionNames($model);

        return response()->json([
            'success' => true,
            'user_id' => $model->id,
            'user' => [
                'id' => $model->id,
                'name' => $model->name,
                'phone' => $model->phone,
                'email' => $model->email,
            ],
            'roles' => $roles,
            'role_names' => $model->roles()->pluck('name')->values()->all(),
            'permissions' => $permissions,
            'data' => [
                'user_id' => $model->id,
                'roles' => $roles,
                'permissions' => $permissions,
            ],
        ]);
    }

    /**
     * Show the roles a user holds and the permissions they grant.
     */
    public function index(Request $request, string $user): JsonResponse
    {
        $model = $this->roles->findUser($user, $request->user());

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $roles = $model->roles()->orderBy('roles.id')->get()
            ->map(fn (Role $role) => RoleResource::make($role)->resolve($request))
            ->all();

        $permissions = $this->roles->effectivePermissionNames($model);

        return response()->json([
            'success' => true,
            'user_id' => $model->id,
            'roles' => $roles,
            'role_names' => $model->roles()->pluck('name')->values()->all(),
            'permissions' => $permissions,
            'data' => [
                'user_id' => $model->id,
                'roles' => $roles,
                'permissions' => $permissions,
            ],
        ]);
    }

    /**
     * Assign a role to a user, creating the role when it is missing.
     */
    public function store(Request $request, string $user): JsonResponse
    {
        $model = $this->roles->findUser($user, $request->user());

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'role' => ['nullable', 'string', 'max:191'],
            'role_name' => ['nullable', 'string', 'max:191'],
            'role_id' => ['nullable', 'integer'],
        ]);

        $identifier = $validated['role'] ?? $validated['role_name'] ?? $validated['role_id'] ?? null;

        if ($identifier === null || $identifier === '') {
            return response()->json([
                'success' => false,
                'message' => 'A role name or a role_id is required.',
            ], 422);
        }

        if (ctype_digit((string) $identifier)) {
            $role = $this->roles->findRole($identifier);

            if (! $role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found',
                ], 404);
            }

            $model->roles()->syncWithoutDetaching([$role->id]);
        } else {
            $model->assignRole((string) $identifier);

            $role = $this->roles->findRole((string) $identifier);
        }

        if ($role) {
            $this->roles->applyDefaultPermissions($role);
        }

        $model->unsetRelation('roles');

        $this->roles->syncSuperAdmin();

        $roles = $model->roles()->orderBy('roles.id')->get()
            ->map(fn (Role $item) => RoleResource::make($item)->resolve($request))
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Role assigned to user',
            'user_id' => $model->id,
            'roles' => $roles,
            'role_names' => $model->roles()->pluck('name')->values()->all(),
            'data' => [
                'user_id' => $model->id,
                'roles' => $roles,
            ],
        ]);
    }

    /**
     * Remove a role from a user.
     */
    public function destroy(Request $request, string $user, string $role): JsonResponse
    {
        $model = $this->roles->findUser($user, $request->user());

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $roleModel = $this->roles->findRole($role);

        if (! $roleModel) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $model->roles()->detach($roleModel->id);
        $model->unsetRelation('roles');

        $roles = $model->roles()->orderBy('roles.id')->get()
            ->map(fn (Role $item) => RoleResource::make($item)->resolve($request))
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Role removed from user',
            'user_id' => $model->id,
            'roles' => $roles,
            'role_names' => $model->roles()->pluck('name')->values()->all(),
            'data' => [
                'user_id' => $model->id,
                'roles' => $roles,
            ],
        ]);
    }

    /**
     * Check whether a user holds a permission.
     */
    public function checkPermission(Request $request, string $user, string $permission): JsonResponse
    {
        $model = $this->roles->findUser($user, $request->user());

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $allowed = $this->roles->userCan($model, $permission);

        return response()->json([
            'success' => true,
            'user_id' => $model->id,
            'permission' => $permission,
            'allowed' => $allowed,
            'has_permission' => $allowed,
        ]);
    }
}
