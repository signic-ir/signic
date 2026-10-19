<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Exhibition\Models\ExhibitorMember;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'phone',
        'name',
        'email',
        'password',
        'avatar',
        'status',
        'phone_verified_at',
        'email_verified_at',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password' => 'hashed',
        'metadata' => 'array',
    ];

    public function toResource(?string $resourceClass = null): JsonResource
    {
        return parent::toResource($resourceClass);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user', 'user_id', 'permission_id')
            ->withTimestamps();
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    public function exhibitorMemberships(): HasMany
    {
        return $this->hasMany(ExhibitorMember::class);
    }

    /**
     * Determine whether the user holds the given role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->contains(fn (Role $r) => $r->name === $role);
    }

    /**
     * Assign a role to the user by name.
     */
    public function assignRole(string $role): Role
    {
        $model = Role::firstOrCreate(
            ['name' => $role],
            ['guard_name' => 'web']
        );

        $this->roles()->syncWithoutDetaching([$model->id]);

        return $model;
    }

    /**
     * Remove a role from the user by name.
     */
    public function removeRole(string $role): void
    {
        $model = Role::where('name', $role)->first();

        if ($model) {
            $this->roles()->detach($model->id);
        }
    }

    /**
     * The roles this user holds, including the roles they inherit from.
     *
     * A role inherits every permission of its ancestors, so a "super_admin"
     * role with no parent of its own still resolves the full set of roles
     * declared as its ancestors.
     *
     * @return \Illuminate\Support\Collection<int, Role>
     */
    public function effectiveRoles(): \Illuminate\Support\Collection
    {
        $roles = $this->roles()->get();
        $effective = collect();

        foreach ($roles as $role) {
            $effective = $effective->merge($this->ancestorsOf($role));
        }

        return $effective->unique('id')->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Role>
     */
    protected function ancestorsOf(Role $role): \Illuminate\Support\Collection
    {
        $collected = collect([$role]);
        $current = $role;
        $guard = 0;

        while ($current->parent_role_id && $guard < 20) {
            $parent = Role::find($current->parent_role_id);

            if (! $parent || $collected->contains('id', $parent->id)) {
                break;
            }

            $collected->push($parent);
            $current = $parent;
            $guard++;
        }

        return $collected;
    }

    /**
     * The names of every permission the user effectively holds.
     *
     * @return array<int, string>
     */
    public function effectivePermissionNames(): array
    {
        $names = $this->permissions->pluck('name')->all();

        foreach ($this->effectiveRoles() as $role) {
            $names = array_merge($names, $role->permissions->pluck('name')->all());
        }

        return array_values(array_unique($names));
    }

    /**
     * Determine whether the user holds the given permission, directly or
     * through one of their roles.
     */
    public function hasPermissionTo(string $permission): bool
    {
        return in_array($permission, $this->effectivePermissionNames(), true);
    }

    /**
     * Determine whether the user is a system administrator.
     */
    public function isSystem(): bool
    {
        return $this->hasRole('super_admin');
    }
}
