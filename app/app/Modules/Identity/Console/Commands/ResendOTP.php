<?php

declare(strict_types=1);

namespace App\Modules\Identity\Console\Commands;

use App\Modules\Identity\Contracts\OTPServiceInterface;
use Illuminate\Console\Command;

class ResendOTP extends Command
{
    protected $signature = 'otp:resend {phone} {event?}';
    protected $description = 'Resend an OTP code to a phone number';

    public function __construct(private readonly OTPServiceInterface $otpService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $phone = $this->argument('phone');
        $eventId = $this->argument('event');

        if ($eventId) {
            $this->otpService->requestOTP($phone, (int) $eventId);
        } else {
            $this->otpService->requestOTP($phone);
        }

        $this->info('OTP resent successfully');

        return self::SUCCESS;
    }
}