<?php

declare(strict_types=1);

namespace App.Modules\Registration\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}