<?php

declare(strict_types=1);

namespace App\Modules\Registration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QRBadge extends Model
{
    protected $table = 'qr_badges';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'attendee_id',
        'token_hash',
        'design_version',
        'is_active',
        'generated_at',
        'expires_at',
        'design_config_json',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the attendee for this QR badge.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }
}