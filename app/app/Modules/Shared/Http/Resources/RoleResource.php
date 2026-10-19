<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Resources;

use App\Modules\Shared\Services\RoleService;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request): array
    {
        $permissions = app(RoleService::class)->effectivePermissions($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'guard_name' => $this->guard_name,
            'is_system' => (bool) $this->is_system,
            'inherits_all' => (bool) $this->inherits_all,
            'parent_role_id' => $this->parent_role_id,
            'parent_role' => $this->parent?->name,
            'permissions' => PermissionResource::collection($permissions)->resolve($request),
            'permission_names' => $permissions->pluck('name')->values()->all(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
