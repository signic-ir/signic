<?php

declare(strict_types=1);

namespace App\Modules\Registration\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Auth\Access\AuthorizationException;

class AttendeeRegistered extends Event implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * The attendee model.
     *
     * @var \App\Modules\Registration\Models\Attendee
     */
    public $attendee;

    /**
     * The authenticated user who registered them.
     *
     * @var \App\Modules\Identity\Models\User
     */
    public $registeredBy;

    /**
     * Create a new event instance.
     */
    public function __construct(\App\Modules\Registration\Models\Attendee $attendee, \App\Modules\Identity\Models\User $registeredBy)
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