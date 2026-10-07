<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Identity\Models\User;

class Turnstile extends Model
{
    protected $table = 'turnstiles';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event_id',
        'name',
        'location',
        'type',
        'ip_address',
        'hardware_model',
        'is_active',
        'config',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the event this turnstile belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Registration\Models\Event::class);
    }

    /**
     * Get the check-in events for this turnstile.
     */
    public function checkInEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class, 'turnstile_id')->where('event_type', 'checkin');
    }

    /**
     * Get the check-out events for this turnstile.
     */
    public function checkOutEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class, 'turnstile_id')->where('event_type', 'checkout');
    }
}