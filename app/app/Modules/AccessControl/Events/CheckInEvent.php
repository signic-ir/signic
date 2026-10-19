<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class CheckInEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * The check-in data.
     *
     * @var array
     */
    public array $checkInData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $checkInData)
    {
        $this->checkInData = $checkInData;
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return ['signic.access-control'];
    }
}
