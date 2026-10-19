<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Http\Controllers;

use App\Modules\AccessControl\Contracts\TurnstileServiceInterface;
use App\Modules\AccessControl\DTOs\ScanEventDTO;
use App\Modules\AccessControl\Http\Controllers\Concerns\HandlesScanRequests;
use App\Modules\AccessControl\Http\Middleware\RateLimitScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    use HandlesScanRequests;

    public function __construct(
        protected TurnstileServiceInterface $turnstile
    ) {
        $this->middleware(RateLimitScan::class);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'event_type' => $this->normalizeEventType($request->input('event_type')),
        ]);

        $validated = $request->validate($this->scanRules());

        $turnstileId = $this->resolveTurnstileId($validated);

        if (! $turnstileId) {
            return $this->turnstileNotFoundResponse();
        }

        $dto = ScanEventDTO::fromRequest($validated, $request->ip());

        $result = $this->turnstile->checkIn(
            $dto->qrToken,
            $turnstileId,
            $dto->metadata,
            $dto->ip,
            $dto->eventType === 'FORCE_CHECK_IN' ? 'FORCE_CHECK_IN' : 'CHECK_IN'
        );

        return $this->scanResponse($result);
    }
}
