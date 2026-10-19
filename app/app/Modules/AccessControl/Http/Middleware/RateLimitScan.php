<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitScan
{
    /**
     * Scans allowed per minute and per user (falling back to the IP address).
     *
     * The budget is generous on purpose: a burst of concurrent scans from
     * several staff members is legitimate traffic and must not be throttled.
     */
    protected int $maxAttempts = 120;

    protected int $decayMinutes = 1;

    public function handle(Request $request, Closure $next, string $key = 'scan')
    {
        $identifier = $request->user()?->id
            ? 'user_'.$request->user()->id
            : 'ip_'.$request->ip();

        $keyForRateLimit = "scans_{$key}_{$identifier}";

        if (RateLimiter::tooManyAttempts($keyForRateLimit, $this->maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many scan requests. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($keyForRateLimit, $this->decayMinutes * 60);

        return $next($request);
    }
}
