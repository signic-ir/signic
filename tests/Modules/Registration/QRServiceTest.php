<?php

namespace Modules\Registration;

use App\Modules\Registration\Contracts\QRServiceInterface;
use App\Modules\Registration\Services\QRService;
use PHPUnit\Framework\TestCase;

class QRServiceTest extends TestCase
{
    private QRService $qrService;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app.key', base64_encode('test-application-key'));
        $this->qrService = new QRService();
    }

    public function test_generate_valid_token(): void
    {
        $attendeeId = 123;
        $token = $this->qrService->generate($attendeeId);

        $this->assertStringContainsString('|', $token);
        $this->assertNotEmpty($token);
    }

    public function test_validate_correct_token(): void
    {
        $attendeeId = 123;
        $token = $this->qrService->generate($attendeeId);

        $result = $this->qrService->validate($token);

        $this->assertEquals($attendeeId, $result);
    }

    public function test_validate_invalid_token_missing_pipe(): void
    {
        $result = $this->qrService->validate('invalid-token-without-pipe');

        $this->assertNull($result);
    }

    public function test_validate_invalid_token_bad_signature(): void
    {
        $result = $this->qrService->validate('invalid|bad-signature');

        $this->assertNull($result);
    }

    public function test_validate_invalid_token_malformed_base64(): void
    {
        $result = $this->qrService->validate('!!!invalid!!!|signature');

        $this->assertNull($result);
    }

    public function test_validate_returns_attendee_id(): void
    {
        $attendeeId = 456;
        $token = $this->qrService->generate($attendeeId);

        $result = $this->qrService->validate($token);

        $this->assertIsInt($result);
        $this->assertEquals($attendeeId, $result);
    }
}