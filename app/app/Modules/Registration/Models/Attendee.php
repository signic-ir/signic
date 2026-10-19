<?php

declare(strict_types=1);

namespace App\Modules\Registration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Identity\Models\User;
use App\Modules\Registration\Models\QRBadge;
use App\Modules\Registration\Models\PrintJob;
use App\Modules\AccessControl\Models\ScanEvent;

class Attendee extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event_id',
        'user_id',
        'phone',
        'name',
        'email',
        'company',
        'job_title',
        'dietary_requirements',
        'accessibility_needs',
        'special_instructions',
        'ticket_type',
        'status',
        'qr_token_hash',
        'qr_token_generated_at',
        'qr_token_expires_at',
        'checkin_at',
        'checkout_at',
        'badge_printed',
        'badge_printed_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'qr_token_generated_at' => 'datetime',
        'qr_token_expires_at' => 'datetime',
        'checkin_at' => 'datetime',
        'checkout_at' => 'datetime',
        'badge_printed' => 'boolean',
        'badge_printed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the event this attendee belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user for this attendee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the QR badge for this attendee.
     */
    public function qrBadge(): BelongsTo
    {
        return $this->hasOne(QRBadge::class);
    }

    /**
     * Get the print jobs for this attendee.
     */
    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }

    /**
     * Get the scan events for this attendee.
     */
    public function scanEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class);
    }
}