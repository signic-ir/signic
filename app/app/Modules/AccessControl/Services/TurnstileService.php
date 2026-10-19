<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Services;

use App\Modules\AccessControl\Contracts\TurnstileServiceInterface;
use App\Modules\Registration\Contracts\QRServiceInterface;
use App\Modules\Registration\Models\Attendee;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class TurnstileService implements TurnstileServiceInterface
{
    const LOCK_PREFIX = 'signic_lock_turnstile_';
    const CHECKIN_PREFIX = 'signic_checkin_';
    const CHECKOUT_PREFIX = 'signic_checkout_';

    protected int $lockTimeout;

    public function __construct()
    {
        $this->lockTimeout = 30; // 30 seconds lock
    }

    public function checkIn(string $qrToken, string $turnstileId, array $metadata = []): object
    {
        // Validate QR token first
        $attendeeId = app(QRServiceInterface::class)->validate($qrToken);

        if (! $attendeeId) {
            return (object) [
                'success' => false,
                'message' => 'Invalid or expired QR token',
                'result' => 'invalid',
            ];
        }

        $lockKey = self::LOCK_PREFIX . $turnstileId;

        // Use Redis atomic lock to prevent race conditions
        $lock = Redis::lock($lockKey, 10);

        try {
            if (! $lock->get()) {
                return (object) [
                    'success' => false,
                    'message' => 'Turnstile is busy. Please try again.',
                    'result' => 'busy',
                ];
            }

            // Check if already checked in (idempotency)
            $checkinKey = self::CHECKIN_PREFIX . $qrToken;
            $existing = Cache::get($checkinKey);

            if ($existing) {
                return (object) [
                    'success' => true,
                    'message' => 'Already checked in',
                    'result' => 'duplicate',
                    'checked_in_at' => $existing,
                ];
            }

            // Perform check-in
            $checkinData = [
                'qr_token' => $qrToken,
                'turnstile_id' => $turnstileId,
                'checked_in_at' => now()->toISOString(),
                'metadata' => $metadata,
            ];

            Cache::put($checkinKey, $checkinData['checked_in_at'], now()->addDays(30));

            // Update attendee check-in timestamp
            Attendee::where('id', $attendeeId)->update(['checkin_at' => now()]);

            // Emit domain event for check-in (for module communication)
            event(new \App\Modules\AccessControl\Events\CheckInEvent($checkinData));

            return (object) [
                'success' => true,
                'message' => 'Check-in successful',
                'result' => 'success',
                'checked_in_at' => $checkinData['checked_in_at'],
            ];

        } finally {
            optional($lock)->release();
        }
    }

    public function checkOut(string $qrToken, string $turnstileId, array $metadata = []): object
    {
        // Validate QR token first
        $attendeeId = app(QRServiceInterface::class)->validate($qrToken);

        if (! $attendeeId) {
            return (object) [
                'success' => false,
                'message' => 'Invalid or expired QR token',
                'result' => 'invalid',
            ];
        }

        $lockKey = self::LOCK_PREFIX . $turnstileId;

        $lock = Redis::lock($lockKey, 10);

        try {
            if (! $lock->get()) {
                return (object) [
                    'success' => false,
                    'message' => 'Turnstile is busy. Please try again.',
                    'result' => 'busy',
                ];
            }

            $checkoutKey = self::CHECKOUT_PREFIX . $qrToken;
            $existing = Cache::get($checkoutKey);

            if ($existing) {
                return (object) [
                    'success' => true,
                    'message' => 'Already checked out',
                    'result' => 'duplicate',
                    'checked_out_at' => $existing,
                ];
            }

            $checkoutData = [
                'qr_token' => $qrToken,
                'turnstile_id' => $turnstileId,
                'checked_out_at' => now()->toISOString(),
                'metadata' => $metadata,
            ];

            Cache::put($checkoutKey, $checkoutData['checked_out_at'], now()->addDays(30));

            // Update attendee check-out timestamp
            Attendee::where('id', $attendeeId)->update(['checkout_at' => now()]);

            event(new \App\Modules\AccessControl\Events\CheckOutEvent($checkoutData));

            return (object) [
                'success' => true,
                'message' => 'Check-out successful',
                'result' => 'success',
                'checked_out_at' => $checkoutData['checked_out_at'],
            ];

        } finally {
            optional($lock)->release();
        }
    }

    public function getStatus(int $attendeeId): object
    {
        // Return attendance status
        return (object) [
            'attendee_id' => $attendeeId,
            'checkedIn' => Cache::has(self::CHECKIN_PREFIX . $attendeeId),
            'checkedOut' => Cache::has(self::CHECKOUT_PREFIX . $attendeeId),
        ];
    }
}