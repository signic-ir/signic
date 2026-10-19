<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booth extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'exhibitor_id',
        'event_id',
        'number',
        'section',
        'location',
        'layout_config',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'layout_config' => 'array',
    ];

    /**
     * Get the exhibitor this booth belongs to.
     */
    public function exhibitor(): BelongsTo
    {
        return $this->belongsTo(Exhibitor::class);
    }

    /**
     * Get the event this booth belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}