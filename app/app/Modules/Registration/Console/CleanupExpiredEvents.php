<?php

declare(strict_types=1);

namespace App\Modules\Registration\Console;

use App\Modules\Registration\Services\EventCleanupService;
use Illuminate\Console\Command;

class CleanupExpiredEvents extends Command
{
    protected $signature = 'signic:cleanup-events';

    protected $description = 'Archive expired events and invalidate expired QR tokens';

    public function handle(EventCleanupService $cleanupService): int
    {
        $summary = $cleanupService->run();

        $this->info(sprintf(
            'Archived %d event(s), invalidated %d QR token(s), cleaned %d temporary record(s).',
            $summary['archived_events'],
            $summary['invalidated_qr_tokens'],
            $summary['cleaned_temporary_data']
        ));

        return self::SUCCESS;
    }
}
