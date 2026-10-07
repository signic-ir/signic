<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\SerializesModels;

class CheckInEvent extends Event implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * The check-in data.
     *
     * @var array
     */
    public $checkInData;

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