<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Tests;

use App\Modules\AccessControl\Contracts\TurnstileServiceInterface;
use App\Modules\AccessControl\DTOs\ScanEventDTO;
use App\Modules\AccessControl\Services\TurnstileService;
use App\Modules\Registration\Contracts\QRServiceInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TurnstileServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function it_rejects_an_invalid_qr_token(): void
    {
        $qrService = Mockery::mock(QRServiceInterface::class);
        $qrService->shouldReceive('validate')->andReturnNull();
        $this->app->instance(QRServiceInterface::class, $qrService);

        /** @var TurnstileService $service */
        $service = $this->app->make(TurnstileServiceInterface::class);

        $result = $service->checkIn('not-a-valid-token', 1);

        $this->assertFalse($result->success);
        $this->assertSame('Invalid QR token', $result->message);
        $this->assertSame('INVALID', $result->result);
        $this->assertSame(422, $result->http_status);
    }

    #[Test]
    public function the_scan_dto_reads_the_token_and_defaults_the_event_type(): void
    {
        $dto = ScanEventDTO::fromRequest([
            'qr_token' => '12|abc|signature',
            'turnstile_id' => 7,
        ], '127.0.0.1');

        $this->assertSame('12|abc|signature', $dto->qrToken);
        $this->assertSame(7, $dto->turnstileId);
        $this->assertSame('CHECK_IN', $dto->eventType);
        $this->assertSame('127.0.0.1', $dto->ip);
        $this->assertSame([], $dto->metadata);
        $this->assertSame(hash('sha256', '12|abc|signature'), $dto->tokenHash());
    }

    #[Test]
    public function the_scan_dto_accepts_the_token_hash_alias_and_a_turnstile_name(): void
    {
        $dto = ScanEventDTO::fromRequest([
            'token_hash' => '12|abc|signature',
            'turnstile' => 'Main Gate',
            'event_type' => 'CHECK_OUT',
        ], '10.0.0.1');

        $this->assertSame('12|abc|signature', $dto->qrToken);
        $this->assertSame('Main Gate', $dto->turnstileId);
        $this->assertSame('CHECK_OUT', $dto->eventType);
    }
}
