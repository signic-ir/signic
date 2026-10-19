<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Middleware;

use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\Event;
use Closure;
use Illuminate\Http\Request;

class CheckAttendeeDuplicate
{
    /**
     * Reject an attendee that is already registered for the same event.
     *
     * An organizer may register as many attendees for an event as they want,
     * so the duplicate is detected by the contact details of the attendee
     * (email first, phone second) and never by the organizer behind it.
     *
     * Re-registering the very same attendee (same email *and* same phone) is
     * not a duplicate but a repeated registration: it is passed on to the
     * controller, which answers with the attendee that already exists.
     */
    public function handle(Request $request, Closure $next)
    {
        $eventId = $this->resolveEventId($request);

        if (! $eventId) {
            return $next($request);
        }

        $email = $request->input('email');
        $phone = $request->input('phone');

        $email = is_string($email) && $email !== '' ? $email : null;
        $phone = is_string($phone) && $phone !== '' ? $phone : null;

        if ($email) {
            $sameEmail = Attendee::where('event_id', $eventId)->where('email', $email)->get();

            foreach ($sameEmail as $attendee) {
                if ($phone !== null && $attendee->phone === $phone) {
                    // The same attendee, registered again.
                    return $next($request);
                }
            }

            if ($sameEmail->isNotEmpty()) {
                return $this->duplicate(
                    'email',
                    'The email has already been registered for this event (duplicate email).'
                );
            }
        }

        if ($phone) {
            $samePhone = Attendee::where('event_id', $eventId)->where('phone', $phone)->exists();

            if ($samePhone) {
                return $this->duplicate(
                    'phone',
                    'The phone number has already been registered for this event (duplicate phone number).'
                );
            }
        }

        return $next($request);
    }

    /**
     * The event the request refers to, either by id or by name.
     */
    protected function resolveEventId(Request $request): ?int
    {
        if ($request->filled('event_id')) {
            return (int) $request->input('event_id');
        }

        if ($request->filled('event_name')) {
            $event = Event::where('name', (string) $request->input('event_name'))->first();

            return $event?->id;
        }

        return null;
    }

    /**
     * The validation error of a duplicate attendee.
     */
    protected function duplicate(string $field, string $message): mixed
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => [
                $field => [$message],
            ],
        ], 422);
    }
}
