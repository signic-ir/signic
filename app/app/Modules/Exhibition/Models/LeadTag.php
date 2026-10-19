<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Identity\Models\User;

class LeadTag extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lead_id',
        'tag_name',
        'color',
        'note',
        'created_by_user_id',
    ];

    /**
     * Get the lead this tag belongs to.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who created this tag.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}