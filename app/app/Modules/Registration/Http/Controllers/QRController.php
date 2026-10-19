<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Controllers;

use App\Modules\Registration\Services\QRValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QRController extends Controller
{
    public function __construct(
        protected QRValidationService $validationService
    ) {}

    /**
     * Validate a QR token.
     *
     * A valid token answers with 200 and the attendee it belongs to, an
     * expired token with 422 and an "expired" message, a forged token with
     * 422 and an "invalid" message. Only a valid answer carries an attendee
     * id.
     */
    public function validate(Request $request): JsonResponse
    {
        $token = $request->input('token', $request->input('qr_token'));

        if (! is_string($token) || trim($token) === '') {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'A QR token is required.',
                'errors' => [
                    'token' => ['A QR token is required.'],
                ],
            ], 422);
        }

        $result = $this->validationService->validate(trim($token));

        if (! $result['valid']) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'expired' => $result['expired'],
                'message' => $result['message'],
                'errors' => [
                    'token' => [$result['message']],
                ],
                'data' => [
                    'valid' => false,
                    'expired' => $result['expired'],
                    'message' => $result['message'],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'expired' => false,
            'validated' => true,
            'attendee_id' => $result['attendee_id'],
            'message' => $result['message'],
            'data' => [
                'valid' => true,
                'expired' => false,
                'validated' => true,
                'attendee_id' => $result['attendee_id'],
                'message' => $result['message'],
            ],
        ]);
    }
}
