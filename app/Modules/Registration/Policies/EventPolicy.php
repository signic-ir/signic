<?php

declare(strict_types=1);

namespace App\Registration\Policies;

use App\Modules\Registration\Models\Event;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($authUser): bool
    {
        return $authUser->can('view-events');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($authUser, Event $event): bool
    {
        if ($authUser->can('view-events')) {
            return $event->status === \App\Modules\Registration\Enums\EventStatus::Active;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($authUser): bool
    {
        return $authUser->can('create-events');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($authUser, Event $event): bool
    {
        return $authUser->can('update-events') || ($authUser->can('organizer') && $event->organizer_id === $authUser->id ?? null);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($authUser, Event $event): bool
    {
        return $authUser->can('delete-events');
    }
}