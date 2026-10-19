<?php

declare(strict_types=1);

namespace App\Modules\Registration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Identity\Models\User;
use App\Modules\Registration\Models\QRBadge;
use App\Modules\Registration\Models\PrintJob;
use App\Modules\AccessControl\Models\ScanEvent;

class Event extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'rules',
        'starts_at',
        'ends_at',
        'registration_start_at',
        'registration_end_at',
        'max_capacity',
        'is_registration_open',
        'status',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'registration_start_at' => 'datetime',
        'registration_end_at' => 'datetime',
        'max_capacity' => 'integer',
        'is_registration_open' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the attendees for this event.
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    /**
     * Get the turnstiles for this event.
     */
    public function turnstiles(): HasMany
    {
        return $this->hasMany(\App\Modules\AccessControl\Models\Turnstile::class);
    }

    /**
     * Get the exhibitors for this event.
     */
    public function exhibitors(): HasMany
    {
        return $this->hasMany(\App\Modules\Exhibition\Models\Exhibitor::class);
    }
}