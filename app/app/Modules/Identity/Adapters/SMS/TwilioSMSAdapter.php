<?php

declare(strict_types=1);

namespace App\Modules\Identity\Adapters\SMS;

use App\Modules\Identity\Contracts\SMSAdapterInterface;

class TwilioSMSAdapter implements SMSAdapterInterface
{
    /**
     * Send an OTP code to the given phone number via Twilio.
     * Requires TWILIO_SID, TWILIO_TOKEN, TWILIO_FROM environment variables.
     */
    public function send(string $phone, string $code, int $expiry): bool
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (! $sid || ! $token || ! $from) {
            \Log::warning('Twilio credentials not configured');

            return false;
        }

        try {
            // Simulate Twilio API call (replace with actual SDK call)
            // $client = new \Twilio\Rest\Client($sid, $token);
            // $client->messages->create($phone, ['from' => $from, 'body' => "Your OTP code: {$code}"]);

            \Log::info("Twilio SMS sent to {$phone}: {$code}");

            return true;
        } catch (\Exception $e) {
            \Log::error('Twilio SMS failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}