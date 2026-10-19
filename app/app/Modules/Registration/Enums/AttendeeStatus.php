<?php

declare(strict_types=1);

namespace App\Modules\Registration\Enums;

enum AttendeeStatus: string
{
    case Registered = 'REGISTERED';
    case Confirmed = 'CONFIRMED';
    case Attended = 'ATTENDED';
    case NoShow = 'NO_SHOW';
    case Cancelled = 'CANCELLED';
    case CheckedIn = 'CHECKED_IN';
    case CheckedOut = 'CHECKED_OUT';
}
