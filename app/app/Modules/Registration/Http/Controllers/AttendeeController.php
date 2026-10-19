<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Modules\Registration\DTOs\AttendeeCreationDTO;
use App\Modules\Registration\Contracts\QRServiceInterface;
use App\Modules\Registration\Http\Middleware\CheckAttendeeDuplicate;

class AttendeeController extends Controller
{
    public function __construct(
        protected QRServiceInterface $qrService
    ) {
        $this->middleware(CheckAttendeeDuplicate::class)->only(['store']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[\+]?[0-9]{10,15}$/'],
            'email' => ['nullable', 'email'],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'ticket_type' => ['required', 'in:general,vip,speaker,press,organizer,volunteer'],
            'metadata' => ['nullable', 'array'],
        ]);

        $dto = AttendeeCreationDTO::fromRequest($validated);

        $attendee = \App\Modules\Registration\Models\Attendee::create([
            'event_id' => $validated['event_id'],
            'user_id' => $request->user()->id,
            'phone' => $dto->phone,
            'name' => $dto->name,
            'email' => $dto->email,
            'company' => $dto->company,
            'job_title' => $dto->jobTitle,
            'ticket_type' => $dto->ticketType,
            'metadata' => $dto->metadata,
            'status' => \App\Modules\Registration\Enums\AttendeeStatus::Registered->value,
        ]);

        // Generate QR token
        $qrToken = $this->qrService->generate($attendee->id);
        $attendee->update([
            'qr_token_hash' => hash('sha256', $qrToken),
            'qr_token_generated_at' => now(),
            'qr_token_expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'message' => 'Attendee registered successfully',
            'attendee' => $attendee->load('qrBadge'),
            'qr_token' => $qrToken,
        ], 201);
    }

    public function qr(int $id): JsonResponse
    {
        $attendee = \App\Modules\Registration\Models\Attendee::where('user_id', request()->user()->id)
            ->where('id', $id)
            ->first();

        if (!$attendee) {
            return response()->json(['message' => 'Attendee not found'], 404);
        }

        return response()->json([
            'attendee' => $attendee,
            'qr_code_url' => $this->qrService->generateQRCode($attendee->qr_token_hash),
        ]);
    }
}