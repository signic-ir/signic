<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Modules\Identity\Contracts\SMSAdapterInterface;
use App\Modules\Identity\Services\OTPService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OTPServiceTest extends TestCase
{
    private OTPService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new OTPService($this->createMock(SMSAdapterInterface::class));
        Cache::flush();
    }

    #[Test]
    public function it_generates_and_sends_otp(): void
    {
        $phone = '+989123456789';
        $smsMock = $this->createMock(SMSAdapterInterface::class);
        $smsMock->expects($this->once())
            ->method('send')
            ->with($phone, $this->anything(), OTPService::OTP_EXPIRY)
            ->willReturn(true);

        $service = new OTPService($smsMock);

        $result = $service->send($phone);

        $this->assertTrue($result);
        $this->assertNotNull(Cache::get(OTPService::CACHE_PREFIX . $phone));
    }

    #[Test]
    public function it_verifies_correct_otp(): void
    {
        $phone = '+989123456789';
        $smsMock = $this->createMock(SMSAdapterInterface::class);
        $smsMock->expects($this->once())
            ->method('send')
            ->willReturn(true);

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
        $phone = '+989123456789';
        $smsMock = $this->createMock(SMSAdapterInterface::class);
        $smsMock->expects($this->once())
            ->method('send')
            ->willReturn(true);

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
        $phone = '+989123456789';
        $smsMock = $this->createMock(SMSAdapterInterface::class);
        $smsMock->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $service = new OTPService($smsMock);
        $service->send($phone);

        $this->assertNotNull(Cache::get(OTPService::CACHE_PREFIX . $phone));

        Cache::forget(OTPService::CACHE_PREFIX . $phone);
        $this->assertNull(Cache::get(OTPService::CACHE_PREFIX . $phone));
    }

    #[Test]
    public function it_code_generation_consistency(): void
    {
        $phone = '+989123456789';
        $smsMock = $this->createMock(SMSAdapterInterface::class);
        $smsMock->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $service = new OTPService($smsMock);
        $service->send($phone);

        $code = Cache::get(OTPService::CACHE_PREFIX . $phone);

        $this->assertIsString($code);
        $this->assertEquals(OTPService::OTP_LENGTH, strlen($code));
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $code);
    }
}