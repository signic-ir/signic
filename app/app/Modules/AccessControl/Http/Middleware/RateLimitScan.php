<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitScan
{
    protected int $maxAttempts = 30;
    protected int $decayMinutes = 1;

    public function handle(Request $request, Closure $next, string $key = 'scan')
    {
        $keyForRateLimit = "scans_{$key}_{$request->ip()}";

        if (RateLimiter::tooManyAttempts($keyForRateLimit, $this->maxAttempts)) {
            return response()->json([
                'message' => 'Too many scan requests. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($keyForRateLimit, $this->decayMinutes * 60);

        return $next($request);
    }
}