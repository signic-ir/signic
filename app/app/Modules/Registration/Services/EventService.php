<?php

declare(strict_types=1);

namespace App\Modules\Registration\Services;

use App\Modules\Registration\Enums\EventStatus;
use App\Modules\Registration\Enums\TicketType;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventService
{
    /**
     * Find an event by its name.
     */
    public function findByName(string $name): ?Event
    {
        return Event::where('name', $name)->first();
    }

    /**
     * Find an event by id.
     */
    public function findById(int $id): ?Event
    {
        return Event::find($id);
    }

    /**
     * Resolve the event a request refers to.
     *
     * An event may be addressed by id or by name. When it is addressed by name
     * and no event carries that name yet, the event is created so that a
     * registration can always be associated with the event it names.
     *
     * @param  array<string, mixed>  $data
     */
    public function resolve(array $data): ?Event
    {
        if (! empty($data['event_id'])) {
            return $this->findById((int) $data['event_id']);
        }

        if (! empty($data['event_name'])) {
            $name = (string) $data['event_name'];

            return $this->findByName($name) ?? $this->create([
                'name' => $name,
                'status' => EventStatus::Active->value,
            ]);
        }

        return null;
    }

    /**
     * Create an event.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Event
    {
        $startsAt = $data['starts_at'] ?? now();
        $endsAt = $data['ends_at'] ?? now()->addDay();

        return Event::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $data['slug'] ?? null),
            'description' => $data['description'] ?? null,
            'rules' => $data['rules'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'registration_start_at' => $data['registration_start_at'] ?? null,
            'registration_end_at' => $data['registration_end_at'] ?? null,
            'max_capacity' => $data['max_capacity'] ?? null,
            'is_registration_open' => $data['is_registration_open'] ?? true,
            'status' => $data['status'] ?? EventStatus::Draft->value,
            'settings' => $data['settings'] ?? null,
        ]);
    }

    /**
     * Build the analytics report of an event.
     *
     * When no event is given the report is aggregated over every event.
     *
     * @return array<string, mixed>
     */
    public function report(?Event $event = null): array
    {
        $attendees = Attendee::query();

        if ($event) {
            $attendees->where('event_id', $event->id);
        }

        $totalRegistered = (clone $attendees)->count();

        $totalCheckedIn = (clone $attendees)->where(function ($query) {
            $query->whereIn('status', [
                \App\Modules\Registration\Enums\AttendeeStatus::CheckedIn->value,
                \App\Modules\Registration\Enums\AttendeeStatus::Attended->value,
                \App\Modules\Registration\Enums\AttendeeStatus::CheckedOut->value,
            ])->orWhereNotNull('checkin_at');
        })->count();

        $totalLeads = $this->countLeads($event);

        $conversionRate = $totalRegistered > 0
            ? round(($totalCheckedIn / $totalRegistered) * 100, 2)
            : 0.0;

        return [
            'event_id' => $event?->id,
            'event_name' => $event?->name,
            'total_registered_attendees' => $totalRegistered,
            'total_checked_in_attendees' => $totalCheckedIn,
            'total_leads_captured' => $totalLeads,
            'conversion_rate' => $conversionRate,
            'leads_per_exhibitor' => $this->leadsPerExhibitor($event),
            'popular_ticket_types' => $this->popularTicketTypes($attendees),
        ];
    }

    /**
     * The number of leads captured for an event.
     */
    protected function countLeads(?Event $event): int
    {
        $query = DB::table('leads');

        if ($event) {
            $query->join('attendees', 'attendees.id', '=', 'leads.attendee_id')
                ->where('attendees.event_id', $event->id);
        }

        return (clone $query)->count();
    }

    /**
     * The number of leads every exhibitor captured.
     *
     * @return array<int, array{exhibitor_id: int, exhibitor_name: string, lead_count: int}>
     */
    protected function leadsPerExhibitor(?Event $event): array
    {
        $query = DB::table('exhibitors')
            ->leftJoin('leads', 'leads.exhibitor_id', '=', 'exhibitors.id')
            ->select('exhibitors.id as exhibitor_id', 'exhibitors.company_name as exhibitor_name')
            ->selectRaw('COUNT(leads.id) as lead_count')
            ->groupBy('exhibitors.id', 'exhibitors.company_name')
            ->orderBy('exhibitors.id');

        if ($event) {
            $query->where('exhibitors.event_id', $event->id);
        }

        return $query->get()->map(fn ($row) => [
            'exhibitor_id' => (int) $row->exhibitor_id,
            'exhibitor_name' => (string) $row->exhibitor_name,
            'lead_count' => (int) $row->lead_count,
        ])->all();
    }

    /**
     * The number of attendees per ticket type.
     *
     * @return array<int, array{ticket_type: string, count: int}>
     */
    protected function popularTicketTypes($attendees): array
    {
        return $attendees
            ->select('ticket_type', DB::raw('COUNT(*) as count'))
            ->groupBy('ticket_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'ticket_type' => (string) $row->ticket_type,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * Build a unique slug for an event.
     */
    protected function uniqueSlug(string $name, ?string $slug = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'event';
        $candidate = $base;
        $suffix = 1;

        while (Event::where('slug', $candidate)->exists()) {
            $suffix++;
            $candidate = $base . '-' . $suffix;
        }

        return $candidate;
    }

    /**
     * The ticket types the platform accepts.
     *
     * @return array<int, string>
     */
    public function ticketTypes(): array
    {
        return array_map(fn (TicketType $type) => $type->value, TicketType::cases());
    }
}
