<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendeeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'company' => $this->company,
            'job_title' => $this->job_title,
            'ticket_type' => $this->ticket_type,
            'status' => $this->status,
            'badge_printed' => $this->badge_printed,
            'qr_token_hash' => $this->qr_token_hash,
            'checkin_at' => $this->checkin_at?->toISOString(),
            'checkout_at' => $this->checkout_at?->toISOString(),
            'metadata' => $this->metadata,
            'event' => [
                'id' => $this->event->id,
                'name' => $this->event->name,
            ],
        ];
    }
}