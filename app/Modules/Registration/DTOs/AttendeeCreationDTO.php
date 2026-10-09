<?php

declare(strict_types=1);

namespace App\Modules\Registration\DTOs;

class AttendeeCreationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $email,
        public readonly string $company,
        public readonly ?string $jobTitle,
        public readonly string $ticketType,
        public readonly array $metadata
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
            phone: $validated['phone'],
            email: $validated['email'] ?? null,
            company: $validated['company'] ?? null,
            jobTitle: $validated['job_title'] ?? null,
            ticketType: $validated['ticket_type'],
            metadata: $validated['metadata'] ?? []
        );
    }
}