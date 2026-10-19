<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Http\Controllers;

use App\Modules\Exhibition\Models\Booth;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BoothController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List the booths.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booth::query();

        if ($request->filled('event_id')) {
            $query->where('event_id', (int) $request->input('event_id'));
        }

        if ($request->filled('exhibitor_id')) {
            $query->where('exhibitor_id', (int) $request->input('exhibitor_id'));
        }

        if ($request->filled('name')) {
            $name = (string) $request->input('name');
            $query->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        }

        if ($request->filled('number')) {
            $query->where('number', (string) $request->input('number'));
        }

        $booths = $query->orderBy('id')->get();

        $data = $booths->map(fn (Booth $booth) => $this->serialize($booth))->values();

        return response()->json([
            'booths' => $data,
            'data' => $data,
            'count' => $data->count(),
            'success' => true,
        ]);
    }

    /**
     * Register a new booth.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'exhibitor_id' => ['required', 'integer', 'exists:exhibitors,id'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'number' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'layout_config' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $booth = Booth::create([
                'exhibitor_id' => (int) $data['exhibitor_id'],
                'event_id' => (int) $data['event_id'],
                'number' => $data['number'],
                'name' => $data['name'] ?? null,
                'section' => $data['section'] ?? null,
                'location' => $data['location'] ?? null,
                'layout_config' => $data['layout_config'] ?? null,
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'number' => ['The booth number has already been taken for this event.'],
                ]);
            }

            throw $exception;
        }

        $payload = $this->serialize($booth);

        return response()->json([
            'message' => 'Booth created successfully',
            'success' => true,
            'booth' => $payload,
            'data' => $payload,
        ], 201);
    }

    /**
     * Show a single booth.
     */
    public function show(Request $request, string $booth): JsonResponse
    {
        $model = Booth::find((int) $booth);

        if (! $model) {
            return response()->json(['message' => 'Booth not found'], 404);
        }

        $payload = $this->serialize($model);

        return response()->json([
            'booth' => $payload,
            'data' => $payload,
            'success' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(Booth $booth): array
    {
        return [
            'id' => $booth->id,
            'exhibitor_id' => $booth->exhibitor_id,
            'event_id' => $booth->event_id,
            'number' => $booth->number,
            'name' => $booth->name,
            'section' => $booth->section,
            'location' => $booth->location,
            'layout_config' => $booth->layout_config,
            'created_at' => $booth->created_at?->toISOString(),
            'updated_at' => $booth->updated_at?->toISOString(),
        ];
    }

    protected function isUniqueViolation(QueryException $exception): bool
    {
        return $exception->getCode() === '23505';
    }
}
