<?php

declare(strict_types=1);

namespace App\Modules\Registration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Registration\Enums\PrintJobStatus;

class PrintJob extends Model
{
    protected $table = 'print_jobs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'attendee_id',
        'status',
        'badge_data',
        'print_settings',
        'printed_at',
        'failed_at',
        'failure_reason',
        'printer_name',
        'attempt',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'badge_data' => 'array',
        'print_settings' => 'array',
        'printed_at' => 'datetime',
        'failed_at' => 'datetime',
        'attempt' => 'integer',
    ];

    /**
     * Get the attendee for this print job.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }
}