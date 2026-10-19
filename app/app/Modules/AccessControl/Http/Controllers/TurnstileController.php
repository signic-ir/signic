<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Http\Controllers;

use App\Modules\AccessControl\Models\Turnstile;
use App\Modules\Registration\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TurnstileController extends Controller
{
    /**
     * List the turnstiles, optionally filtered by event, name or state.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $query = Turnstile::query();

        if (isset($validated['event_id'])) {
            $query->where('event_id', $validated['event_id']);
        }

        if (isset($validated['name'])) {
            $query->where('name', $validated['name']);
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
        }

        $turnstiles = $query->orderBy('id')->get();

        return response()->json([
            'turnstiles' => $turnstiles->map(fn (Turnstile $turnstile) => $this->transform($turnstile))->all(),
            'count' => $turnstiles->count(),
        ]);
    }

    /**
     * Create a turnstile.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required_without:event,event_name', 'nullable', 'integer', 'exists:events,id'],
            'event' => ['nullable', 'string', 'max:255'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'hardware_model' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $eventId = $validated['event_id'] ?? $this->resolveEventId($validated);

        if (! $eventId) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
                'data' => [
                    'success' => false,
                    'message' => 'Event not found',
                    'result' => 'NOT_FOUND',
                ],
            ], 404);
        }

        $turnstile = Turnstile::create([
            'event_id' => $eventId,
            'name' => $validated['name'],
            'location' => $validated['location'] ?? null,
            'type' => $validated['type'] ?? 'gate',
            'ip_address' => $validated['ip_address'] ?? null,
            'hardware_model' => $validated['hardware_model'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'config' => $validated['config'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Turnstile created successfully',
            'turnstile' => $this->transform($turnstile),
        ], 201);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function resolveEventId(array $validated): ?int
    {
        $name = $validated['event'] ?? $validated['event_name'] ?? null;

        if (! $name) {
            return null;
        }

        return Event::where('name', $name)->value('id')
            ?? Event::where('slug', $name)->value('id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(Turnstile $turnstile): array
    {
        return [
            'id' => $turnstile->id,
            'event_id' => $turnstile->event_id,
            'name' => $turnstile->name,
            'location' => $turnstile->location,
            'type' => $turnstile->type,
            'ip_address' => $turnstile->ip_address,
            'hardware_model' => $turnstile->hardware_model,
            'is_active' => (bool) $turnstile->is_active,
            'config' => $turnstile->config,
            'created_at' => $turnstile->created_at?->toISOString(),
            'updated_at' => $turnstile->updated_at?->toISOString(),
        ];
    }
}
