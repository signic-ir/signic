<?php

declare(strict_types=1);

namespace App\Modules\Registration\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Registration\Contracts\QRServiceInterface;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\PrintJob;
use App\Modules\Registration\Models\QRBadge;

class AttendeeService
{
    public function __construct(
        protected QRServiceInterface $qrService
    ) {}

    /**
     * Register an attendee for an event and issue their badge material.
     *
     * @param  array<string, mixed>  $data
     * @return array{attendee: Attendee, token: string, print_job: PrintJob}
     */
    public function register(array $data, User $user): array
    {
        $attendee = Attendee::create([
            'event_id' => $data['event_id'],
            'user_id' => $data['user_id'] ?? $user->id,
            'created_by_user_id' => $data['created_by_user_id'] ?? $user->id,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'company' => $data['company'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'dietary_requirements' => $data['dietary_requirements'] ?? null,
            'accessibility_needs' => $data['accessibility_needs'] ?? null,
            'special_instructions' => $data['special_instructions'] ?? null,
            'ticket_type' => $data['ticket_type'],
            'status' => \App\Modules\Registration\Enums\AttendeeStatus::Confirmed->value,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $token = $this->issueToken($attendee);

        $printJob = $this->queuePrintJob($attendee, $token, $user);

        return [
            'attendee' => $attendee->refresh(),
            'token' => $token,
            'print_job' => $printJob,
        ];
    }

    /**
     * Issue a fresh QR token for the attendee.
     *
     * The plain token is only ever handed out through the API; the database
     * keeps its sha256 hash. Issuing a token rotates the badge: the previous
     * token stops being the attendee's active one.
     */
    public function issueToken(Attendee $attendee): string
    {
        $token = $this->qrService->generate($attendee->id);
        $expiresAt = now()->addHours((int) config('registration.qr.expiry_hours', 24));

        $attendee->forceFill([
            'qr_token_hash' => $this->qrService->tokenHash($token),
            'qr_token_generated_at' => now(),
            'qr_token_expires_at' => $expiresAt,
        ])->save();

        QRBadge::updateOrCreate(
            ['attendee_id' => $attendee->id],
            [
                'token_hash' => $this->qrService->tokenHash($token),
                'generated_at' => now(),
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]
        );

        return $token;
    }

    /**
     * Queue the badge print job for the attendee.
     */
    public function queuePrintJob(Attendee $attendee, string $token, ?User $user = null): PrintJob
    {
        return PrintJob::create([
            'attendee_id' => $attendee->id,
            'printed_by_user_id' => $user?->id,
            'status' => 'pending',
            'qr_code' => $token,
            'badge_data' => [
                'attendee_name' => $attendee->name,
                'company' => $attendee->company,
                'ticket_type' => $attendee->ticket_type,
                'event' => $attendee->event?->name,
                'qr_token_hash' => $this->qrService->tokenHash($token),
            ],
            'print_settings' => ['size' => 'credit-card'],
        ]);
    }
}
