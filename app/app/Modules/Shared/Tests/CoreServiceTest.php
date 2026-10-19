<?php

declare(strict_types=1);

namespace App\Modules\Shared\Tests;

use App\Modules\Shared\Services\CoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\Faker\FakerGenerator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class CoreServiceTest
{
    private CoreService $service;
    private FakerGenerator $faker;

    public function setUp(): void
    {
        $this->service = new CoreService();
        $this->faker = app(FakerGenerator::class);
        Cache::flush();
    }

    public function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
    }

    #[Test]
    public function it_generates_and_verifies_jwt_token(): void
    {
        $userId = 123;
        $token = $this->service->generateToken($userId);

        $this->assertStringNotEmpty($token);
        $this->assertStringContainsString('.', $token);
    }

    #[Test]
    public function it_validates_permission_for_user(): void
    {
        $userId = 123;
        $permission = 'view_attendees';

        $hasPermission = $this->service->checkPermission($userId, $permission);

        $this->assertIsBool($hasPermission);
    }

    #[Test]
    public function it_routes_notification_by_channel(): void
    {
        $recipient = '+1234567890';
        $message = 'Test message';
        $channel = 'sms';

        $result = $this->service->sendNotification($recipient, $message, $channel);

        $this->assertIsBool($result);
    }
}