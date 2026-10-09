<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Contracts\OTPServiceInterface;
use App\Modules\Identity\Contracts\SMSAdapterInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OTPService implements OTPServiceInterface
{
    protected SMSAdapterInterface $sms;
    
    const OTP_LENGTH = 6;
    const OTP_EXPIRY = 300; // 5 minutes
    const CACHE_PREFIX = 'signic_otp_';

    public function __construct(SMSAdapterInterface $smsAdapter)
    {
        $this->sms = $smsAdapter;
    }

    public function send(string $phone): bool
    {
        $code = $this->generateCode();
        $cacheKey = self::CACHE_PREFIX . $phone;
        
        // Store OTP in cache with expiry
        Cache::put($cacheKey, $code, self::OTP_EXPIRY);
        
        // Send via SMS adapter
        $this->sms->send($phone, $code, self::OTP_EXPIRY);
        
        return true;
    }

    public function verify(string $phone, string $code): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        $cacheKey = self::CACHE_PREFIX . $phone;
        $storedCode = Cache::get($cacheKey);
        
        if (! $storedCode || ! hash_equals($storedCode, $code)) {
            return null;
        }
        
        // OTP is valid, remove it from cache
        Cache::forget($cacheKey);
        
        // Find or create user by phone number
        $user = \App\Modules\Identity\Models\User::firstOrCreate(
            ['phone' => $phone],
            [
                'name'               => null,
                'phone_verified_at'  => now(),
            ]
        );

        // Mark phone as verified if not already
        if (! $user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }
        
        return $user;
    }

    protected function generateCode(): string
    {
        // Generate a random numeric code
        return str_pad(random_int(0, pow(10, self::OTP_LENGTH) - 1), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }
}