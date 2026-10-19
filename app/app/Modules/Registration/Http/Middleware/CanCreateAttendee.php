<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CanCreateAttendee
{
    /**
     * The permission an organizer needs to register attendees.
     */
    protected string $permission = 'attendee.create';

    /**
     * Guard the attendee creation.
     *
     * A user that holds the "attendee.create" permission — directly or through
     * one of its roles — may register attendees. A user that carries roles but
     * not the permission is denied, so the role model stays meaningful.
     *
     * A user without any role has no permission set to check against; such a
     * plain authenticated user may use the public registration endpoint.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->hasPermissionTo($this->permission)) {
            return $next($request);
        }

        if ($user->roles()->count() === 0) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Permission denied. This action is unauthorized.',
        ], 403);
    }
}
