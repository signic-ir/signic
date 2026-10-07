<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Exhibition\Models\Exhibitor;
use App\Modules\Identity\Models\User;

class Lead extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'exhibitor_id',
        'attendee_id',
        'scanned_at',
        'location',
        'notes',
        'interest_level',
        'follow_up_sent',
        'contacted',
        'additional_data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'scanned_at' => 'datetime',
        'follow_up_sent' => 'boolean',
        'contacted' => 'boolean',
        'additional_data' => 'array',
    ];

    /**
     * Get the exhibitor this lead belongs to.
     */
    public function exhibitor(): BelongsTo
    {
        return $this->belongsTo(Exhibitor::class);
    }

    /**
     * Get the attendee this lead is for.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    /**
     * Get the tags for this lead.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(LeadTag::class);
    }
}