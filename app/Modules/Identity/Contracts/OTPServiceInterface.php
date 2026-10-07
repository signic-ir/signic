<?php

declare(strict_types=1);

namespace App.Modules\Identity\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface OTPServiceInterface
{
    /**
     * Send an OTP to the given phone number.
     */
    public function send(string $phone): bool;

    /**
     * Verify the OTP for the given phone number.
     */
    public function verify(string $phone, string $code): ?Authenticatable;
}