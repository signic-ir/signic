<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Resources;

use App\Modules\Shared\Models\Role;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray($request): array
    {
        $roles = $this->roles()->get();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'guard_name' => $this->guard_name,
            'is_system' => (bool) $this->is_system,
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => (bool) $role->is_system,
            ])->values()->all(),
            'role_names' => $roles->pluck('name')->values()->all(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
