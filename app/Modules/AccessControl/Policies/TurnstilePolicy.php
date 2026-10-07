<?php

declare(strict_types=1);

namespace App\AccessControl\Policies;

use App\AccessControl\Models\Turnstile;
use App\AccessControl\Models\ScanEvent;
use App\Identity\Models\User;

class TurnstilePolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('view-turnstiles');
    }

    public function view(User $authUser, Turnstile $turnstile): bool
    {
        return $authUser->can('view-turnstiles');
    }

    public function create(User $authUser): bool
    {
        return $authUser->can('manage-turnstiles');
    }

    public function update(User $authUser, Turnstile $turnstile): bool
    {
        return $authUser->can('manage-turnstiles');
    }

    public function delete(User $authUser, Turnstile $turnstile): bool
    {
        return $authUser->can('delete-turnstiles');
    }
}

class ScanEventPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('view-scans');
    }

    public function view(User $authUser, ScanEvent $scanEvent): bool
    {
        return $authUser->can('view-scans');
    }
}