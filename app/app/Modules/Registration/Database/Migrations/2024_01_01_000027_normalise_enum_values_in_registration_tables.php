<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalise the enum columns of the registration tables.
 *
 * The tables were created with lower case enum values while the platform
 * spells every status in upper case. The migration is idempotent, it also
 * runs cleanly on a database that was created with the current migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropConstraint('attendees', 'attendees_status_check');
        $this->dropConstraint('attendees', 'attendees_ticket_type_check');
        $this->dropConstraint('events', 'events_status_check');

        DB::statement('UPDATE attendees SET status = UPPER(status) WHERE status <> UPPER(status)');
        DB::statement('UPDATE attendees SET ticket_type = UPPER(ticket_type) WHERE ticket_type <> UPPER(ticket_type)');
        DB::statement('UPDATE events SET status = UPPER(status) WHERE status <> UPPER(status)');

        DB::statement("ALTER TABLE attendees ADD CONSTRAINT attendees_status_check CHECK (status::text = ANY (ARRAY['REGISTERED','CONFIRMED','ATTENDED','NO_SHOW','CANCELLED','CHECKED_IN','CHECKED_OUT']::text[]))");
        DB::statement("ALTER TABLE attendees ADD CONSTRAINT attendees_ticket_type_check CHECK (ticket_type::text = ANY (ARRAY['GENERAL','VIP','SPEAKER','PRESS','ORGANIZER','VOLUNTEER']::text[]))");
        DB::statement("ALTER TABLE events ADD CONSTRAINT events_status_check CHECK (status::text = ANY (ARRAY['DRAFT','ACTIVE','PAUSED','COMPLETED','CANCELLED']::text[]))");

        DB::statement("ALTER TABLE attendees ALTER COLUMN status SET DEFAULT 'REGISTERED'");
        DB::statement("ALTER TABLE attendees ALTER COLUMN ticket_type SET DEFAULT 'GENERAL'");
        DB::statement("ALTER TABLE events ALTER COLUMN status SET DEFAULT 'DRAFT'");
    }

    public function down(): void
    {
        $this->dropConstraint('attendees', 'attendees_status_check');
        $this->dropConstraint('attendees', 'attendees_ticket_type_check');
        $this->dropConstraint('events', 'events_status_check');

        DB::statement('UPDATE attendees SET status = LOWER(status) WHERE status <> LOWER(status)');
        DB::statement('UPDATE attendees SET ticket_type = LOWER(ticket_type) WHERE ticket_type <> LOWER(ticket_type)');
        DB::statement('UPDATE events SET status = LOWER(status) WHERE status <> LOWER(status)');

        DB::statement("ALTER TABLE attendees ADD CONSTRAINT attendees_status_check CHECK (status::text = ANY (ARRAY['registered','confirmed','attended','no_show','cancelled','checked_in','checked_out']::text[]))");
        DB::statement("ALTER TABLE attendees ADD CONSTRAINT attendees_ticket_type_check CHECK (ticket_type::text = ANY (ARRAY['general','vip','speaker','press','organizer','volunteer']::text[]))");
        DB::statement("ALTER TABLE events ADD CONSTRAINT events_status_check CHECK (status::text = ANY (ARRAY['draft','active','paused','completed','cancelled']::text[]))");

        DB::statement("ALTER TABLE attendees ALTER COLUMN status SET DEFAULT 'registered'");
        DB::statement("ALTER TABLE attendees ALTER COLUMN ticket_type SET DEFAULT 'general'");
        DB::statement("ALTER TABLE events ALTER COLUMN status SET DEFAULT 'draft'");
    }

    protected function dropConstraint(string $table, string $constraint): void
    {
        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
    }
};
