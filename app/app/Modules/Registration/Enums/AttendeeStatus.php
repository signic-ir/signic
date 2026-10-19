<?php

declare(strict_types=1);

namespace App\Modules\Registration\Enums;

enum AttendeeStatus: string
{
    case Registered = 'registered';
    case Confirmed = 'confirmed';
    case Attended = 'attended';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';
}