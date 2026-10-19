<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'attendee_id' => $this->attendee_id,
            'scanned_at' => $this->scanned_at?->toISOString(),
            'location' => $this->location,
            'notes' => $this->notes,
            'interest_level' => $this->interest_level,
            'follow_up_sent' => $this->follow_up_sent,
            'contacted' => $this->contacted,
            'tags' => $this->tags->map(fn ($tag) => [
                'tag_name' => $tag->tag_name,
                'color' => $tag->color,
                'note' => $tag->note,
            ]),
            'attendee' => [
                'id' => $this->attendee->id,
                'name' => $this->attendee->name,
                'phone' => $this->attendee->phone,
                'email' => $this->attendee->email,
                'company' => $this->attendee->company,
                'ticket_type' => $this->attendee->ticket_type,
                'checked_in_at' => $this->attendee->checkin_at?->toISOString(),
            ],
        ];
    }
}