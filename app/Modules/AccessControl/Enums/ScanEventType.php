<?php

declare(strict_types=1);

namespace App.Modules\AccessControl\Enums;

enum ScanEventType: string
{
    case Checkin = 'checkin';
    case Checkout = 'checkout';
    case ForceCheckin = 'force_checkin';
    case ForceCheckout = 'force_checkout';
}