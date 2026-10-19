<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Enums;

enum InterestLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}