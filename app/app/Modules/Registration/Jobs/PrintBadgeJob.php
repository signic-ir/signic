<?php

declare(strict_types=1);

namespace App\Modules\Registration\Jobs;

use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\PrintJob;
use App\Modules\AccessControl\Services\TurnstileService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Dispatchable;

class PrintBadgeJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * The attendee instance.
     */
    protected Attendee $attendee;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Attendee $attendee,
        protected string $token
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $printJob = $this->attendee->printJobs()->latest()->first();

        if (! $printJob) {
            \Log::warning('Print badge job: no print job found', ['attendee_id' => $this->attendee->id]);
            return;
        }

        $printJob->update([
            'status' => 'printing',
        ]);

        \Log::info('Printing badge', ['attendee_id' => $this->attendee->id, 'print_job_id' => $printJob->id]);
    }
}