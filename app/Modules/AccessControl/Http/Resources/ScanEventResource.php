<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ScanEventResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'attendee_id' => $this->attendee_id,
            'turnstile_id' => $this->turnstile_id,
            'event_type' => $this->event_type,
            'result' => $this->result,
            'ip_address' => $this->ip_address,
            'metadata' => $this->metadata,
            'scanned_at' => $this->scanned_at?->toISOString(),
            'attendee' => [
                'id' => $this->attendee->id,
                'name' => $this->attendee->name,
                'phone' => $this->attendee->phone,
            ],
        ];
    }
}