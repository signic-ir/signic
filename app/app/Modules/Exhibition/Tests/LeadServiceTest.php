<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Tests;

use App\Modules\Exhibition\DTOs\LeadCreationDTO;
use App\Modules\Exhibition\Models\Exhibitor;
use App\Modules\Exhibition\Models\Lead;
use App\Modules\Exhibition\Models\LeadTag;
use App\Modules\Exhibition\Services\LeadService;
use App\Modules\Identity\Models\User;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\Event;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;

class LeadServiceTest extends BaseTestCase
{
    use DatabaseTransactions;

    private LeadService $service;

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
        // migrations of the Identity, Registration and Exhibition modules are
        // applied.
        Artisan::call('migrate', [
            '--force' => true,
            '--path' => [
                'app/Modules/Identity/Database/Migrations',
                'app/Modules/Registration/Database/Migrations',
                'app/Modules/Exhibition/Database/Migrations',
            ],
        ]);

        $this->service = new LeadService();
    }

    #[Test]
    public function it_creates_a_lead_for_an_attendee(): void
    {
        [$exhibitor, $attendee] = $this->makeExhibitorAndAttendee();

        $lead = $this->service->createLead($exhibitor->id, new LeadCreationDTO(
            attendeeId: $attendee->id,
            location: 'Main Hall',
            notes: 'Asked about the enterprise plan',
            interestLevel: 'HIGH',
            additionalData: ['source' => 'badge_scan'],
        ));

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertTrue($lead->exists);
        $this->assertSame($exhibitor->id, $lead->exhibitor_id);
        $this->assertSame($attendee->id, $lead->attendee_id);
        $this->assertSame('Main Hall', $lead->location);
        $this->assertSame('HIGH', $lead->interest_level);
        $this->assertSame('NEW', $lead->status);
        $this->assertSame(['source' => 'badge_scan'], $lead->additional_data);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'exhibitor_id' => $exhibitor->id,
            'attendee_id' => $attendee->id,
        ]);
    }

    #[Test]
    public function it_defaults_the_interest_level_to_medium(): void
    {
        [$exhibitor, $attendee] = $this->makeExhibitorAndAttendee();

        $lead = $this->service->createLead($exhibitor->id, new LeadCreationDTO(
            attendeeId: $attendee->id,
            location: null,
            notes: null,
            interestLevel: null,
        ));

        $this->assertSame('MEDIUM', $lead->interest_level);
    }

    #[Test]
    public function it_throws_when_the_attendee_does_not_exist(): void
    {
        [$exhibitor] = $this->makeExhibitorAndAttendee();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->createLead($exhibitor->id, new LeadCreationDTO(
            attendeeId: 999999,
            location: null,
            notes: null,
            interestLevel: 'LOW',
        ));
    }

    #[Test]
    public function it_adds_and_removes_a_tag_on_a_lead(): void
    {
        [$exhibitor, $attendee] = $this->makeExhibitorAndAttendee();

        $lead = $this->service->createLead($exhibitor->id, new LeadCreationDTO(
            attendeeId: $attendee->id,
            location: 'Main Hall',
            notes: null,
            interestLevel: 'MEDIUM',
        ));

        $tag = $this->service->addTag($lead->id, 'Enterprise', null, '#FF0000');

        $this->assertInstanceOf(LeadTag::class, $tag);
        $this->assertSame('Enterprise', $tag->tag_name);
        $this->assertSame('#FF0000', $tag->color);
        $this->assertCount(1, $lead->fresh()->tags);

        $this->assertTrue($this->service->removeTag($lead->id, 'Enterprise'));
        $this->assertCount(0, $lead->fresh()->tags);
    }

    #[Test]
    public function it_filters_the_leads_of_an_exhibitor(): void
    {
        [$exhibitor, $attendee] = $this->makeExhibitorAndAttendee();

        $lead = $this->service->createLead($exhibitor->id, new LeadCreationDTO(
            attendeeId: $attendee->id,
            location: 'Main Hall',
            notes: null,
            interestLevel: 'HIGH',
        ));

        $this->assertCount(1, $this->service->getLeads($exhibitor->id));
        $this->assertCount(1, $this->service->getLeads($exhibitor->id, ['interest_level' => 'HIGH']));
        $this->assertCount(0, $this->service->getLeads($exhibitor->id, ['interest_level' => 'LOW']));
        $this->assertCount(1, $this->service->getLeads($exhibitor->id, ['attendee_id' => $attendee->id]));
        $this->assertCount(0, $this->service->getLeads($exhibitor->id, ['attendee_id' => 999999]));
    }

    /**
     * @return array{0: \App\Modules\Exhibition\Models\Exhibitor, 1: Attendee}
     */
    private function makeExhibitorAndAttendee(): array
    {
        $event = Event::create([
            'name' => 'Test Event',
            'slug' => 'test-event-'.uniqid(),
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
            'status' => 'ACTIVE',
        ]);

        $user = User::create([
            'phone' => '+989120000000',
            'name' => 'Test User',
        ]);

        $attendee = Attendee::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'phone' => '+989123456789',
            'name' => 'Test Attendee',
        ]);

        $exhibitor = \App\Modules\Exhibition\Models\Exhibitor::create([
            'event_id' => $event->id,
            'company_name' => 'Acme Corp',
            'status' => 'CONFIRMED',
        ]);

        return [$exhibitor, $attendee];
    }
}