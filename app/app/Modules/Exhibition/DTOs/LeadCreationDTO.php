<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\DTOs;

class LeadCreationDTO
{
    /**
     * @param  array<string, mixed>  $additionalData
     */
    public function __construct(
        public readonly int $attendeeId,
        public readonly ?string $location,
        public readonly ?string $notes,
        public readonly ?string $interestLevel,
        public readonly array $additionalData = [],
        public readonly ?int $boothId = null
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(array $validated): self
    {
        return new self(
            attendeeId: (int) ($validated['attendee_id'] ?? 0),
            location: $validated['location'] ?? null,
            notes: $validated['notes'] ?? null,
            interestLevel: $validated['interest_level'] ?? 'MEDIUM',
            additionalData: $validated['additional_data'] ?? [],
            boothId: isset($validated['booth_id']) ? (int) $validated['booth_id'] : null
        );
    }
}
