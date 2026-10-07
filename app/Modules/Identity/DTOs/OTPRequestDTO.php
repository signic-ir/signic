<?php

declare(strict_types=1);

namespace App\Identity\DTOs;

class OTPRequestDTO
{
    public function __construct(
        public readonly string $phone,
        public readonly string $ip,
        public readonly string $userAgent
    ) {}

    public static function fromRequest(array $validated, string $ip, string $userAgent): self
    {
        return new self(
            phone: $validated['phone'],
            ip: $ip,
            userAgent: $userAgent
        );
    }
}