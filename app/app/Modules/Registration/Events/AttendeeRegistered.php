<?php

declare(strict_types=1);

namespace App\Modules\Registration\Events;

use App\Modules\Identity\Models\User;
use App\Modules\Registration\Models\Attendee;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class AttendeeRegistered implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * The attendee model.
     */
    public Attendee $attendee;

    /**
     * The authenticated user who registered them.
     */
    public User $registeredBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Attendee $attendee, User $registeredBy)
    {
        $this->attendee = $attendee;
        $this->registeredBy = $registeredBy;
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return ['signic.registration'];
    }
}
