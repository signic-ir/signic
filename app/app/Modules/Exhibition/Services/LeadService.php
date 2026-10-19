<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Services;

use App\Modules\Exhibition\Contracts\LeadServiceInterface;
use App\Modules\Exhibition\DTOs\LeadCreationDTO;
use App\Modules\Exhibition\Enums\InterestLevel;
use App\Modules\Exhibition\Enums\LeadStatus;
use App\Modules\Exhibition\Models\Exhibitor;
use App\Modules\Exhibition\Models\Lead;
use App\Modules\Exhibition\Models\LeadHistory;
use App\Modules\Exhibition\Models\LeadTag;
use App\Modules\Identity\Models\User;
use App\Modules\Registration\Models\Attendee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LeadService implements LeadServiceInterface
{
    /**
     * Permission names that allow a user to capture a lead.
     *
     * @var array<int, string>
     */
    public const PERMISSION_CREATE = ['exhibitor.lead.create', 'lead.create', 'create-leads'];

    /**
     * Permission names that allow a user to read every lead.
     *
     * @var array<int, string>
     */
    public const PERMISSION_VIEW = ['exhibitor.lead.view', 'lead.view', 'view-leads'];

    /**
     * Permission names that allow a user to update a lead.
     *
     * @var array<int, string>
     */
    public const PERMISSION_UPDATE = ['exhibitor.lead.update', 'lead.update', 'update-leads'];

    public function createLead(int $exhibitorId, LeadCreationDTO $dto): Lead
    {
        $attendee = Attendee::find($dto->attendeeId);

        if (! $attendee) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                'Attendee '.$dto->attendeeId.' was not found.'
            );
        }

        $lead = Lead::create([
            'exhibitor_id' => $exhibitorId,
            'attendee_id' => $dto->attendeeId,
            'booth_id' => $dto->boothId,
            'scanned_at' => now(),
            'location' => $dto->location,
            'notes' => $dto->notes,
            'interest_level' => $dto->interestLevel ?: InterestLevel::Medium->value,
            'status' => LeadStatus::New->value,
            'additional_data' => $dto->additionalData,
        ]);

        return $this->withRelations($lead);
    }

    public function getLeads(int $exhibitorId, array $filters = []): Collection
    {
        $query = Lead::query()->where('exhibitor_id', $exhibitorId);

        return $this->applyFilters($query, $filters)->get();
    }

    public function getLeadsForUser(User $user, array $filters = []): Collection
    {
        $query = Lead::query();

        $visible = $this->visibleExhibitorIds($user);

        if ($visible !== null) {
            $query->whereIn('exhibitor_id', $visible ?: [0]);
        }

        if (! empty($filters['exhibitor_id'])) {
            $query->where('exhibitor_id', (int) $filters['exhibitor_id']);
        }

        return $this->applyFilters($query, $filters)->get();
    }

    public function findLeadForUser(User $user, int $leadId): ?Lead
    {
        $lead = Lead::with(['attendee', 'booth', 'exhibitor', 'tags'])->find($leadId);

        if (! $lead) {
            return null;
        }

        if (! $this->canViewExhibitor($user, (int) $lead->exhibitor_id)) {
            return null;
        }

        return $lead;
    }

    public function updateLead(int $leadId, array $attributes, ?User $actor = null): Lead
    {
        $lead = Lead::findOrFail($leadId);

        $updatable = ['status', 'notes', 'interest_level', 'contacted', 'follow_up_sent', 'location', 'booth_id', 'additional_data'];

        $changes = [];

        foreach ($updatable as $field) {
            if (! array_key_exists($field, $attributes) || $attributes[$field] === null) {
                continue;
            }

            $value = $attributes[$field];

            if ($field === 'status') {
                $value = strtoupper((string) $value);
            }

            if ($field === 'interest_level') {
                $value = strtoupper((string) $value);
            }

            if ((string) $lead->{$field} !== (string) $value) {
                $changes[$field] = [
                    'old_value' => $lead->{$field} === null ? null : (string) $lead->{$field},
                    'new_value' => (string) $value,
                ];
            }

            $lead->{$field} = $value;
        }

        $lead->save();

        foreach ($changes as $field => $values) {
            if ($field !== 'status') {
                continue;
            }

            LeadHistory::create([
                'lead_id' => $lead->id,
                'user_id' => $actor?->id,
                'field' => 'status',
                'old_value' => $values['old_value'],
                'new_value' => $values['new_value'],
            ]);
        }

        if (array_key_exists('tags', $attributes) && is_array($attributes['tags'])) {
            $this->syncTags($lead, $attributes['tags'], $actor);
        }

        return $this->withRelations($lead->fresh());
    }

    public function addTag(
        int $leadId,
        string $tagName,
        ?User $actor = null,
        ?string $color = null,
        ?string $note = null
    ): LeadTag {
        $lead = Lead::findOrFail($leadId);

        $tag = LeadTag::updateOrCreate(
            ['lead_id' => $lead->id, 'tag_name' => $tagName],
            [
                'color' => $color ?: '#6B7280',
                'note' => $note,
                'created_by_user_id' => $actor?->id,
            ]
        );

        return $tag;
    }

    public function removeTag(int $leadId, string $tagName): bool
    {
        $lead = Lead::findOrFail($leadId);

        $tag = LeadTag::where('lead_id', $lead->id)
            ->where('tag_name', $tagName)
            ->first();

        if (! $tag) {
            return false;
        }

        $tag->delete();

        return true;
    }

    public function getHistory(int $leadId): Collection
    {
        return LeadHistory::where('lead_id', $leadId)
            ->orderBy('id')
            ->get();
    }

    public function exportLeads(int $exhibitorId): Collection
    {
        return $this->getLeads($exhibitorId)
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'attendee_id' => $lead->attendee_id,
                    'attendee_name' => $lead->attendee ? $lead->attendee->name : null,
                    'attendee_email' => $lead->attendee ? $lead->attendee->email : null,
                    'scanned_at' => $lead->scanned_at,
                    'location' => $lead->location,
                    'notes' => $lead->notes,
                    'interest_level' => $lead->interest_level,
                    'status' => $lead->status,
                    'contacted' => $lead->contacted,
                    'created_at' => $lead->created_at,
                    'updated_at' => $lead->updated_at,
                    'tags' => $lead->tags->pluck('tag_name')->toArray(),
                ];
            });
    }

    public function canManageExhibitor(User $user, int $exhibitorId): bool
    {
        if ($this->canCreateLeads($user)) {
            return true;
        }

        return $this->isActiveMember($user, $exhibitorId);
    }

    public function canCreateLeads(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->hasAnyPermission($user, self::PERMISSION_CREATE);
    }

    public function canUpdateExhibitor(User $user, int $exhibitorId): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($this->hasAnyPermission($user, self::PERMISSION_UPDATE) || $this->hasAnyPermission($user, self::PERMISSION_CREATE)) {
            return true;
        }

        return $this->isActiveMember($user, $exhibitorId);
    }

    public function canViewExhibitor(User $user, int $exhibitorId): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($this->hasAnyPermission($user, self::PERMISSION_VIEW) || $this->hasAnyPermission($user, self::PERMISSION_CREATE)) {
            return true;
        }

        return $this->isActiveMember($user, $exhibitorId);
    }

    public function visibleExhibitorIds(User $user): ?array
    {
        if ($this->isSuperAdmin($user)) {
            return null;
        }

        if ($this->hasAnyPermission($user, self::PERMISSION_VIEW) || $this->hasAnyPermission($user, self::PERMISSION_CREATE)) {
            return null;
        }

        return $user->exhibitorMemberships()
            ->where('is_active', true)
            ->pluck('exhibitor_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query->with(['attendee', 'booth', 'exhibitor', 'tags']);

        if (! empty($filters['attendee_id'])) {
            $query->where('attendee_id', (int) $filters['attendee_id']);
        }

        if (! empty($filters['booth_id'])) {
            $query->where('booth_id', (int) $filters['booth_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', strtoupper((string) $filters['status']));
        }

        if (! empty($filters['interest_level'])) {
            $query->where('interest_level', strtoupper((string) $filters['interest_level']));
        }

        if (! empty($filters['location'])) {
            $query->where('location', $filters['location']);
        }

        if (array_key_exists('contacted', $filters) && $filters['contacted'] !== null && $filters['contacted'] !== '') {
            $query->where('contacted', $this->toBoolean($filters['contacted']));
        }

        if (array_key_exists('follow_up_sent', $filters) && $filters['follow_up_sent'] !== null && $filters['follow_up_sent'] !== '') {
            $query->where('follow_up_sent', $this->toBoolean($filters['follow_up_sent']));
        }

        if (! empty($filters['tag'])) {
            $tag = (string) $filters['tag'];

            $query->whereHas('tags', fn ($q) => $q->where('tag_name', $tag));
        }

        return $query->orderByDesc('id');
    }

    /**
     * @param  array<int, string>  $tagNames
     */
    protected function syncTags(Lead $lead, array $tagNames, ?User $actor = null): void
    {
        $names = array_values(array_filter(array_map(
            fn ($name) => is_string($name) ? trim($name) : (is_array($name) ? ($name['tag_name'] ?? $name['name'] ?? null) : null),
            $tagNames
        )));

        LeadTag::where('lead_id', $lead->id)
            ->whereNotIn('tag_name', $names ?: ['__none__'])
            ->delete();

        foreach ($names as $name) {
            LeadTag::updateOrCreate(
                ['lead_id' => $lead->id, 'tag_name' => $name],
                ['color' => '#6B7280', 'created_by_user_id' => $actor?->id]
            );
        }
    }

    protected function withRelations(Lead $lead): Lead
    {
        return $lead->loadMissing(['attendee', 'booth', 'exhibitor', 'tags']);
    }

    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function isActiveMember(User $user, int $exhibitorId): bool
    {
        return $user->exhibitorMemberships()
            ->where('exhibitor_id', $exhibitorId)
            ->where('is_active', true)
            ->exists();
    }

    protected function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
