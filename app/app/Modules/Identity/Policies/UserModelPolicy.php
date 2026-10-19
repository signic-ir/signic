<?php

declare(strict_types=1);

namespace App\Modules\Identity\Policies;

use App\Modules\Identity\Models\User;

class UserModelPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('view-users');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $authUser, User $user): bool
    {
        return $authUser->can('view-users') || $authUser->id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $authUser): bool
    {
        return $authUser->can('create-users');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $authUser, User $user): bool
    {
        return $authUser->can('update-users') || $authUser->id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $authUser, User $user): bool
    {
        return $authUser->can('delete-users') || ($authUser->id === $user->id && !$authUser->isSystem());
    }
}