<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Http\Controllers\Concerns;

use App\Modules\AccessControl\Models\Turnstile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait HandlesScanRequests
{
    /**
     * Validation rules shared by the check-in and check-out endpoints.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function scanRules(): array
    {
        return [
            'qr_token' => ['required_without:token_hash', 'string', 'max:255'],
            'token_hash' => ['required_without:qr_token', 'string', 'max:255'],
            'turnstile_id' => ['required_without_all:turnstile,turnstile_name'],
            'turnstile' => ['nullable', 'string', 'max:255'],
            'turnstile_name' => ['nullable', 'string', 'max:255'],
            'event_type' => ['nullable', 'in:CHECK_IN,CHECK_OUT,FORCE_CHECK_IN,FORCE_CHECK_OUT'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Normalise the incoming event type so that "checkin" and "CHECK_IN" are
     * both understood.
     */
    protected function normalizeEventType(?string $eventType): ?string
    {
        if ($eventType === null || trim($eventType) === '') {
            return null;
        }

        $value = strtolower(str_replace(['-', ' '], '_', trim($eventType)));

        return match ($value) {
            'checkin', 'check_in' => 'CHECK_IN',
            'checkout', 'check_out' => 'CHECK_OUT',
            'force_checkin', 'force_check_in' => 'FORCE_CHECK_IN',
            'force_checkout', 'force_check_out' => 'FORCE_CHECK_OUT',
            default => strtoupper(trim($eventType)),
        };
    }

    /**
     * Resolve the turnstile from its id or from its name.
     *
     * @param  array<string, mixed>  $validated
     */
    protected function resolveTurnstileId(array $validated): ?int
    {
        $value = $validated['turnstile_id']
            ?? $validated['turnstile']
            ?? $validated['turnstile_name']
            ?? null;

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (ctype_digit((string) $value)) {
            return Turnstile::whereKey((int) $value)->value('id');
        }

        return Turnstile::where('name', (string) $value)->where('is_active', true)->value('id')
            ?? Turnstile::where('name', (string) $value)->value('id');
    }

    /**
     * Build the JSON response of a scan.
     */
    protected function scanResponse(object $result): JsonResponse
    {
        $status = (int) ($result->http_status ?? 200);

        return response()->json([
            'success' => (bool) ($result->success ?? false),
            'message' => (string) ($result->message ?? ''),
            'data' => Arr::except((array) $result, ['http_status']),
        ], $status);
    }

    /**
     * Response used when the referenced turnstile cannot be resolved.
     */
    protected function turnstileNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Turnstile not found',
            'data' => [
                'success' => false,
                'message' => 'Turnstile not found',
                'result' => 'NOT_FOUND',
            ],
        ], 404);
    }
}
