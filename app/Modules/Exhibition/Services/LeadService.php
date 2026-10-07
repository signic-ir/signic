<?php

declare(strict_types=1);

namespace App\Exhibition\Services;

use App\Exhibition\Contracts\LeadServiceInterface;
use App\Exhibition\DTOs\LeadCreationDTO;
use App\Exhibition\Models\Lead;

class LeadService implements LeadServiceInterface
{
    public function createLead(int $exhibitorId, LeadCreationDTO $dto): Lead
    {
        // Check if attendee is currently checked in (valid scan)
        $attendee = \App\Registration\Models\Attendee::findOrFail($dto->attendeeId);

        if (!$attendee->checkin_at) {
            throw new \Exception('Attendee has not checked in');
        }

        $lead = Lead::create([
            'exhibitor_id' => $exhibitorId,
            'attendee_id' => $dto->attendeeId,
            'scanned_at' => now(),
            'location' => $dto->location,
            'notes' => $dto->notes,
            'interest_level' => $dto->interestLevel,
            'additional_data' => $dto->additionalData,
        ]);

        return $lead->load('tags');
    }

    public function getLeads(int $exhibitorId, array $filters = []): \Illuminate\Support\Collection
    {
        $query = Lead::query()
            ->where('exhibitor_id', $exhibitorId)
            ->with('attendee');

        if (isset($filters['interest_level'])) {
            $query->where('interest_level', $filters['interest_level']);
        }

        if (isset($filters['contacted'])) {
            $query->where('contacted', $filters['contacted']);
        }

        return $query->get();
    }
}