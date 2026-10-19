<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Enums;

enum ScanResult: string
{
    case Success = 'SUCCESS';
    case Duplicate = 'DUPLICATE';
    case NotFound = 'NOT_FOUND';
    case Expired = 'EXPIRED';
    case Invalid = 'INVALID';
}