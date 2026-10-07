<?php

declare(strict_types=1);

namespace App.Modules\AccessControl\Enums;

enum ScanResult: string
{
    case Success = 'success';
    case Duplicate = 'duplicate';
    case NotFound = 'not_found';
    case Expired = 'expired';
    case Invalid = 'invalid';
}