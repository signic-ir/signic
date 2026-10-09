<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Policies;

use App\Modules\Exhibition\Models\Exhibitor;
use App\Modules\Exhibition\Models\Lead;

class ExhibitorPolicy
{
    public function viewAny($authUser): bool
    {
        return $authUser->can('view-exhibitors');
    }

    public function view($authUser, Exhibitor $exhibitor): bool
    {
        return $authUser->can('view-exhibitors') ||
               $authUser->exhibitorMemberships()->where('exhibitor_id', $exhibitor->id)->exists();
    }

    public function create($authUser): bool
    {
        return $authUser->can('create-exhibitors');
    }

    public function update($authUser, Exhibitor $exhibitor): bool
    {
        return $authUser->can('update-exhibitors') ||
               $authUser->exhibitorMemberships()->where('exhibitor_id', $exhibitor->id)->exists();
    }
}