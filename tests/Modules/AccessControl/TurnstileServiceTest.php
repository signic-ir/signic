<?php

namespace Modules\AccessControl;

use App\Modules\Registration\Contracts\QRServiceInterface;
use App\Modules\Registration\Services\QRService;
use App\Modules\AccessControl\Services\TurnstileService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\TestCase;

class TurnstileServiceTest extends TestCase
{
    private TurnstileService $turnstileService;
    private TestQRService $qrService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qrService = new TestQRService();
        $this->turnstileService = new TurnstileService();
    }

    public function test_check_in_with_invalid_qr_token(): void
    {
        $this->qrService->shouldReturn = null;
        $result = $this->turnstileService->checkIn('invalid-token', 'ts-001');

        $this->assertFalse($result->success);
        $this->assertEquals('invalid', $result->result);
        $this->assertStringContainsString('Invalid', $result->message);
    }

    public function test_check_in_with_valid_qr_token(): void
    {
        $this->qrService->shouldReturn = 123;
        $result = $this->turnstileService->checkIn('valid-token', 'ts-001');

        $this->assertTrue($result->success);
        $this->assertEquals('success', $result->result);
        $this->assertStringContainsString('Check-in successful', $result->message);
    }

    public function test_check_out_with_invalid_qr_token(): void
    {
        $this->qrService->shouldReturn = null;
        $result = $this->turnstileService->checkOut('invalid-token', 'ts-001');

        $this->assertFalse($result->success);
        $this->assertEquals('invalid', $result->result);
    }

    public function test_duplicate_check_in_returns_duplicate(): void
    {
        $this->qrService->shouldReturn = 456;
        $cacheKey = 'signic_checkin_' . 'valid-token';
        Cache::put($cacheKey, now()->toISOString(), now()->addDays(30));

        $result = $this->turnstileService->checkIn('valid-token', 'ts-001');

        $this->assertTrue($result->success);
        $this->assertEquals('duplicate', $result->result);
    }

    public function test_get_status(): void
    {
        $this->qrService->shouldReturn = 789;
        Cache::put('signic_checkin_valid-token', now()->toISOString(), now()->addDays(30));

        $status = $this->turnstileService->getStatus(789);

        $this->assertTrue($status->checkedIn);
        $this->assertFalse($status->checkedOut);
    }
}

class TestQRService implements QRServiceInterface
{
    public ?int $shouldReturn = 123;

    public function generate(int $attendeeId): string
    {
        return 'test-token-' . $attendeeId;
    }

    public function validate(string $token): ?int
    {
        return $this->shouldReturn;
    }
}