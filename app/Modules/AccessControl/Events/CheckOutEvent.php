<?php

declare(strict_types=1);

namespace App.Modules\AccessControl\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CheckOutEvent extends Event implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * The check-out data.
     */
    public array $checkOutData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $checkOutData)
    {
        $this->checkOutData = $checkOutData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return ['signic.access-control'];
    }
}