<?php

declare(strict_types=1);

namespace App\Registration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAttendeeDuplicate
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->user()->id;
        $eventId = $request->input('event_id');
        $phone = $request->input('phone');

        $exists = \App\Registration\Models\Attendee::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You are already registered for this event',
            ], 409);
        }

        $existsByPhone = \App\Registration\Models\Attendee::where('phone', $phone)
            ->where('event_id', $eventId)
            ->exists();

        if ($existsByPhone) {
            return response()->json([
                'message' => 'An attendee with this phone number is already registered for this event',
            ], 409);
        }

        return $next($request);
    }
}