<?php

declare(strict_types=1);

namespace App\Modules\Registration\Jobs;

use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\PrintJob;
use App\Modules\AccessControl\Services\TurnstileService;

class PrintBadgeJob
{
    /**
     * The attendee instance.
     */
    protected Attendee $attendee;

    /**
     * Create a new job instance.
     */
    public function __construct(Attendee $attendee)
    {
        $this->attendee = $attendee;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $printJob = PrintJob::create([
            'attendee_id' => $this->attendee->id,
            'status' => 'pending',
            'badge_data' => json_encode([
                'attendee_name' => $this->attendee->name,
                'company' => $this->attendee->company,
                'ticket_type' => $this->attendee->ticket_type,
                'event' => $this->attendee->event->name,
                'qr_token' => $this->attendee->qr_token_hash,
            ]),
            'print_settings' => json_encode(['size' => 'credit-card']),
        ]);

        \Log::info('Print badge job created', ['attendee_id' => $this->attendee->id, 'print_job_id' => $printJob->id]);
    }
}