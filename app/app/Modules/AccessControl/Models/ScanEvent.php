<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Registration\Models\Attendee;

class ScanEvent extends Model
{
    protected $table = 'scan_events';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'attendee_id',
        'turnstile_id',
        'token_hash',
        'event_type',
        'result',
        'ip_address',
        'metadata',
        'scanned_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
        'scanned_at' => 'datetime',
    ];

    /**
     * Get the attendee for this scan event.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    /**
     * Get the turnstile for this scan event.
     */
    public function turnstile(): BelongsTo
    {
        return $this->belongsTo(Turnstile::class);
    }
}