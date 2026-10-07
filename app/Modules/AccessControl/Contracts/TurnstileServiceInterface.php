<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Contracts;

interface TurnstileServiceInterface
{
    /**
     * Process a check-in scan event.
     * Must be idempotent and race-condition safe.
     */
    public function checkIn(string $qrToken, string $turnstileId, array $metadata = []): object;

    /**
     * Process a check-out scan event.
     * Must be idempotent and race-condition safe.
     */
    public function checkOut(string $qrToken, string $turnstileId, array $metadata = []): object;

    /**
     * Get current attendance status for an attendee.
     */
    public function getStatus(int $attendeeId): object;
}