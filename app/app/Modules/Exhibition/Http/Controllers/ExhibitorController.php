<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Http\Controllers;

use App\Modules\Exhibition\Enums\ExhibitorStatus;
use App\Modules\Exhibition\Models\Exhibitor;
use App\Modules\Exhibition\Models\ExhibitorMember;
use App\Modules\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExhibitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List the exhibitors.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Exhibitor::query()->with(['booths', 'members']);

        if ($request->filled('event_id')) {
            $query->where('event_id', (int) $request->input('event_id'));
        }

        if ($request->filled('name')) {
            $name = (string) $request->input('name');
            $query->whereRaw('LOWER(company_name) = ?', [mb_strtolower($name)]);
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper((string) $request->input('status')));
        }

        $exhibitors = $query->orderBy('id')->get();

        $data = $exhibitors->map(fn (Exhibitor $exhibitor) => $this->serialize($exhibitor))->values();

        return response()->json([
            'exhibitors' => $data,
            'data' => $data,
            'count' => $data->count(),
            'success' => true,
        ]);
    }

    /**
     * Register a new exhibitor.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'booth_number' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'booth_description' => ['nullable', 'string'],
            'booth_specs' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(ExhibitorStatus::values())],
            'contact_person' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $exhibitor = Exhibitor::create([
            'event_id' => (int) $data['event_id'],
            'company_name' => $data['company_name'],
            'booth_number' => $data['booth_number'] ?? null,
            'website' => $data['website'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'booth_description' => $data['booth_description'] ?? null,
            'booth_specs' => $data['booth_specs'] ?? null,
            'status' => strtoupper((string) ($data['status'] ?? ExhibitorStatus::Pending->value)),
            'contact_person' => $data['contact_person'] ?? null,
        ]);

        $this->registerRepresentatives($exhibitor, $request->user(), $data['contact_person'] ?? null);

        $exhibitor->load(['booths', 'members']);

        $payload = $this->serialize($exhibitor);

        return response()->json([
            'message' => 'Exhibitor created successfully',
            'success' => true,
            'exhibitor' => $payload,
            'data' => $payload,
        ], 201);
    }

    /**
     * Show a single exhibitor.
     */
    public function show(Request $request, string $exhibitor): JsonResponse
    {
        $model = Exhibitor::with(['booths', 'members'])->find((int) $exhibitor);

        if (! $model) {
            return response()->json(['message' => 'Exhibitor not found'], 404);
        }

        $payload = $this->serialize($model);

        return response()->json([
            'exhibitor' => $payload,
            'data' => $payload,
            'success' => true,
        ]);
    }

    /**
     * Attach a member to an exhibitor.
     */
    public function addMember(Request $request, string $exhibitor): JsonResponse
    {
        $model = Exhibitor::find((int) $exhibitor);

        if (! $model) {
            return response()->json(['message' => 'Exhibitor not found'], 404);
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'user_id' => ['required_without:email', 'nullable', 'integer', 'exists:users,id'],
            'email' => ['required_without:user_id', 'nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = $this->findUser($data);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $member = ExhibitorMember::updateOrCreate(
            ['exhibitor_id' => $model->id, 'user_id' => $user->id],
            [
                'role' => $data['role'] ?? 'member',
                'position' => $data['position'] ?? null,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]
        );

        $model->load(['booths', 'members']);

        $payload = $this->serialize($model);

        return response()->json([
            'message' => 'Member added to exhibitor',
            'success' => true,
            'member' => $this->serializeMember($member),
            'exhibitor' => $payload,
            'data' => $payload,
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(Exhibitor $exhibitor): array
    {
        return [
            'id' => $exhibitor->id,
            'event_id' => $exhibitor->event_id,
            'company_name' => $exhibitor->company_name,
            'booth_number' => $exhibitor->booth_number,
            'website' => $exhibitor->website,
            'contact_email' => $exhibitor->contact_email,
            'contact_phone' => $exhibitor->contact_phone,
            'booth_description' => $exhibitor->booth_description,
            'booth_specs' => $exhibitor->booth_specs,
            'status' => $exhibitor->status,
            'contact_person' => $exhibitor->contact_person,
            'created_at' => $exhibitor->created_at?->toISOString(),
            'updated_at' => $exhibitor->updated_at?->toISOString(),
            'booths' => $exhibitor->booths->map(fn ($booth) => [
                'id' => $booth->id,
                'exhibitor_id' => $booth->exhibitor_id,
                'event_id' => $booth->event_id,
                'number' => $booth->number,
                'name' => $booth->name,
                'section' => $booth->section,
                'location' => $booth->location,
                'layout_config' => $booth->layout_config,
            ])->values(),
            'members' => $exhibitor->members->map(fn ($member) => $this->serializeMember($member))->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeMember(ExhibitorMember $member): array
    {
        return [
            'id' => $member->id,
            'exhibitor_id' => $member->exhibitor_id,
            'user_id' => $member->user_id,
            'role' => $member->role,
            'position' => $member->position,
            'is_active' => (bool) $member->is_active,
            'invited_at' => $member->invited_at?->toISOString(),
            'accepted_at' => $member->accepted_at?->toISOString(),
        ];
    }

    /**
     * The creator of an exhibitor and any explicitly named contact become
     * active representatives of that exhibitor.
     *
     * @param  array<string, mixed>|null  $contactPerson
     */
    protected function registerRepresentatives(Exhibitor $exhibitor, ?User $creator, ?array $contactPerson): void
    {
        $users = [];

        if ($creator) {
            $users[] = $creator;
        }

        if ($contactPerson) {
            $contact = $this->findUser($contactPerson);

            if ($contact) {
                $users[] = $contact;
            }
        }

        foreach ($users as $user) {
            ExhibitorMember::updateOrCreate(
                ['exhibitor_id' => $exhibitor->id, 'user_id' => $user->id],
                [
                    'role' => $creator && $user->is($creator) ? 'owner' : 'member',
                    'is_active' => true,
                    'invited_at' => now(),
                    'accepted_at' => now(),
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function findUser(array $data): ?User
    {
        if (! empty($data['user_id'])) {
            return User::find((int) $data['user_id']);
        }

        if (! empty($data['email'])) {
            return User::where('email', $data['email'])->first();
        }

        if (! empty($data['phone'])) {
            return User::where('phone', $data['phone'])->first();
        }

        return null;
    }
}
