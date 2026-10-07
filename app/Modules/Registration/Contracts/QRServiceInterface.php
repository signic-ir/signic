<?php

declare(strict_types=1);

namespace App.Modules\Registration\Contracts;

interface QRServiceInterface
{
    /**
     * Generate a secure QR token for an attendee.
     */
    public function generate(int $attendeeId): string;

    /**
     * Validate and decode a QR token.
     */
    public function validate(string $token): ?int;

    /**
     * Generate a QR code image data URL for the given token.
     */
    public function generateQRCode(string $token): string;
}