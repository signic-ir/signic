<?php

declare(strict_types=1);

namespace App\Registration\Policies;

use App\Registration\Models\Attendee;

class AttendeePolicy
{
    public function viewAny($authUser): bool
    {
        return $authUser->can('view-attendees');
    }

    public function view($authUser, Attendee $attendee): bool
    {
        return $authUser->can('view-attendees') || $authUser->id === $attendee->user_id;
    }

    public function create($authUser): bool
    {
        return $authUser->can('create-attendees');
    }

    public function update($authUser, Attendee $attendee): bool
    {
        return $authUser->can('update-attendees') || $authUser->id === $attendee->user_id;
    }

    public function delete($authUser, Attendee $attendee): bool
    {
        return $authUser->can('delete-attendees');
    }
}