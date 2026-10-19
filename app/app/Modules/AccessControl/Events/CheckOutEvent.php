<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class CheckOutEvent implements ShouldBroadcast
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
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return ['signic.access-control'];
    }
}
