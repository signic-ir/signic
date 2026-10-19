<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Services;

use App\Modules\AccessControl\Contracts\TurnstileServiceInterface;
use App\Modules\AccessControl\Enums\ScanEventType;
use App\Modules\AccessControl\Enums\ScanResult;
use App\Modules\AccessControl\Events\CheckInEvent;
use App\Modules\AccessControl\Events\CheckOutEvent;
use App\Modules\AccessControl\Models\ScanEvent;
use App\Modules\AccessControl\Models\Turnstile;
use App\Modules\Registration\Contracts\QRServiceInterface;
use App\Modules\Registration\Enums\AttendeeStatus;
use App\Modules\Registration\Models\Attendee;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class TurnstileService implements TurnstileServiceInterface
{
    /**
     * Lock prefix for a single attendee. Serialises every scan of the same
     * attendee, no matter which turnstile it happens at.
     */
    public const ATTENDEE_LOCK_PREFIX = 'signic_lock_attendee_';

    /**
     * Lock prefix for a single turnstile.
     */
    public const TURNSTILE_LOCK_PREFIX = 'signic_lock_turnstile_';

    /**
     * How long a lock is held before Redis expires it (seconds).
     */
    protected int $lockTimeout = 10;

    /**
     * How long a competing scan waits for the attendee lock (seconds).
     */
    protected int $lockWaitSeconds = 5;

    public function __construct()
    {
        $this->lockTimeout = 10;
        $this->lockWaitSeconds = 5;
    }

    /**
     * Process a check-in scan.
     *
     * The database is the source of truth: an attendee is checked in when
     * `checkin_at` is set. The check and the write happen under a per attendee
     * lock, so two concurrent scans of the same badge cannot both succeed.
     */
    public function checkIn(
        string $qrToken,
        int|string $turnstileId,
        array $metadata = [],
        ?string $ipAddress = null,
        string $eventType = ScanEventType::Checkin->value
    ): object {
        $qrService = app(QRServiceInterface::class);
        $attendeeId = $qrService->validate($qrToken);

        if (! $attendeeId) {
            return $this->failure('Invalid QR token', ScanResult::Invalid, 422);
        }

        $turnstile = $this->resolveTurnstile($turnstileId);

        if (! $turnstile) {
            return $this->failure('Turnstile not found', ScanResult::NotFound, 404);
        }

        $forced = $this->isForced($eventType);
        $lock = Cache::lock(self::ATTENDEE_LOCK_PREFIX . $attendeeId, $this->lockTimeout);

        try {
            $lock->block($this->lockWaitSeconds);
        } catch (LockTimeoutException) {
            return $this->failure('Turnstile is busy. Please try again.', ScanResult::Duplicate, 409);
        }

        try {
            // Re-read the attendee inside the lock: this is the check-then-act
            // that makes concurrent scans safe.
            $attendee = Attendee::find($attendeeId);

            if (! $attendee) {
                return $this->failure('Attendee not found', ScanResult::NotFound, 404);
            }

            if ($attendee->checkin_at !== null && ! $forced) {
                return $this->failure('Attendee is already being checked-in', ScanResult::Duplicate, 409, [
                    'attendee_id' => $attendee->id,
                    'attendee_name' => $attendee->name,
                    'event_type' => $eventType,
                    'checked_in_at' => $attendee->checkin_at?->toISOString(),
                    'turnstile_id' => $turnstile->id,
                ]);
            }

            $scannedAt = now();

            $attendee->forceFill([
                'checkin_at' => $scannedAt,
                'status' => AttendeeStatus::CheckedIn->value,
            ])->save();

            $scanEvent = $this->recordScanEvent(
                $attendee->id,
                $turnstile->id,
                $qrToken,
                $eventType,
                $ipAddress,
                $metadata,
                $scannedAt
            );

            event(new CheckInEvent([
                'attendee_id' => $attendee->id,
                'attendee_name' => $attendee->name,
                'turnstile_id' => $turnstile->id,
                'event_type' => $eventType,
                'checked_in_at' => $scannedAt->toISOString(),
                'metadata' => $metadata,
            ]));

            return (object) [
                'success' => true,
                'message' => 'Check-in successful',
                'result' => ScanResult::Success->value,
                'attendee_id' => $attendee->id,
                'attendee_name' => $attendee->name,
                'event_type' => $eventType,
                'checked_in_at' => $scannedAt->toISOString(),
                'turnstile_id' => $turnstile->id,
                'scan_event_id' => $scanEvent->id,
                'http_status' => 200,
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Process a check-out scan.
     *
     * A check-out is only valid for an attendee that is currently checked in.
     */
    public function checkOut(
        string $qrToken,
        int|string $turnstileId,
        array $metadata = [],
        ?string $ipAddress = null,
        string $eventType = ScanEventType::Checkout->value
    ): object {
        $qrService = app(QRServiceInterface::class);
        $attendeeId = $qrService->validate($qrToken);

        if (! $attendeeId) {
            return $this->failure('Invalid QR token', ScanResult::Invalid, 422);
        }

        $turnstile = $this->resolveTurnstile($turnstileId);

        if (! $turnstile) {
            return $this->failure('Turnstile not found', ScanResult::NotFound, 404);
        }

        $forced = $this->isForced($eventType);
        $lock = Cache::lock(self::ATTENDEE_LOCK_PREFIX . $attendeeId, $this->lockTimeout);

        try {
            $lock->block($this->lockWaitSeconds);
        } catch (LockTimeoutException) {
            return $this->failure('Turnstile is busy. Please try again.', ScanResult::Duplicate, 409);
        }

        try {
            $attendee = Attendee::find($attendeeId);

            if (! $attendee) {
                return $this->failure('Attendee not found', ScanResult::NotFound, 404);
            }

            if ($attendee->checkout_at !== null && ! $forced) {
                return $this->failure('Attendee is already being checked-out', ScanResult::Duplicate, 409, [
                    'attendee_id' => $attendee->id,
                    'attendee_name' => $attendee->name,
                    'event_type' => $eventType,
                    'checked_out_at' => $attendee->checkout_at?->toISOString(),
                    'turnstile_id' => $turnstile->id,
                ]);
            }

            if ($attendee->checkin_at === null && ! $forced) {
                return $this->failure(
                    'Invalid check-out attempt: attendee has not been checked in',
                    ScanResult::Invalid,
                    409,
                    [
                        'attendee_id' => $attendee->id,
                        'attendee_name' => $attendee->name,
                        'event_type' => $eventType,
                        'turnstile_id' => $turnstile->id,
                    ]
                );
            }

            $scannedAt = now();

            $attendee->forceFill([
                'checkout_at' => $scannedAt,
                'status' => AttendeeStatus::CheckedOut->value,
            ])->save();

            $scanEvent = $this->recordScanEvent(
                $attendee->id,
                $turnstile->id,
                $qrToken,
                $eventType,
                $ipAddress,
                $metadata,
                $scannedAt
            );

            event(new CheckOutEvent([
                'attendee_id' => $attendee->id,
                'attendee_name' => $attendee->name,
                'turnstile_id' => $turnstile->id,
                'event_type' => $eventType,
                'checked_out_at' => $scannedAt->toISOString(),
                'metadata' => $metadata,
            ]));

            return (object) [
                'success' => true,
                'message' => 'Check-out successful',
                'result' => ScanResult::Success->value,
                'attendee_id' => $attendee->id,
                'attendee_name' => $attendee->name,
                'event_type' => $eventType,
                'checked_in_at' => $attendee->checkin_at?->toISOString(),
                'checked_out_at' => $scannedAt->toISOString(),
                'turnstile_id' => $turnstile->id,
                'scan_event_id' => $scanEvent->id,
                'http_status' => 200,
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Current attendance status of an attendee, read from the database.
     */
    public function getStatus(int $attendeeId): object
    {
        $attendee = Attendee::find($attendeeId);

        if (! $attendee) {
            return (object) [
                'attendee_id' => $attendeeId,
                'found' => false,
                'status' => null,
                'checkin_at' => null,
                'checkout_at' => null,
                'checked_in' => false,
                'checked_out' => false,
            ];
        }

        return (object) [
            'attendee_id' => $attendee->id,
            'found' => true,
            'attendee_name' => $attendee->name,
            'status' => $attendee->status,
            'checkin_at' => $attendee->checkin_at?->toISOString(),
            'checkout_at' => $attendee->checkout_at?->toISOString(),
            'checked_in' => $attendee->checkin_at !== null,
            'checked_out' => $attendee->checkout_at !== null,
        ];
    }

    /**
     * Resolve a turnstile from an id or from its name.
     */
    public function resolveTurnstile(int|string $turnstile): ?Turnstile
    {
        $value = trim((string) $turnstile);

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return Turnstile::find((int) $value);
        }

        return Turnstile::where('name', $value)->where('is_active', true)->first()
            ?? Turnstile::where('name', $value)->first();
    }

    /**
     * Write the scan event that proves the scan happened.
     */
    protected function recordScanEvent(
        int $attendeeId,
        int $turnstileId,
        string $qrToken,
        string $eventType,
        ?string $ipAddress,
        array $metadata,
        \DateTimeInterface $scannedAt
    ): ScanEvent {
        return ScanEvent::create([
            'attendee_id' => $attendeeId,
            'turnstile_id' => $turnstileId,
            'token_hash' => hash('sha256', $qrToken),
            'event_type' => $eventType,
            'result' => ScanResult::Success->value,
            'ip_address' => $ipAddress,
            'metadata' => $metadata ?: null,
            'scanned_at' => $scannedAt,
        ]);
    }

    protected function isForced(string $eventType): bool
    {
        return in_array(
            $eventType,
            [ScanEventType::ForceCheckin->value, ScanEventType::ForceCheckout->value],
            true
        );
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function failure(string $message, ScanResult $result, int $status, array $extra = []): object
    {
        return (object) array_merge([
            'success' => false,
            'message' => $message,
            'result' => $result->value,
            'http_status' => $status,
        ], $extra);
    }
}
