<?php

declare(strict_types=1);

namespace App\Modules\Registration\Tests;

use App\Modules\Identity\Models\User;
use App\Modules\Registration\Contracts\QRServiceInterface;
use App\Modules\Registration\Enums\AttendeeStatus;
use App\Modules\Registration\Enums\TicketType;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\Event;
use App\Modules\Registration\Models\PrintJob;
use App\Modules\Registration\Models\QRBadge;
use App\Modules\Registration\Services\AttendeeService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;

class AttendeeServiceTest extends BaseTestCase
{
    use DatabaseTransactions;

    private AttendeeService $service;

    /**
     * Boot the application for the test.
     *
     * The tests run against an isolated in-memory database so that they
     * never touch the database of the platform.
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The schema of the testing database is built once per run. Only the
        // migrations of the Identity and Registration modules are applied.
        Artisan::call('migrate', [
            '--force' => true,
            '--path' => [
                'app/Modules/Identity/Database/Migrations',
                'app/Modules/Registration/Database/Migrations',
            ],
        ]);

        $this->service = $this->app->make(AttendeeService::class);
    }

    #[Test]
    public function it_registers_an_attendee_for_an_event(): void
    {
        [$event, $registrar] = $this->makeEventAndRegistrar();

        $result = $this->service->register([
            'event_id' => $event->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'name' => 'John Doe',
            'phone' => '+989123456789',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'ticket_type' => TicketType::Vip->value,
        ], $registrar);

        $attendee = $result['attendee'];

        $this->assertInstanceOf(Attendee::class, $attendee);
        $this->assertTrue($attendee->exists);
        $this->assertSame($event->id, $attendee->event_id);
        $this->assertSame($registrar->id, $attendee->user_id);
        $this->assertSame($registrar->id, $attendee->created_by_user_id);
        $this->assertSame('John Doe', $attendee->name);
        $this->assertSame('John', $attendee->first_name);
        $this->assertSame('Doe', $attendee->last_name);
        $this->assertSame('Acme Corp', $attendee->company);
        $this->assertSame(TicketType::Vip->value, $attendee->ticket_type);
        $this->assertSame(AttendeeStatus::Confirmed->value, $attendee->status);

        $this->assertDatabaseHas('attendees', [
            'id' => $attendee->id,
            'event_id' => $event->id,
            'user_id' => $registrar->id,
            'status' => AttendeeStatus::Confirmed->value,
        ]);
    }

    #[Test]
    public function it_issues_a_qr_token_that_validates_back_to_the_attendee(): void
    {
        [$event, $registrar] = $this->makeEventAndRegistrar();

        $result = $this->service->register($this->registrationData($event->id), $registrar);

        $token = $result['token'];
        $attendee = $result['attendee'];

        $this->assertNotSame('', $token);
        $this->assertSame(3, count(explode('|', $token)));

        /** @var QRServiceInterface $qrService */
        $qrService = $this->app->make(QRServiceInterface::class);
        $this->assertSame($attendee->id, $qrService->validate($token));

        // Only the hash of the token is kept on the attendee, never the token
        // itself.
        $this->assertSame(hash('sha256', $token), $attendee->qr_token_hash);
        $this->assertNotSame($token, $attendee->qr_token_hash);
        $this->assertNotNull($attendee->qr_token_generated_at);
        $this->assertNotNull($attendee->qr_token_expires_at);
        $this->assertTrue($attendee->qr_token_expires_at->isAfter($attendee->qr_token_generated_at));
    }

    #[Test]
    public function it_creates_an_active_qr_badge_for_the_attendee(): void
    {
        [$event, $registrar] = $this->makeEventAndRegistrar();

        $result = $this->service->register($this->registrationData($event->id), $registrar);

        $attendee = $result['attendee'];
        $badge = $attendee->qrBadge;

        $this->assertInstanceOf(QRBadge::class, $badge);
        $this->assertTrue($badge->is_active);
        $this->assertSame($attendee->qr_token_hash, $badge->token_hash);
        $this->assertNotNull($badge->generated_at);
        $this->assertNotNull($badge->expires_at);

        $this->assertDatabaseHas('qr_badges', [
            'attendee_id' => $attendee->id,
            'token_hash' => $attendee->qr_token_hash,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_queues_a_pending_print_job_for_the_badge(): void
    {
        [$event, $registrar] = $this->makeEventAndRegistrar();

        $result = $this->service->register($this->registrationData($event->id), $registrar);

        $attendee = $result['attendee'];
        $printJob = $result['print_job'];

        $this->assertInstanceOf(PrintJob::class, $printJob);
        $this->assertSame('pending', $printJob->status);
        $this->assertSame($attendee->id, $printJob->attendee_id);
        $this->assertSame($registrar->id, $printJob->printed_by_user_id);
        $this->assertSame($result['token'], $printJob->qr_code);

        $this->assertSame('John Doe', $printJob->badge_data['attendee_name']);
        $this->assertSame('Acme Corp', $printJob->badge_data['company']);
        $this->assertSame(TicketType::General->value, $printJob->badge_data['ticket_type']);
        $this->assertSame('Test Event', $printJob->badge_data['event']);

        $this->assertDatabaseHas('print_jobs', [
            'id' => $printJob->id,
            'attendee_id' => $attendee->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function it_rotates_the_badge_when_a_fresh_token_is_issued(): void
    {
        [$event, $registrar] = $this->makeEventAndRegistrar();

        $result = $this->service->register($this->registrationData($event->id), $registrar);

        $attendee = $result['attendee'];
        $firstHash = $attendee->qr_token_hash;

        $secondToken = $this->service->issueToken($attendee);
        $attendee->refresh();

        $this->assertNotSame($firstHash, $attendee->qr_token_hash);
        $this->assertSame(hash('sha256', $secondToken), $attendee->qr_token_hash);

        // The badge is rotated in place, the attendee never ends up with more
        // than one badge.
        $this->assertSame(1, QRBadge::where('attendee_id', $attendee->id)->count());
        $this->assertSame($attendee->qr_token_hash, $attendee->qrBadge->token_hash);
    }

    /**
     * @return array{0: Event, 1: User}
     */
    private function makeEventAndRegistrar(): array
    {
        $event = Event::create([
            'name' => 'Test Event',
            'slug' => 'test-event-'.uniqid(),
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
            'status' => 'ACTIVE',
        ]);

        $registrar = User::create([
            'phone' => '+989120000000',
            'name' => 'Test Registrar',
        ]);

        return [$event, $registrar];
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationData(int $eventId): array
    {
        return [
            'event_id' => $eventId,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'name' => 'John Doe',
            'phone' => '+989123456789',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'ticket_type' => TicketType::General->value,
        ];
    }
}
