<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Tests;

use App\Modules\AccessControl\Services\TurnstileService;
use App\Modules\AccessControl\Models\Turnstile;
use App\Modules\Registration\Models\Attendee;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Testing\Faker\FakerGenerator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class TurnstileServiceTest
{
    private TurnstileService $service;
    private FakerGenerator $faker;

    public function setUp(): void
    {
        $this->service = new TurnstileService();
        $this->faker = app(FakerGenerator::class);
        Cache::flush();
        Redis::flushAll();
    }

    public function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        Redis::flushAll();
    }

    #[Test]
    public function it_checks_in_attendee_with_valid_qr(): void
    {
        $attendee = Attendee::factory()->make(['id' => 1]);
        $turnstile = Turnstile::factory()->make(['id' => 1]);
        $qrService = Mockery::mock('App\Modules\Registration\Services\QRService');
        $qrService->shouldReceive('validateToken')
            ->with(Mockery::any())
            ->andReturn(['attendee_id' => $attendee->id]);

        $service = new TurnstileService($qrService);
        $result = $service->checkIn('valid_qr_token', $attendee, $turnstile);

        $this->assertEquals('success', $result['result']);
        $this->assertEquals($attendee->id, $result['attendee_id']);
        $this->assertEquals($turnstile->id, $result['turnstile_id']);
    }

    #[Test]
    public function it_rejects_invalid_qr_token(): void
    {
        $attendee = Attendee::factory()->make();
        $turnstile = Turnstile::factory()->make();
        $qrService = Mockery::mock('App\Modules\Registration\Services\QRService');
        $qrService->shouldReceive('validateToken')
            ->with('invalid_token')
            ->andReturn(null);

        $service = new TurnstileService($qrService);
        $result = $service->checkIn('invalid_token', $attendee, $turnstile);

        $this->assertEquals('invalid', $result['result']);
    }

    #[Test]
    public function it_checks_out_attendee(): void
    {
        $attendee = Attendee::factory()->make(['id' => 1]);
        $turnstile = Turnstile::factory()->make(['id' => 1]);
        $qrService = Mockery::mock('App\Modules\Registration\Services\QRService');
        $qrService->shouldReceive('validateToken')
            ->with(Mockery::any())
            ->andReturn(['attendee_id' => $attendee->id]);

        $service = new TurnstileService($qrService);
        $result = $service->checkOut('valid_qr_token', $attendee, $turnstile);

        $this->assertEquals('success', $result['result']);
    }
}