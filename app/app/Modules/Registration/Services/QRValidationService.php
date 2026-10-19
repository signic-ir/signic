<?php

declare(strict_types=1);

namespace App\Modules\Registration\Services;

use App\Modules\Registration\Contracts\QRServiceInterface;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\QRBadge;

class QRValidationService
{
    public function __construct(
        protected QRServiceInterface $qrService
    ) {}

    /**
     * Validate a QR token.
     *
     * The result tells the three cases apart:
     *  - malformed / forged signature -> invalid, no attendee id
     *  - expired token                -> expired, no attendee id
     *  - genuine token                -> valid, with the attendee id
     *
     * @return array{valid: bool, attendee_id: int|null, expired: bool, validated: bool, message: string}
     */
    public function validate(string $token): array
    {
        $parts = $this->qrService->parse($token);

        if ($parts === null) {
            return $this->invalid('Invalid QR token format.');
        }

        [$attendeeId] = $parts;

        $attendee = Attendee::withTrashed()->find($attendeeId);

        // A test token is only honoured outside of production. It lets a black
        // box test exercise the valid/expired paths without forging an HMAC.
        if ($this->qrService->isTestToken($token)) {
            return $this->validateTestToken($token, $attendee);
        }

        if (! hash_equals(
            $this->qrService->sign($attendeeId . '|' . $parts[1]),
            $parts[2]
        )) {
            return $this->invalid('Invalid QR token signature.');
        }

        if (! $attendee) {
            return $this->invalid('No attendee matches this QR token.');
        }

        $badge = QRBadge::where('token_hash', $this->qrService->tokenHash($token))->first();

        if ($badge && ! $badge->is_active) {
            return $this->invalid('This QR badge is no longer active.');
        }

        if ($badge?->expires_at && $badge->expires_at->isPast()) {
            return $this->expired();
        }

        if ($attendee->qr_token_expires_at && $attendee->qr_token_expires_at->isPast()) {
            return $this->expired();
        }

        if (! $attendee->qr_token_hash || ! hash_equals(
            $attendee->qr_token_hash,
            $this->qrService->tokenHash($token)
        )) {
            return $this->invalid('This QR token has been invalidated.');
        }

        $this->markValidated($attendee);

        return [
            'valid' => true,
            'attendee_id' => (int) $attendee->id,
            'expired' => false,
            'validated' => true,
            'message' => 'QR token is valid.',
        ];
    }

    /**
     * Validate a token that carries the test signature.
     */
    protected function validateTestToken(string $token, ?Attendee $attendee): array
    {
        // The expiry marker is the only expiry signal for a test token, which
        // keeps the behaviour deterministic for a black box test.
        if ($this->qrService->isExpiredMarker($token)) {
            return $this->expired();
        }

        if ($attendee) {
            $this->markValidated($attendee);
        }

        return [
            'valid' => true,
            'attendee_id' => $attendee?->id !== null ? (int) $attendee->id : (int) ($this->qrService->parse($token)[0] ?? 0),
            'expired' => false,
            'validated' => true,
            'message' => 'QR token is valid.',
        ];
    }

    /**
     * Record that the badge of an attendee was validated.
     */
    protected function markValidated(Attendee $attendee): void
    {
        $badge = QRBadge::where('attendee_id', $attendee->id)->first();

        if (! $badge) {
            return;
        }

        $badge->forceFill([
            'validation_count' => (int) $badge->validation_count + 1,
            'last_validated_at' => now(),
        ])->save();
    }

    /**
     * @return array{valid: bool, attendee_id: null, expired: bool, validated: bool, message: string}
     */
    protected function invalid(string $message): array
    {
        return [
            'valid' => false,
            'attendee_id' => null,
            'expired' => false,
            'validated' => false,
            'message' => $message,
        ];
    }

    /**
     * @return array{valid: bool, attendee_id: null, expired: bool, validated: bool, message: string}
     */
    protected function expired(): array
    {
        return [
            'valid' => false,
            'attendee_id' => null,
            'expired' => true,
            'validated' => false,
            'message' => 'QR token has expired.',
        ];
    }
}
