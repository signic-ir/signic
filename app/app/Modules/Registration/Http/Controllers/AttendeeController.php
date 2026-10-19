<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Controllers;

use App\Modules\Registration\Http\Requests\CreateAttendeeRequest;
use App\Modules\Registration\Http\Resources\AttendeeResource;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\QRBadge;
use App\Modules\Registration\Services\AttendeeService;
use App\Modules\Registration\Services\EventService;
use App\Modules\Registration\Services\QRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendeeController extends Controller
{
    public function __construct(
        protected AttendeeService $attendeeService,
        protected EventService $eventService,
        protected QRService $qrService
    ) {}

    /**
     * Register an attendee for an event.
     */
    public function store(CreateAttendeeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $event = $this->eventService->resolve([
            'event_id' => $validated['event_id'] ?? null,
            'event_name' => $validated['event_name'] ?? $request->input('event_name'),
        ]);

        if (! $event) {
            return response()->json([
                'success' => false,
                'message' => 'The event could not be found.',
                'errors' => [
                    'event_id' => ['The event could not be found.'],
                ],
            ], 422);
        }

        $attendee = $this->findRegisteredAttendee($event->id, $validated);

        if ($attendee) {
            // The very same attendee is registered again: answer with the
            // registration that already exists instead of a second one.
            $token = $this->attendeeService->issueToken($attendee);

            $payload = AttendeeResource::make($attendee->load(['event', 'printJobs']))->resolve($request);

            return response()->json([
                'success' => true,
                'message' => 'Attendee is already registered for this event',
                'attendee' => $payload,
                'data' => $payload,
                'qr_token' => $token,
                'qr_code' => $token,
                'qr_code_url' => $this->qrService->generateQRCode($token),
                'print_job' => $attendee->printJobs()->latest()->first(),
            ], 200);
        }

        $result = $this->attendeeService->register(
            $this->attendeeData($validated, $event->id),
            $request->user()
        );

        $attendee = $result['attendee'];
        $token = $result['token'];

        $payload = AttendeeResource::make($attendee)->resolve($request);

        return response()->json([
            'success' => true,
            'message' => 'Attendee registered successfully',
            'attendee' => $payload,
            'data' => $payload,
            'qr_token' => $token,
            'qr_code' => $token,
            'qr_code_url' => $this->qrService->generateQRCode($token),
            'print_job' => $result['print_job'],
        ], 201);
    }

    /**
     * List the attendees of an event.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Attendee::query()->with(['event', 'printJobs'])->latest();

        if ($request->filled('event_id')) {
            $query->where('event_id', (int) $request->input('event_id'));
        }

        if ($request->filled('event_name')) {
            $event = $this->eventService->findByName((string) $request->input('event_name'));

            $query->where('event_id', $event?->id ?? 0);
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper((string) $request->input('status')));
        }

        $attendees = $query->get()
            ->map(fn (Attendee $attendee) => AttendeeResource::make($attendee)->resolve($request))
            ->all();

        return response()->json([
            'success' => true,
            'attendees' => $attendees,
            'data' => $attendees,
        ]);
    }

    /**
     * Show a single attendee.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $attendee = Attendee::with(['event', 'printJobs'])->find($id);

        if (! $attendee) {
            return response()->json([
                'success' => false,
                'message' => 'Attendee not found',
            ], 404);
        }

        $payload = AttendeeResource::make($attendee)->resolve($request);

        return response()->json([
            'success' => true,
            'message' => 'Attendee retrieved successfully',
            'attendee' => $payload,
            'data' => $payload,
        ]);
    }

    /**
     * Issue a QR token for an attendee.
     *
     * Every call rotates the badge: a fresh token is generated, the stored
     * hash and the expiry timestamps are updated and the previous token stops
     * being the active one.
     */
    public function qr(Request $request, int $id): JsonResponse
    {
        $attendee = Attendee::with(['event', 'printJobs'])->find($id);

        if (! $attendee) {
            return response()->json([
                'success' => false,
                'message' => 'Attendee not found',
            ], 404);
        }

        $token = $this->attendeeService->issueToken($attendee);

        $attendee->refresh();

        $payload = AttendeeResource::make($attendee)->resolve($request);

        return response()->json([
            'success' => true,
            'message' => 'QR token generated successfully',
            'qr_token' => $token,
            'qr_code' => $token,
            'qr_code_url' => $this->qrService->generateQRCode($token),
            'attendee' => $payload,
            'data' => array_merge($payload, [
                'qr_token' => $token,
                'qr_code' => $token,
                'qr_code_url' => $this->qrService->generateQRCode($token),
            ]),
        ]);
    }

    /**
     * Age the QR token of an attendee out.
     *
     * The endpoint exists so a black box test can observe the expired token
     * path: the token itself stays the same, only its expiry moves into the
     * past. It is refused outside of the testing environments.
     */
    public function expireQr(Request $request, int $id): JsonResponse
    {
        if (! app()->environment((array) config('registration.qr.test_mode_envs', ['testing', 'local']))) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available outside of production.',
            ], 403);
        }

        $attendee = Attendee::find($id);

        if (! $attendee) {
            return response()->json([
                'success' => false,
                'message' => 'Attendee not found',
            ], 404);
        }

        $expiredAt = now()->subMinute();

        $attendee->forceFill([
            'qr_token_expires_at' => $expiredAt,
        ])->save();

        QRBadge::where('attendee_id', $attendee->id)->update(['expires_at' => $expiredAt]);

        return response()->json([
            'success' => true,
            'message' => 'QR token expired',
            'attendee_id' => $attendee->id,
            'qr_token_expires_at' => $expiredAt->toISOString(),
        ]);
    }

    /**
     * The attendee that is already registered with the same contact details.
     *
     * @param  array<string, mixed>  $validated
     */
    protected function findRegisteredAttendee(int $eventId, array $validated): ?Attendee
    {
        if (empty($validated['email']) || empty($validated['phone'])) {
            return null;
        }

        return Attendee::where('event_id', $eventId)
            ->where('email', $validated['email'])
            ->where('phone', $validated['phone'])
            ->first();
    }

    /**
     * Build the attendee payload out of the validated request.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function attendeeData(array $validated, int $eventId): array
    {
        $firstName = $validated['first_name'] ?? null;
        $lastName = $validated['last_name'] ?? null;

        $name = $validated['name'] ?? null;

        if (! $name) {
            $name = trim(implode(' ', array_filter([$firstName, $lastName])));
        }

        return array_merge($validated, [
            'event_id' => $eventId,
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }
}
