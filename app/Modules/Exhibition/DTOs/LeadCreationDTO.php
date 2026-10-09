<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\DTOs;

class LeadCreationDTO
{
    public function __construct(
        public readonly string $attendeeId,
        public readonly string $location,
        public readonly ?string $notes,
        public readonly string $interestLevel,
        public readonly array $additionalData
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            attendeeId: $validated['attendee_id'],
            location: $validated['location'] ?? null,
            notes: $validated['notes'] ?? null,
            interestLevel: $validated['interest_level'] ?? 'medium',
            additionalData: $validated['additional_data'] ?? []
        );
    }
}