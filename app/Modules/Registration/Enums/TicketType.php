<?php

declare(strict_types=1);

namespace App.Modules\Registration\Enums;

enum TicketType: string
{
    case General = 'general';
    case Vip = 'vip';
    case Speaker = 'speaker';
    case Press = 'press';
    case Organizer = 'organizer';
    case Volunteer = 'volunteer';
}