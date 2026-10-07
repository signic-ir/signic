<?php

declare(strict_types=1);

namespace App.Modules\Identity\Contracts;

interface SMSAdapterInterface
{
    /**
     * Send an OTP code to the given phone number.
     */
    public function send(string $phone, string $code, int $expiry): bool;
}