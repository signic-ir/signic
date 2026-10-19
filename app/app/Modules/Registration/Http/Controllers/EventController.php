<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Controllers;

use App\Modules\Registration\Http\Requests\CreateEventRequest;
use App\Modules\Registration\Models\Event;
use App\Modules\Registration\Services\EventCleanupService;
use App\Modules\Registration\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(
        protected EventService $eventService,
        protected EventCleanupService $cleanupService
    ) {}

    /**
     * List the events.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()->withCount('attendees')->latest();

        if ($request->filled('name')) {
            $query->where('name', (string) $request->input('name'));
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper((string) $request->input('status')));
        }

        $events = $query->get()
            ->map(fn (Event $event) => $this->eventData($event))
            ->all();

        return response()->json([
            'success' => true,
            'events' => $events,
            'data' => $events,
        ]);
    }

    /**
     * Create an event.
     */
    public function store(CreateEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create($request->validated());

        $payload = $this->eventData($event->loadCount('attendees'));

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'event' => $payload,
            'data' => $payload,
        ], 201);
    }

    /**
     * Show a single event.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $event = Event::withCount('attendees')->find($id);

        if (! $event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        $payload = $this->eventData($event);

        return response()->json([
            'success' => true,
            'message' => 'Event retrieved successfully',
            'event' => $payload,
            'data' => $payload,
        ]);
    }

    /**
     * The analytics report of an event.
     *
     * Without an event id the report is aggregated over every event, the
     * event may also be selected through the "name" query parameter.
     */
    public function report(Request $request, ?int $id = null): JsonResponse
    {
        $event = null;

        if ($id) {
            $event = $this->eventService->findById($id);
        } elseif ($request->filled('name')) {
            $event = $this->eventService->findByName((string) $request->input('name'));
        } elseif ($request->filled('event_id')) {
            $event = $this->eventService->findById((int) $request->input('event_id'));
        }

        if ($id && ! $event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        $report = $this->eventService->report($event);

        return response()->json(array_merge([
            'success' => true,
            'message' => 'Event report generated successfully',
            'report' => $report,
            'data' => $report,
        ], $report));
    }

    /**
     * Archive the expired events and invalidate the expired QR tokens.
     */
    public function cleanup(): JsonResponse
    {
        $summary = $this->cleanupService->run();

        return response()->json(array_merge([
            'success' => true,
            'message' => 'Expired event cleanup finished',
            'report' => $summary,
            'data' => $summary,
        ], $summary));
    }

    /**
     * The payload of an event.
     *
     * @return array<string, mixed>
     */
    protected function eventData(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
            'description' => $event->description,
            'status' => $event->status,
            'starts_at' => $event->starts_at?->toISOString(),
            'ends_at' => $event->ends_at?->toISOString(),
            'registration_start_at' => $event->registration_start_at?->toISOString(),
            'registration_end_at' => $event->registration_end_at?->toISOString(),
            'max_capacity' => $event->max_capacity,
            'is_registration_open' => $event->is_registration_open,
            'settings' => $event->settings,
            'attendees_count' => $event->attendees_count ?? null,
            'created_at' => $event->created_at?->toISOString(),
            'updated_at' => $event->updated_at?->toISOString(),
        ];
    }
}
