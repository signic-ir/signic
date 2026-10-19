<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadHistory extends Model
{
    protected $table = 'lead_histories';

    protected $fillable = [
        'lead_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
    ];

    /**
     * Get the lead this history entry belongs to.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
