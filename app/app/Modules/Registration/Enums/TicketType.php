<?php

declare(strict_types=1);

namespace App\Modules\Registration\Enums;

enum TicketType: string
{
    case General = 'GENERAL';
    case Vip = 'VIP';
    case Speaker = 'SPEAKER';
    case Press = 'PRESS';
    case Organizer = 'ORGANIZER';
    case Volunteer = 'VOLUNTEER';
}