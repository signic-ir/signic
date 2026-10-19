<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Guards the scan endpoints.
 *
 * The application has no "login" route, so the default authentication
 * exception handler would end up in a 500 while trying to redirect. This
 * middleware answers with a plain 401 JSON payload instead.
 */
class EnsureSanctumAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('sanctum')->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }
}
