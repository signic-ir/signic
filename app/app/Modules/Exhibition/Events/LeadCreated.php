<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\SerializesModels;

class LeadCreated extends Event implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * The lead data.
     */
    public array $leadData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $leadData)
    {
        $this->leadData = $leadData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return ['signic.exhibition'];
    }
}