<?php

namespace Tests\Feature;

use Tests\TestCase;
use App.Modules\Registration\Models\Attendee;
use App.Modules\Registration\Models\Event;
use App.Modules\Registration\Models\PrintJob;
use App.Modules\Registration\Models\QRBadge;
use App.Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendee_can_register_for_event_and_get_qr_badge(): void
    {
        // Create an event
        $event = Event::factory()->create([
            'name' => 'Test Event',
            'status' => \App.Modules\Registration\Enums\EventStatus::ACTIVE,
        ]);

        // Create a user (registrar)
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'status' => \App.Modules\Identity\Enums\UserStatus::ACTIVE,
        ]);

        // Simulate attendee registration (would normally go through API)
        $attendee = Attendee::factory()->create([
            'event_id' => $event->id,
            'registered_by' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+989123456789',
            'ticket_type' => \App.Modules\Registration\Enums\TicketType::GENERAL,
            'status' => \App.Modules\Registration\Enums\AttendeeStatus::CONFIRMED,
        ]);

        // Check attendee was created
        $this->assertDatabaseHas('attendees', [
            'id' => $attendee->id,
            'event_id' => $event->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        // Check QR badge was generated (through event listener or job)
        $this->assertDatabaseHas('qr_badges', [
            'attendee_id' => $attendee->id,
        ]);

        // Check print job was dispatched (would be in print_jobs table)
        $this->assertDatabaseHas('print_jobs', [
            'attendee_id' => $attendee->id,
            'status' => \App.Modules\Registration\Enums\JobStatus::PENDING,
        ]);
    }

    public function test_qr_token_can_be_generated_and_validated(): void
    {
        $attendee = Attendee::factory()->create();

        // This would normally go through QRService
        $qrService = app(\App.Modules\Registration\Contracts\QRServiceInterface::class);
        $token = $qrService->generate($attendee->id);

        $this->assertStringContainsString('|', $token);

        // Validate the token
        $validatedId = $qrService->validate($token);
        $this->assertEquals($attendee->id, $validatedId);
    }
}