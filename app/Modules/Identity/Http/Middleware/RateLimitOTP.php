<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitOTP
{
    protected int $maxAttempts = 5;
    protected int $decayMinutes = 1;

    public function handle(Request $request, Closure $next, string $type = 'otp')
    {
        $key = "otp_{$type}_{$request->ip()}_{$request->input('phone', 'unknown')}";

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many OTP attempts. Please try again later.',
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, $this->decayMinutes * 60);

        return $next($request);
    }
}