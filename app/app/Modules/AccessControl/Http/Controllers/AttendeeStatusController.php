<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Http\Controllers;

use App\Modules\AccessControl\Contracts\TurnstileServiceInterface;
use Illuminate\Http\JsonResponse;

class AttendeeStatusController extends Controller
{
    public function __construct(
        protected TurnstileServiceInterface $turnstile
    ) {}

    /**
     * Current attendance status of an attendee.
     */
    public function show(int $id): JsonResponse
    {
        $status = $this->turnstile->getStatus($id);

        if (! $status->found) {
            return response()->json([
                'success' => false,
                'message' => 'Attendee not found',
                'data' => [
                    'attendee_id' => $id,
                    'status' => null,
                    'checkin_at' => null,
                    'checkout_at' => null,
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'attendee_id' => $status->attendee_id,
            'attendee_name' => $status->attendee_name,
            'status' => $status->status,
            'checkin_at' => $status->checkin_at,
            'checkout_at' => $status->checkout_at,
            'checked_in' => $status->checked_in,
            'checked_out' => $status->checked_out,
        ]);
    }
}
