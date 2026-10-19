<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Controllers;

use App\Modules\Shared\Http\Resources\RoleResource;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\Role;
use App\Modules\Shared\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(protected RoleService $roles) {}

    /**
     * List every role together with the permissions it grants.
     */
    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()->orderBy('id')->get()
            ->map(fn (Role $role) => RoleResource::make($role)->resolve($request))
            ->all();

        return response()->json([
            'success' => true,
            'roles' => $roles,
            'data' => $roles,
        ]);
    }

    /**
     * Create a role. Creating a role that already exists is a no-op.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_role_id' => ['nullable', 'integer'],
            'parent' => ['nullable', 'string', 'max:191'],
            'is_system' => ['nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);
        $isSuperAdmin = $name === RoleService::SUPER_ADMIN;

        $role = Role::where('name', $name)->first();
        $created = false;

        if ($role) {
            $role->fill([
                'description' => $validated['description'] ?? $role->description,
            ]);
            $role->save();
        } else {
            $role = Role::create([
                'name' => $name,
                'guard_name' => 'web',
                'description' => $validated['description'] ?? null,
                'is_system' => (bool) ($validated['is_system'] ?? $isSuperAdmin),
                'inherits_all' => $isSuperAdmin,
            ]);
            $created = true;
        }

        $parentId = $this->resolveParentId($validated);

        if ($parentId !== null && (int) $role->parent_role_id !== $parentId) {
            $role->parent_role_id = $parentId;
            $role->save();
        }

        $this->roles->applyDefaultPermissions($role);
        $this->roles->syncSuperAdmin();

        $payload = RoleResource::make($role->refresh())->resolve($request);

        return response()->json([
            'success' => true,
            'message' => $created ? 'Role created successfully' : 'Role already exists',
            'role' => $payload,
            'data' => $payload,
        ], $created ? 201 : 200);
    }

    /**
     * Show a single role, addressed by its id or by its name.
     */
    public function show(Request $request, string $role): JsonResponse
    {
        $model = $this->roles->findRole($role);

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $payload = RoleResource::make($model)->resolve($request);

        return response()->json([
            'success' => true,
            'role' => $payload,
            'data' => $payload,
        ]);
    }

    /**
     * Attach a permission to a role.
     */
    public function storePermission(Request $request, string $role): JsonResponse
    {
        $model = $this->roles->findRole($role);

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $validated = $request->validate([
            'permission' => ['nullable', 'string', 'max:191'],
            'permission_name' => ['nullable', 'string', 'max:191'],
            'permission_id' => ['nullable', 'integer'],
        ]);

        $identifier = $validated['permission'] ?? $validated['permission_name'] ?? $validated['permission_id'] ?? null;

        if ($identifier === null || $identifier === '') {
            return response()->json([
                'success' => false,
                'message' => 'A permission name or a permission_id is required.',
            ], 422);
        }

        $permission = $this->roles->findPermission($identifier);

        if (! $permission) {
            if (ctype_digit((string) $identifier)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found',
                ], 404);
            }

            $permission = Permission::firstOrCreate(
                ['name' => (string) $identifier],
                ['guard_name' => 'web', 'description' => null, 'is_system' => false]
            );
        }

        $attached = $this->roles->assignPermission($model, $permission);

        $payload = RoleResource::make($model->refresh())->resolve($request);

        return response()->json([
            'success' => true,
            'message' => 'Permission assigned to role',
            'role' => $payload,
            'data' => $payload,
        ], $attached ? 201 : 200);
    }

    /**
     * Detach a permission from a role.
     */
    public function destroyPermission(Request $request, string $role, string $permission): JsonResponse
    {
        $model = $this->roles->findRole($role);

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $permissionModel = $this->roles->findPermission($permission);

        if (! $permissionModel) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }

        $this->roles->removePermission($model, $permissionModel);

        $payload = RoleResource::make($model->refresh())->resolve($request);

        return response()->json([
            'success' => true,
            'message' => 'Permission removed from role',
            'role' => $payload,
            'data' => $payload,
        ]);
    }

    /**
     * Make a role inherit from another role.
     */
    public function inherit(Request $request, string $role): JsonResponse
    {
        $model = $this->roles->findRole($role);

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $validated = $request->validate([
            'parent_role_id' => ['nullable', 'integer'],
            'parent' => ['nullable', 'string', 'max:191'],
        ]);

        $parentId = $this->resolveParentId($validated);

        if ($parentId === null) {
            return response()->json([
                'success' => false,
                'message' => 'A parent_role_id or a parent role name is required.',
            ], 422);
        }

        $model->parent_role_id = $parentId;
        $model->save();

        $payload = RoleResource::make($model->refresh())->resolve($request);

        return response()->json([
            'success' => true,
            'message' => 'Role inheritance updated',
            'role' => $payload,
            'data' => $payload,
        ]);
    }

    /**
     * Make a role inherit the permissions of every other role.
     */
    public function inheritAll(Request $request, string $role): JsonResponse
    {
        $model = $this->roles->findRole($role);

        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $model->inherits_all = true;
        $model->save();

        $this->roles->syncSuperAdmin();

        $payload = RoleResource::make($model->refresh())->resolve($request);

        return response()->json([
            'success' => true,
            'message' => 'The role now inherits the permissions of every other role',
            'role' => $payload,
            'data' => $payload,
        ]);
    }

    /**
     * Resolve the parent role out of the validated payload.
     *
     * @param  array<string, mixed>  $validated
     */
    protected function resolveParentId(array $validated): ?int
    {
        if (! empty($validated['parent_role_id'])) {
            return (int) $validated['parent_role_id'];
        }

        if (! empty($validated['parent'])) {
            return $this->roles->findRole((string) $validated['parent'])?->id;
        }

        return null;
    }
}
