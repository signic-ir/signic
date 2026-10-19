<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Controllers;

use App\Modules\Shared\Http\Resources\PermissionResource;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(protected RoleService $roles) {}

    /**
     * List every permission together with the roles holding it.
     */
    public function index(Request $request): JsonResponse
    {
        $permissions = Permission::query()->orderBy('id')->get()
            ->map(fn (Permission $permission) => PermissionResource::make($permission)->resolve($request))
            ->all();

        return response()->json([
            'success' => true,
            'permissions' => $permissions,
            'data' => $permissions,
        ]);
    }

    /**
     * Create a permission. Creating a permission that already exists is a no-op.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_system' => ['nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);
        $permission = Permission::where('name', $name)->first();
        $created = false;

        if ($permission) {
            $permission->fill([
                'description' => $validated['description'] ?? $permission->description,
            ]);
            $permission->save();
        } else {
            $permission = Permission::create([
                'name' => $name,
                'guard_name' => 'web',
                'description' => $validated['description'] ?? null,
                'is_system' => (bool) ($validated['is_system'] ?? false),
            ]);
            $created = true;
        }

        $this->roles->syncSuperAdmin();

        $payload = PermissionResource::make($permission->refresh())->resolve($request);

        return response()->json([
            'success' => true,
            'message' => $created ? 'Permission created successfully' : 'Permission already exists',
            'permission' => $payload,
            'data' => $payload,
        ], $created ? 201 : 200);
    }

    /**
     * Check whether a user holds a permission.
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permission' => ['required', 'string', 'max:191'],
            'user_id' => ['nullable'],
        ]);

        $identifier = $validated['user_id'] ?? $request->user()?->id;

        if ($identifier === null) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user = $this->roles->findUser($identifier, $request->user());

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $allowed = $this->roles->userCan($user, (string) $validated['permission']);

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'permission' => (string) $validated['permission'],
            'allowed' => $allowed,
            'has_permission' => $allowed,
        ]);
    }
}
