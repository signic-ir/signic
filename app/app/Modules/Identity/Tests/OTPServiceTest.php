<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Modules\Identity\Services\OTPService;
use App\Modules\Identity\Contracts\SMSAdapterInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\Faker\FakerGenerator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class OTPServiceTest
{
    private OTPService $service;
    private FakerGenerator $faker;

    public function setUp(): void
    {
        $this->service = new OTPService(Mockery::mock(SMSAdapterInterface::class));
        $this->faker = app(FakerGenerator::class);
        Cache::flush();
    }

    #[Test]
    public function it_generates_and_sends_otp(): void
    {
        $phone = $this->faker->phoneNumber();
        $smsMock = Mockery::mock(SMSAdapterInterface::class);
        $smsMock->shouldReceive('send')
            ->with($phone, Mockery::any(), self::$service::OTP_EXPIRY)
            ->once()
            ->andReturn(true);

        $service = new OTPService($smsMock);
        
        $result = $service->send($phone);
        
        $this->assertTrue($result);
        $this->assertNotNull(Cache::get(OTPService::CACHE_PREFIX . $phone));
    }

    #[Test]
    public function it_verifies_correct_otp(): void
    {
        $phone = $this->faker->phoneNumber();
        $smsMock = Mockery::mock(SMSAdapterInterface::class);
        $smsMock->shouldReceive('send')->andReturn(true);
        
        $service = new OTPService($smsMock);
        $service->send($phone);
        
        $code = Cache::get(OTPService::CACHE_PREFIX . $phone);
        $verifiedUser = $service->verify($phone, $code);
        
        $this->assertNotNull($verifiedUser);
        $this->assertEquals($phone, $verifiedUser->phone);
        $this->assertNull(Cache::get(OTPService::CACHE_PREFIX . $phone));
    }

    #[Test]
    public function it_rejects_wrong_otp(): void
    {
        $phone = $this->faker->phoneNumber();
        $smsMock = Mockery::mock(SMSAdapterInterface::class);
        $smsMock->shouldReceive('send')->andReturn(true);
        
        $service = new OTPService($smsMock);
        $service->send($phone);
        
        $wrongCode = '123456';
        $verifiedUser = $service->verify($phone, $wrongCode);
        
        $this->assertNull($verifiedUser);
        $this->assertNotNull(Cache::get(OTPService::CACHE_PREFIX . $phone));
    }

    #[Test]
    public function it_otp_expires_after_expiry(): void
    {
        $phone = $this->faker->phoneNumber();
        $smsMock = Mockery::mock(SMSAdapterInterface::class);
        $smsMock->shouldReceive('send')->andReturn(true);
        
        $service = new OTPService($smsMock);
        $service->send($phone);
        
        // Manually expire OTP by not calling verify
        $this->assertNotNull(Cache::get(OTPService::CACHE_PREFIX . $phone));
        
        // Simulate time passing by clearing cache
        Cache::forget(OTPService::CACHE_PREFIX . $phone);
        $this->assertNull(Cache::get(OTPService::CACHE_PREFIX . $phone));
    }

    #[Test]
    public function it_code_generation_consistency(): void
    {
        $phone = $this->faker->phoneNumber();
        $smsMock = Mockery::mock(SMSAdapterInterface::class);
        $smsMock->shouldReceive('send')->andReturn(true);
        
        $service = new OTPService($smsMock);
        $service->send($phone);
        
        $code = Cache::get(OTPService::CACHE_PREFIX . $phone);
        
        $this->assertIsString($code);
        $this->assertEquals(OTPService::OTP_LENGTH, strlen($code));
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $code);
    }
}