<?php

declare(strict_types=1);

namespace App\Modules\Identity\Adapters\SMS;

use App.Modules\Identity\Contracts\SMSAdapterInterface;

// TODO: Replace with a real SMS provider adapter (e.g., Twilio, Kavenegar) when SMS service is available
class NullSMSAdapter implements SMSAdapterInterface
{
    /**
     * Send an OTP code to the given phone number (null implementation for development).
     */
    public function send(string $phone, string $code, int $expiry): bool
    {
        \Log::info("SMS OTP for {$phone}: {$code} (valid for {$expiry} seconds)");

        return true;
    }
}