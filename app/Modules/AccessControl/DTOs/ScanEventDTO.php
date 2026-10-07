<?php

declare(strict_types=1);

namespace App\AccessControl\DTOs;

class ScanEventDTO
{
    public function __construct(
        public readonly string $tokenHash,
        public readonly string $turnstileId,
        public readonly string $eventType,
        public readonly string $ip,
        public readonly array $metadata
    ) {}

    public static function fromRequest(array $validated, string $ip): self
    {
        return new self(
            tokenHash: $validated['token_hash'],
            turnstileId: $validated['turnstile_id'],
            eventType: $validated['event_type'],
            ip: $ip,
            metadata: $validated['metadata'] ?? []
        );
    }
}