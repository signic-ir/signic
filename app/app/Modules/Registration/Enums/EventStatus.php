<?php

declare(strict_types=1);

namespace App\Modules\Registration\Enums;

enum EventStatus: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Paused = 'PAUSED';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}
