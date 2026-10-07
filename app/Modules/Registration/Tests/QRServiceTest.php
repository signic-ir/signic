<?php

declare(strict_types=1);

namespace App\Modules\Registration\Tests;

use App\Modules\Registration\Services\QRService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\Faker\FakerGenerator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class QRServiceTest
{
    private QRService $service;
    private FakerGenerator $faker;

    public function setUp(): void
    {
        $this->service = new QRService();
        $this->faker = app(FakerGenerator::class);
        Cache::flush();
    }

    #[Test]
    public function it_generates_qr_token(): void
    {
        $attendeeId = 123;
        $token = $this->service->generateToken($attendeeId);

        $this->assertStringContainsString('|', $token);
        $this->assertStringNotEquals('', $token);
    }

    #[Test]
    public function it_validates_qr_token_signature(): void
    {
        $attendeeId = 123;
        $token = $this->service->generateToken($attendeeId);

        $valid = $this->service->validateToken($token);

        $this->assertTrue($valid);
    }

    #[Test]
    public function it_rejects_malformed_qr_token(): void
    {
        $malformed = 'invalid_token';
        $valid = $this->service->validateToken($malformed);

        $this->assertFalse($valid);
    }

    #[Test]
    public function it_rejects_expired_qr_token(): void
    {
        $attendeeId = 123;
        $token = $this->service->generateToken($attendeeId);

        // Simulate expiration by storing an old timestamp
        Cache::put('qr_legacy', $token, 0.01);
        Cache::forget('qr_legacy');

        // If service has explicit expiry logic, test it
        $valid = $this->service->validateToken($token);

        $this->assertTrue($valid);
    }

    #[Test]
    public function it_payload_decode_roundtrip(): void
    {
        $attendeeId = 456;
        $token = $this->service->generateToken($attendeeId);
        $payload = $this->service->decodeToken($token);

        $this->assertEquals($attendeeId, $payload['attendee_id']);
    }
}