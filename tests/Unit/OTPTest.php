<?php

use App\Modules\Identity\Services\OTPService;
use App\Modules\Identity\Contracts\SMSAdapterInterface;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

it('can generate a 6-digit OTP code', function () {
    $otpService = new OTPService($this->createMock(SMSAdapterInterface::class));

    $reflection = new \ReflectionClass(OTPService::class);
    $method = $reflection->getMethod('generateCode');
    $method->setAccessible(true);

    $code = $method->invoke($otpService);

    expect($code)->toBeString()->toHaveLength(6)->toMatch('/^\d{6}$/');
});

it('can send OTP via SMS adapter', function () {
    $phone = '+989123456789';
    $smsMock = $this->createMock(SMSAdapterInterface::class);
    $smsMock->expects($this->once())
        ->method('send')
        ->with($this->equalTo($phone), $this->string(), $this->equalTo(300))
        ->willReturn(true);

    $otpService = new OTPService($smsMock);
    $result = $otpService->send($phone);

    expect($result)->toBeTrue();
    expect(Cache::has(OTPService::CACHE_PREFIX . $phone))->toBeTrue();
});

it('can verify correct OTP code', function () {
    $phone = '+989123456789';
    $smsMock = $this->createMock(SMSAdapterInterface::class);
    $smsMock->method('send')->willReturn(true);

    $otpService = new OTPService($smsMock);
    $otpService->send($phone);

    $code = Cache::get(OTPService::CACHE_PREFIX . $phone);

    $user = $otpService->verify($phone, $code);

    expect($user)->not->toBeNull();
    expect($user->phone)->toEqual($phone);
});