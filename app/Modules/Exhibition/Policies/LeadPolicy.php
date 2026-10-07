<?php

declare(strict_types=1);

namespace App\Exhibition\Policies;

use App\Modules\Exhibition\Models\Lead;
use App\Modules\Identity\Models\User;

class LeadPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('view-leads');
    }

    public function view(User $authUser, Lead $lead): bool
    {
        return $authUser->can('view-leads') ||
               $authUser->exhibitorMemberships()
                   ->where('exhibitor_id', $lead->exhibitor_id)
                   ->exists();
    }

    public function create(User $authUser): bool
    {
        return $authUser->can('create-leads');
    }

    public function update(User $authUser, Lead $lead): bool
    {
        return $authUser->can('update-leads') ||
               $authUser->exhibitorMemberships()
                   ->where('exhibitor_id', $lead->exhibitor_id)
                   ->exists();
    }

    public function export(User $authUser, Lead $lead): bool
    {
        return $authUser->can('export-leads');
    }
}