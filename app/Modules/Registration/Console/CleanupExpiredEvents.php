<?php

declare(strict_types=1);

namespace App\Modules\Registration\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Registration\Models\Event;

class CleanupExpiredEvents extends Command
{
    protected $signature = 'signic:cleanup-events';
    protected $description = 'Mark expired events as completed';

    public function handle(): int
    {
        Event::where('status', 'active')
            ->where('ends_at', '<', now())
            ->update(['status' => 'completed']);

        $this->info('Expired events marked as completed.');

        return self::SUCCESS;
    }
}