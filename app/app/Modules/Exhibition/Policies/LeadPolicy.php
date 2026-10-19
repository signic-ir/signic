<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Policies;

use App\Modules\Exhibition\Contracts\LeadServiceInterface;
use App\Modules\Exhibition\Models\Lead;
use App\Modules\Identity\Models\User;

class LeadPolicy
{
    public function __construct(
        protected LeadServiceInterface $leadService
    ) {}

    public function viewAny(User $authUser): bool
    {
        return $this->leadService->visibleExhibitorIds($authUser) === null
            || $this->leadService->canCreateLeads($authUser);
    }

    public function view(User $authUser, Lead $lead): bool
    {
        return $this->leadService->canViewExhibitor($authUser, (int) $lead->exhibitor_id);
    }

    public function create(User $authUser): bool
    {
        return $this->leadService->canCreateLeads($authUser);
    }

    public function update(User $authUser, Lead $lead): bool
    {
        return $this->leadService->canUpdateExhibitor($authUser, (int) $lead->exhibitor_id);
    }

    public function delete(User $authUser, Lead $lead): bool
    {
        return $this->leadService->canUpdateExhibitor($authUser, (int) $lead->exhibitor_id);
    }

    public function export(User $authUser, Lead $lead): bool
    {
        return $this->leadService->canViewExhibitor($authUser, (int) $lead->exhibitor_id);
    }
}
