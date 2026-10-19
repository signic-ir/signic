<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Http\Controllers;

use App\Modules\AccessControl\Http\Controllers\Concerns\HandlesScanRequests;
use App\Modules\AccessControl\Http\Resources\ScanEventResource;
use App\Modules\AccessControl\Models\ScanEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanEventController extends Controller
{
    use HandlesScanRequests;

    /**
     * List the recorded scan events, optionally filtered.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attendee_id' => ['nullable', 'integer'],
            'event_type' => ['nullable', 'string', 'max:50'],
            'turnstile_id' => ['nullable', 'integer'],
            'result' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $query = ScanEvent::query()->with('attendee');

        if (isset($validated['attendee_id'])) {
            $query->where('attendee_id', $validated['attendee_id']);
        }

        if (! empty($validated['event_type'])) {
            $query->where('event_type', $this->normalizeEventType($validated['event_type']));
        }

        if (isset($validated['turnstile_id'])) {
            $query->where('turnstile_id', $validated['turnstile_id']);
        }

        if (! empty($validated['result'])) {
            $query->where('result', strtoupper(trim($validated['result'])));
        }

        $scanEvents = $query->orderBy('id')
            ->limit($validated['limit'] ?? 200)
            ->get();

        return response()->json([
            'scan_events' => ScanEventResource::collection($scanEvents)->resolve(),
            'count' => $scanEvents->count(),
        ]);
    }
}
