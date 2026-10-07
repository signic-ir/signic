<?php

declare(strict_types=1);

namespace App\Exhibition\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exhibition\DTOs\LeadCreationDTO;
use App\Exhibition\Contracts\LeadServiceInterface;

class LeadController extends Controller
{
    public function __construct(
        protected LeadServiceInterface $leadService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attendee_id' => ['required', 'integer', 'exists:attendees,id'],
            'exhibitor_id' => ['required', 'integer', 'exists:exhibitors,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'interest_level' => ['nullable', 'in:low,medium,high'],
            'additional_data' => ['nullable', 'array'],
        ]);

        $dto = LeadCreationDTO::fromRequest($validated);

        $lead = $this->leadService->createLead($validated['exhibitor_id'], $dto);

        return response()->json([
            'message' => 'Lead created successfully',
            'lead' => $lead,
        ], 201);
    }
}