<?php

declare(strict_types=1);

namespace App.Modules\Exhibition\Enums;

enum ExhibitorStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Inactive = 'inactive';
}