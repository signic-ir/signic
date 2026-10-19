<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Identity\Models\User;

class Turnstile extends Model
{
    use SoftDeletes;

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
        return $this->hasMany(ScanEvent::class, 'turnstile_id')->where('event_type', 'CHECK_IN');
    }

    /**
     * Get the check-out events for this turnstile.
     */
    public function checkOutEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class, 'turnstile_id')->where('event_type', 'CHECK_OUT');
    }

    /**
     * Get every scan recorded at this turnstile.
     */
    public function scanEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class, 'turnstile_id');
    }
}
