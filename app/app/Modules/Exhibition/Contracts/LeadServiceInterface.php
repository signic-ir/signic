<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Contracts;

use App\Modules\Exhibition\DTOs\LeadCreationDTO;
use App\Modules\Exhibition\Models\Lead;
use App\Modules\Exhibition\Models\LeadTag;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Collection;

interface LeadServiceInterface
{
    public function createLead(int $exhibitorId, LeadCreationDTO $dto): Lead;

    public function getLeads(int $exhibitorId, array $filters = []): Collection;

    /**
     * List the leads the given user is allowed to see.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getLeadsForUser(User $user, array $filters = []): Collection;

    public function findLeadForUser(User $user, int $leadId): ?Lead;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateLead(int $leadId, array $attributes, ?User $actor = null): Lead;

    public function addTag(
        int $leadId,
        string $tagName,
        ?User $actor = null,
        ?string $color = null,
        ?string $note = null
    ): LeadTag;

    public function removeTag(int $leadId, string $tagName): bool;

    public function getHistory(int $leadId): Collection;

    public function exportLeads(int $exhibitorId): Collection;

    /**
     * Determine whether the user may create leads for the given exhibitor.
     */
    public function canManageExhibitor(User $user, int $exhibitorId): bool;

    /**
     * Determine whether the user may capture leads at all.
     */
    public function canCreateLeads(User $user): bool;

    /**
     * Determine whether the user may update the leads of the given exhibitor.
     */
    public function canUpdateExhibitor(User $user, int $exhibitorId): bool;

    /**
     * Determine whether the user may read the leads of the given exhibitor.
     */
    public function canViewExhibitor(User $user, int $exhibitorId): bool;

    /**
     * The exhibitors whose leads the user may read, or null when the user may
     * read the leads of every exhibitor.
     *
     * @return array<int, int>|null
     */
    public function visibleExhibitorIds(User $user): ?array;
}
