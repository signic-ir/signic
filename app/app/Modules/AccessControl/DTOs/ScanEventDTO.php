<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\DTOs;

class ScanEventDTO
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $qrToken,
        public readonly int|string $turnstileId,
        public readonly string $eventType,
        public readonly string $ip,
        public readonly array $metadata
    ) {}

    /**
     * The sha256 hash of the token, as stored on a scan event.
     */
    public function tokenHash(): string
    {
        return hash('sha256', $this->qrToken);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(array $validated, string $ip): self
    {
        $qrToken = (string) ($validated['qr_token'] ?? $validated['token_hash'] ?? '');

        $turnstile = $validated['turnstile_id']
            ?? $validated['turnstile']
            ?? $validated['turnstile_name']
            ?? '';

        return new self(
            qrToken: $qrToken,
            turnstileId: is_int($turnstile) ? $turnstile : (string) $turnstile,
            eventType: (string) ($validated['event_type'] ?? 'CHECK_IN'),
            ip: $ip,
            metadata: (array) ($validated['metadata'] ?? [])
        );
    }
}
