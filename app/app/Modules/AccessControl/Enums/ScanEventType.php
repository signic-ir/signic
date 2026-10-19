<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Enums;

enum ScanEventType: string
{
    case Checkin = 'CHECK_IN';
    case Checkout = 'CHECK_OUT';
    case ForceCheckin = 'FORCE_CHECK_IN';
    case ForceCheckout = 'FORCE_CHECK_OUT';
}