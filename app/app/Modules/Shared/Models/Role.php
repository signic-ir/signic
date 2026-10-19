<?php

declare(strict_types=1);

namespace App\Modules\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'is_system',
        'inherits_all',
        'parent_role_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'inherits_all' => 'boolean',
    ];

    /**
     * The legacy `permissions` JSON column of the roles table would shadow
     * the `permissions()` relation, so it is dropped from the attributes of
     * every model the role table produces. The relation is the only source
     * of truth for the permissions of a role.
     */
    protected static function booted(): void
    {
        $dropLegacyColumn = static function (self $role): void {
            $role->offsetUnset('permissions');
        };

        static::retrieved($dropLegacyColumn);
        static::created($dropLegacyColumn);
    }

    /**
     * The parent role this role inherits permissions from.
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_role_id');
    }

    /**
     * The roles that inherit from this role.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_role_id');
    }

    /**
     * The permissions granted directly to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    /**
     * The users holding this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Identity\Models\User::class, 'role_user', 'role_id', 'user_id')
            ->withTimestamps();
    }
}
