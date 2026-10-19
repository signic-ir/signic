<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Contracts;

interface TurnstileServiceInterface
{
    /**
     * Process a check-in scan event.
     *
     * The turnstile may be referenced by id or by name. The operation is
     * idempotent and race-condition safe: a second scan of an attendee that is
     * already checked in fails without recording another scan event.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function checkIn(
        string $qrToken,
        int|string $turnstileId,
        array $metadata = [],
        ?string $ipAddress = null,
        string $eventType = 'CHECK_IN'
    ): object;

    /**
     * Process a check-out scan event.
     *
     * The turnstile may be referenced by id or by name. A check-out is only
     * valid for an attendee that is currently checked in.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function checkOut(
        string $qrToken,
        int|string $turnstileId,
        array $metadata = [],
        ?string $ipAddress = null,
        string $eventType = 'CHECK_OUT'
    ): object;

    /**
     * Get current attendance status for an attendee.
     */
    public function getStatus(int $attendeeId): object;
}
