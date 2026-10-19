<?php

declare(strict_types=1);

namespace App\Modules\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Keep the roles of the system in sync with a permission that entered it.
     */
    protected static function booted(): void
    {
        static::created(function (self $permission) {
            app(\App\Modules\Shared\Services\RoleService::class)->permissionCreated($permission);
        });
    }

    /**
     * The roles holding this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role', 'permission_id', 'role_id')
            ->withTimestamps();
    }

    /**
     * The users holding this permission directly.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Identity\Models\User::class, 'permission_user', 'permission_id', 'user_id')
            ->withTimestamps();
    }
}
