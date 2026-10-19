<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Enums;

enum LeadStatus: string
{
    case New = 'NEW';
    case Qualified = 'QUALIFIED';
    case Contacted = 'CONTACTED';
    case Converted = 'CONVERTED';
    case Lost = 'LOST';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
