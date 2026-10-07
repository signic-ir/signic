<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Identity\Models\User;

class ExhibitorMember extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'exhibitor_id',
        'user_id',
        'role',
        'position',
        'is_active',
        'invited_at',
        'accepted_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the exhibitor this member belongs to.
     */
    public function exhibitor(): BelongsTo
    {
        return $this->belongsTo(Exhibitor::class);
    }

    /**
     * Get the user for this member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}