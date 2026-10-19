<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Http\Controllers;

use App\Modules\Exhibition\Contracts\LeadServiceInterface;
use App\Modules\Exhibition\DTOs\LeadCreationDTO;
use App\Modules\Exhibition\Enums\InterestLevel;
use App\Modules\Exhibition\Http\Resources\LeadResource;
use App\Modules\Exhibition\Models\Booth;
use App\Modules\Exhibition\Models\Exhibitor;
use App\Modules\Exhibition\Models\Lead;
use App\Modules\Identity\Models\User;
use App\Modules\Registration\Models\Attendee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeadController extends Controller
{
    /**
     * Message returned when the caller may not act on the target exhibitor.
     */
    public const PERMISSION_DENIED_MESSAGE = 'Permission denied. You are not authorized to create leads for this exhibitor.';

    public function __construct(
        protected LeadServiceInterface $leadService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * List the leads the caller is allowed to see.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'exhibitor_id',
            'attendee_id',
            'booth_id',
            'status',
            'interest_level',
            'tag',
            'contacted',
            'follow_up_sent',
            'location',
        ]);

        $leads = $this->leadService->getLeadsForUser($request->user(), $filters);

        $data = $leads
            ->map(fn (Lead $lead) => (new LeadResource($lead))->resolve($request))
            ->values();

        return response()->json([
            'leads' => $data,
            'data' => $data,
            'count' => $data->count(),
            'success' => true,
        ]);
    }

    /**
     * Capture a new lead.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->normalize($request->all());

        $exhibitor = $this->resolveExhibitor($data, $user);

        if ($exhibitor) {
            if (! $this->leadService->canManageExhibitor($user, (int) $exhibitor->id)) {
                return $this->permissionDenied();
            }
        } elseif (! $this->leadService->canCreateLeads($user)) {
            return $this->permissionDenied();
        }

        $validator = Validator::make($data, [
            'attendee_id' => ['required_without:attendee_name', 'nullable', 'integer'],
            'attendee_name' => ['required_without:attendee_id', 'nullable', 'string', 'max:255'],
            'exhibitor_id' => ['nullable', 'integer'],
            'exhibitor_name' => ['nullable', 'string', 'max:255'],
            'booth_id' => ['nullable', 'integer'],
            'booth_name' => ['nullable', 'string', 'max:255'],
            'interest_level' => ['nullable', Rule::in(InterestLevel::values())],
            'notes' => ['required', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'additional_data' => ['nullable', 'array'],
        ]);

        $validator->after(function ($validator) use ($exhibitor, $data) {
            if (! $exhibitor) {
                $validator->errors()->add('exhibitor_id', 'The exhibitor field is required.');
            }

            // A lead must point at an attendee, either by id or by name. The
            // spec phrases this as a single "missing attendee" error, so the
            // generic key is added alongside the field-specific ones.
            if (empty($data['attendee_id']) && empty($data['attendee_name'])) {
                $validator->errors()->add('attendee', 'The attendee field is required.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $attendeeId = $this->resolveAttendeeId($data);

        if (! $attendeeId) {
            throw ValidationException::withMessages([
                'attendee_id' => ['The selected attendee is invalid.'],
            ]);
        }

        $booth = $this->resolveBooth($data, $exhibitor);

        $dto = LeadCreationDTO::fromRequest([
            'attendee_id' => $attendeeId,
            'booth_id' => $booth?->id,
            'location' => $data['location'] ?? $booth?->location,
            'notes' => $data['notes'],
            'interest_level' => $data['interest_level'] ?? InterestLevel::Medium->value,
            'additional_data' => $data['additional_data'] ?? [],
        ]);

        $lead = $this->leadService->createLead((int) $exhibitor->id, $dto);

        $payload = (new LeadResource($lead))->resolve($request);

        return response()->json([
            'message' => 'Lead created successfully',
            'success' => true,
            'lead' => $payload,
            'data' => $payload,
        ], 201);
    }

    /**
     * Show a single lead.
     */
    public function show(Request $request, string $lead): JsonResponse
    {
        $model = $this->leadService->findLeadForUser($request->user(), (int) $lead);

        if (! $model) {
            return response()->json(['message' => 'Lead not found'], 404);
        }

        $payload = (new LeadResource($model))->resolve($request);

        return response()->json([
            'lead' => $payload,
            'data' => $payload,
            'success' => true,
        ]);
    }

    /**
     * Update a lead.
     */
    public function update(Request $request, string $lead): JsonResponse
    {
        $user = $request->user();
        $model = Lead::find((int) $lead);

        if (! $model || ! $this->leadService->canViewExhibitor($user, (int) $model->exhibitor_id)) {
            return response()->json(['message' => 'Lead not found'], 404);
        }

        if (! $this->leadService->canUpdateExhibitor($user, (int) $model->exhibitor_id)) {
            return $this->permissionDenied();
        }

        $data = $this->normalize($request->all());

        $validator = Validator::make($data, [
            'status' => ['nullable', Rule::in(\App\Modules\Exhibition\Enums\LeadStatus::values())],
            'notes' => ['nullable', 'string', 'max:5000'],
            'interest_level' => ['nullable', Rule::in(InterestLevel::values())],
            'contacted' => ['nullable', 'boolean'],
            'follow_up_sent' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'booth_id' => ['nullable', 'integer'],
            'booth_name' => ['nullable', 'string', 'max:255'],
            'additional_data' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $attributes = $request->only([
            'status',
            'notes',
            'interest_level',
            'contacted',
            'follow_up_sent',
            'location',
            'booth_id',
            'additional_data',
            'tags',
        ]);

        if (! empty($data['booth_name'])) {
            $booth = $this->resolveBooth($data, $model->exhibitor);
            $attributes['booth_id'] = $booth?->id;
        }

        $updated = $this->leadService->updateLead((int) $model->id, $attributes, $user);

        $payload = (new LeadResource($updated))->resolve($request);

        return response()->json([
            'message' => 'Lead updated successfully',
            'success' => true,
            'lead' => $payload,
            'data' => $payload,
        ]);
    }

    /**
     * The status change history of a lead.
     */
    public function history(Request $request, string $lead): JsonResponse
    {
        $user = $request->user();
        $model = Lead::find((int) $lead);

        if (! $model || ! $this->leadService->canViewExhibitor($user, (int) $model->exhibitor_id)) {
            return response()->json(['message' => 'Lead not found'], 404);
        }

        $history = $this->leadService->getHistory((int) $model->id)->map(fn ($entry) => [
            'id' => $entry->id,
            'lead_id' => $entry->lead_id,
            'user_id' => $entry->user_id,
            'field' => $entry->field,
            'old_value' => $entry->old_value,
            'new_value' => $entry->new_value,
            'created_at' => $entry->created_at?->toISOString(),
        ])->values();

        return response()->json([
            'history' => $history,
            'data' => $history,
            'count' => $history->count(),
            'success' => true,
        ]);
    }

    /**
     * Attach a tag to a lead.
     */
    public function addTag(Request $request, string $lead): JsonResponse
    {
        $user = $request->user();
        $model = Lead::find((int) $lead);

        if (! $model || ! $this->leadService->canViewExhibitor($user, (int) $model->exhibitor_id)) {
            return response()->json(['message' => 'Lead not found'], 404);
        }

        if (! $this->leadService->canUpdateExhibitor($user, (int) $model->exhibitor_id)) {
            return $this->permissionDenied();
        }

        $data = $request->all();
        $tagName = $data['tag_name'] ?? $data['name'] ?? $data['tag'] ?? null;

        $validator = Validator::make(
            ['tag_name' => $tagName],
            ['tag_name' => ['required', 'string', 'max:255']]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $tag = $this->leadService->addTag(
            (int) $model->id,
            (string) $tagName,
            $user,
            isset($data['color']) ? (string) $data['color'] : null,
            isset($data['note']) ? (string) $data['note'] : null
        );

        $updated = $model->fresh()->load(['attendee', 'booth', 'exhibitor', 'tags']);
        $payload = (new LeadResource($updated))->resolve($request);

        return response()->json([
            'message' => 'Tag added to lead',
            'success' => true,
            'tag' => [
                'id' => $tag->id,
                'tag_name' => $tag->tag_name,
                'color' => $tag->color,
                'note' => $tag->note,
            ],
            'lead' => $payload,
            'data' => $payload,
        ], 201);
    }

    /**
     * Detach a tag from a lead.
     */
    public function removeTag(Request $request, string $lead, string $tag): JsonResponse
    {
        $user = $request->user();
        $model = Lead::find((int) $lead);

        if (! $model || ! $this->leadService->canViewExhibitor($user, (int) $model->exhibitor_id)) {
            return response()->json(['message' => 'Lead not found'], 404);
        }

        if (! $this->leadService->canUpdateExhibitor($user, (int) $model->exhibitor_id)) {
            return $this->permissionDenied();
        }

        if (! $this->leadService->removeTag((int) $model->id, $tag)) {
            return response()->json(['message' => 'Tag not found'], 404);
        }

        $updated = $model->fresh()->load(['attendee', 'booth', 'exhibitor', 'tags']);
        $payload = (new LeadResource($updated))->resolve($request);

        return response()->json([
            'message' => 'Tag removed from lead',
            'success' => true,
            'lead' => $payload,
            'data' => $payload,
        ]);
    }

    /**
     * Uppercase the enum-like inputs so both "high" and "HIGH" are accepted.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalize(array $data): array
    {
        foreach (['interest_level', 'status'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = strtoupper(trim($data[$field]));
            }
        }

        return $data;
    }

    /**
     * Find the exhibitor the lead belongs to.
     *
     * @param  array<string, mixed>  $data
     */
    protected function resolveExhibitor(array $data, User $user): ?Exhibitor
    {
        if (! empty($data['exhibitor_id'])) {
            return Exhibitor::find((int) $data['exhibitor_id']);
        }

        if (! empty($data['exhibitor_name'])) {
            $exhibitor = $this->findExhibitorByName((string) $data['exhibitor_name']);

            if ($exhibitor) {
                return $exhibitor;
            }
        }

        $booth = $this->resolveBooth($data, null);

        if ($booth && $booth->exhibitor) {
            return $booth->exhibitor;
        }

        $membership = $user->exhibitorMemberships()
            ->where('is_active', true)
            ->with('exhibitor')
            ->first();

        if ($membership && $membership->exhibitor) {
            return $membership->exhibitor;
        }

        return null;
    }

    protected function findExhibitorByName(string $name): ?Exhibitor
    {
        return Exhibitor::whereRaw('LOWER(company_name) = ?', [mb_strtolower($name)])->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveAttendeeId(array $data): ?int
    {
        if (! empty($data['attendee_id'])) {
            $attendee = Attendee::find((int) $data['attendee_id']);

            if ($attendee) {
                return (int) $attendee->id;
            }
        }

        if (! empty($data['attendee_name'])) {
            $name = (string) $data['attendee_name'];

            $attendee = Attendee::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

            if (! $attendee && preg_match('/^(.+)\s+(.+)$/u', $name, $matches)) {
                $attendee = Attendee::whereRaw('LOWER(first_name) = ?', [mb_strtolower(trim($matches[1]))])
                    ->whereRaw('LOWER(last_name) = ?', [mb_strtolower(trim($matches[2]))])
                    ->first();
            }

            if ($attendee) {
                return (int) $attendee->id;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveBooth(array $data, ?Exhibitor $exhibitor): ?Booth
    {
        if (! empty($data['booth_id'])) {
            return Booth::find((int) $data['booth_id']);
        }

        $name = $data['booth_name'] ?? null;

        if (! $name && ! empty($data['location'])) {
            $name = (string) $data['location'];
        }

        if (! $name) {
            return null;
        }

        $query = Booth::whereRaw('LOWER(name) = ?', [mb_strtolower((string) $name)])
            ->orWhere('number', (string) $name);

        if ($exhibitor) {
            return (clone $query)->where('exhibitor_id', (int) $exhibitor->id)->first()
                ?? Booth::whereRaw('LOWER(name) = ?', [mb_strtolower((string) $name)])
                    ->orWhere('number', (string) $name)
                    ->first();
        }

        return $query->first();
    }

    protected function permissionDenied(): JsonResponse
    {
        return response()->json([
            'message' => self::PERMISSION_DENIED_MESSAGE,
            'error' => 'permission_denied',
        ], 403);
    }
}
