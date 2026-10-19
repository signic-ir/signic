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
            'created_by_user_id' => $this->created_by_user_id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'company' => $this->company,
            'job_title' => $this->job_title,
            'dietary_requirements' => $this->dietary_requirements,
            'accessibility_needs' => $this->accessibility_needs,
            'special_instructions' => $this->special_instructions,
            'ticket_type' => $this->ticket_type,
            'status' => $this->status,
            'badge_printed' => $this->badge_printed,
            'qr_token_hash' => $this->qr_token_hash,
            'qr_token_generated_at' => $this->qr_token_generated_at?->toISOString(),
            'qr_token_expires_at' => $this->qr_token_expires_at?->toISOString(),
            'checkin_at' => $this->checkin_at?->toISOString(),
            'checkout_at' => $this->checkout_at?->toISOString(),
            'badge_printed_at' => $this->badge_printed_at?->toISOString(),
            'metadata' => $this->metadata,
            'print_jobs' => $this->printJobs->map(fn ($job) => [
                'id' => $job->id,
                'attendee_id' => $job->attendee_id,
                'status' => $job->status,
                'qr_code' => $job->qr_code,
                'badge_data' => $job->badge_data,
                'print_settings' => $job->print_settings,
                'printed_at' => $job->printed_at?->toISOString(),
                'failed_at' => $job->failed_at?->toISOString(),
                'failure_reason' => $job->failure_reason,
                'printer_name' => $job->printer_name,
                'created_at' => $job->created_at?->toISOString(),
                'updated_at' => $job->updated_at?->toISOString(),
            ])->all(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'name' => $this->event->name,
                'slug' => $this->event->slug,
                'status' => $this->event->status,
            ]),
        ];
    }
}
