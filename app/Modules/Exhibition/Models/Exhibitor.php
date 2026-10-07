<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Registration\Models\Event;
use App\Modules\Exhibition\Models\Booth;
use App\Modules\Exhibition\Models\ExhibitorMember;
use App\Modules\Exhibition\Models\Lead;

class Exhibitor extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event_id',
        'company_name',
        'booth_number',
        'website',
        'contact_email',
        'contact_phone',
        'booth_description',
        'booth_specs',
        'status',
        'contact_person',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'booth_specs' => 'array',
        'contact_person' => 'array',
    ];

    /**
     * Get the event this exhibitor belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the booths for this exhibitor.
     */
    public function booths(): HasMany
    {
        return $this->hasMany(Booth::class);
    }

    /**
     * Get the members of this exhibitor.
     */
    public function members(): HasMany
    {
        return $this->hasMany(ExhibitorMember::class);
    }

    /**
     * Get the leads for this exhibitor.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}