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
            'exhibitor_id' => $this->exhibitor_id,
            'attendee_id' => $this->attendee_id,
            'booth_id' => $this->booth_id,
            'scanned_at' => $this->scanned_at?->toISOString(),
            'location' => $this->location,
            'notes' => $this->notes,
            'interest_level' => $this->interest_level,
            'status' => $this->status,
            'follow_up_sent' => (bool) $this->follow_up_sent,
            'contacted' => (bool) $this->contacted,
            'additional_data' => $this->additional_data,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'attendee' => $this->attendee ? [
                'id' => $this->attendee->id,
                'name' => $this->attendee->name,
                'phone' => $this->attendee->phone,
                'email' => $this->attendee->email,
                'company' => $this->attendee->company,
                'ticket_type' => $this->attendee->ticket_type,
                'status' => $this->attendee->status,
                'checked_in_at' => $this->attendee->checkin_at?->toISOString(),
            ] : null,
            'booth' => $this->booth ? [
                'id' => $this->booth->id,
                'name' => $this->booth->name,
                'number' => $this->booth->number,
                'section' => $this->booth->section,
                'location' => $this->booth->location,
            ] : null,
            'exhibitor' => $this->exhibitor ? [
                'id' => $this->exhibitor->id,
                'company_name' => $this->exhibitor->company_name,
                'status' => $this->exhibitor->status,
            ] : null,
            'tags' => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'tag_name' => $tag->tag_name,
                'color' => $tag->color,
                'note' => $tag->note,
            ])->values(),
        ];
    }
}
