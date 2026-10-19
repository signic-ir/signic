<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Enums;

enum ExhibitorStatus: string
{
    case Pending = 'PENDING';
    case Confirmed = 'CONFIRMED';
    case Cancelled = 'CANCELLED';
    case Inactive = 'INACTIVE';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
