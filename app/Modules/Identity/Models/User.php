<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Modules\Exhibition\Models\ExhibitorMember;
use App\Modules\Registration\Models\Attendee;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
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

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password' => 'hashed',
        'metadata' => 'array',
    ];

    /**
     * Get the roles associated with the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\Spatie\Permission\Models\Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Get the permissions associated with the user.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(\Spatie\Permission\Models\Permission::class, 'permission_user')
            ->withTimestamps();
    }

    /**
     * Get the attendee records for this user.
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    /**
     * Get the exhibitor memberships for this user.
     */
    public function exhibitorMemberships(): HasMany
    {
        return $this->hasMany(ExhibitorMember::class);
    }

    /**
     * Convert to API resource.
     */
    public function toResource(): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'phone_verified_at' => $this->phone_verified_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
        ];
    }
}