<?php

declare(strict_types=1);

namespace App\Modules\Registration\Services;

use App\Modules\Registration\Enums\EventStatus;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\Event;
use App\Modules\Registration\Models\PrintJob;
use App\Modules\Registration\Models\QRBadge;
use Illuminate\Support\Facades\DB;

class EventCleanupService
{
    /**
     * Archive the events that are over and invalidate the QR tokens that
     * expired.
     *
     * Nothing is deleted that reporting depends on: attendees, events and
     * leads are kept, only their temporary material (the badge material of
     * events that are long gone) is cleaned up.
     *
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $archiveBefore = now()->subDays((int) config('registration.cleanup.archive_after_days', 30));

        $archivedEvents = Event::where('ends_at', '<', $archiveBefore)
            ->whereNotIn('status', [EventStatus::Completed->value, EventStatus::Cancelled->value])
            ->update(['status' => EventStatus::Completed->value]);

        $expiredAttendees = Attendee::whereNotNull('qr_token_expires_at')
            ->where('qr_token_expires_at', '<', now())
            ->whereNotNull('qr_token_hash')
            ->get(['id', 'qr_token_hash']);

        $invalidatedQrTokens = 0;

        foreach ($expiredAttendees as $attendee) {
            $attendee->forceFill(['qr_token_hash' => null])->save();

            QRBadge::where('attendee_id', $attendee->id)->update(['is_active' => false]);

            $invalidatedQrTokens++;
        }

        $cleanedTemporaryData = $this->cleanTemporaryData($archiveBefore);

        return [
            'archived_events' => $archivedEvents,
            'invalidated_qr_tokens' => $invalidatedQrTokens,
            'cleaned_temporary_data' => $cleanedTemporaryData,
            'preserved_records' => [
                'events' => Event::count(),
                'attendees' => Attendee::count(),
                'leads' => DB::table('leads')->count(),
                'print_jobs' => PrintJob::count(),
            ],
        ];
    }

    /**
     * Remove the print jobs of events that were archived long ago.
     */
    protected function cleanTemporaryData($archiveBefore): int
    {
        return PrintJob::where('status', 'pending')
            ->where('created_at', '<', $archiveBefore)
            ->delete();
    }
}
