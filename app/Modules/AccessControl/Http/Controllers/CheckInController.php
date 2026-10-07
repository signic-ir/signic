<?php

declare(strict_types=1);

namespace App\AccessControl\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\AccessControl\DTOs\ScanEventDTO;
use App\AccessControl\Contracts\TurnstileServiceInterface;
use App\AccessControl\Http\Middleware\RateLimitScan;

class CheckInController extends Controller
{
    public function __construct(
        protected TurnstileServiceInterface $turnstile
    ) {
        $this->middleware(RateLimitScan::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token_hash' => ['required', 'string'],
            'turnstile_id' => ['required', 'integer', 'exists:turnstiles,id'],
        ]);

        $dto = ScanEventDTO::fromRequest($validated, $request->ip());

        $result = $this->turnstile->checkIn($dto->tokenHash, $dto->turnstileId, $dto->metadata);

        return response()->json([
            'success' => $result->success,
            'message' => $result->message,
            'data' => $result,
        ]);
    }
}