<?php

namespace Modules\Identity;

use App\Modules\Identity\Contracts\SMSAdapterInterface;
use App\Modules\Identity\Services\OTPService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\TestCase;

class OTPServiceTest extends TestCase
{
    private OTPService $otpService;
    private TestSMSAdapter $smsAdapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->smsAdapter = new TestSMSAdapter();
        $this->otpService = new OTPService($this->smsAdapter);
    }

    public function test_generate_a_6_digit_code(): void
    {
        $reflection = new \ReflectionClass(OTPService::class);
        $method = $reflection->getMethod('generateCode');
        $method->setAccessible(true);

        $code = $method->invoke($this->otpService);

        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function test_send_generates_code_and_sends_via_sms_adapter(): void
    {
        $phone = '+989123456789';

        $result = $this->otpService->send($phone);

        $this->assertTrue($result);
        $this->assertEquals(1, $this->smsAdapter->messagesSent);
        $this->assertEquals($phone, $this->smsAdapter->lastPhone);
        $this->assertEquals(300, $this->smsAdapter->lastExpiry);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->smsAdapter->lastCode);
        $this->assertTrue(Cache::has(OTPService::CACHE_PREFIX . $phone));
    }

    public function test_verify_correct_code(): void
    {
        $phone = '+989123456789';
        $this->otpService->send($phone);
        $code = Cache::get(OTPService::CACHE_PREFIX . $phone);

        $user = $this->otpService->verify($phone, $code);

        $this->assertNotNull($user);
        $this->assertEquals($phone, $user->phone);
    }

    public function test_verify_incorrect_code_returns_null(): void
    {
        $phone = '+989123456789';
        $this->otpService->send($phone);

        $user = $this->otpService->verify($phone, '123456');

        $this->assertNull($user);
    }

    public function test_verify_used_code_returns_null(): void
    {
        $phone = '+989123456789';
        $this->otpService->send($phone);
        $code = Cache::get(OTPService::CACHE_PREFIX . $phone);

        $this->otpService->verify($phone, $code);
        $user = $this->otpService->verify($phone, $code);

        $this->assertNull($user);
        $this->assertFalse(Cache::has(OTPService::CACHE_PREFIX . $phone));
    }
}

class TestSMSAdapter implements SMSAdapterInterface
{
    public int $messagesSent = 0;
    public ?string $lastPhone = null;
    public ?string $lastCode = null;
    public ?int $lastExpiry = null;

    public function send(string $phone, string $code, int $expiry): bool
    {
        $this->messagesSent++;
        $this->lastPhone = $phone;
        $this->lastCode = $code;
        $this->lastExpiry = $expiry;

        return true;
    }
}