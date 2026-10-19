<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendee_id')->constrained('attendees')->onDelete('cascade');
            $table->foreignId('turnstile_id')->constrained('turnstiles')->onDelete('cascade');
            $table->string('token_hash');
            $table->enum('event_type', ['CHECK_IN', 'CHECK_OUT', 'FORCE_CHECK_IN', 'FORCE_CHECK_OUT']);
            $table->enum('result', ['SUCCESS', 'DUPLICATE', 'NOT_FOUND', 'EXPIRED', 'INVALID']);
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index(['token_hash']);
            $table->index(['attendee_id']);
            $table->index(['turnstile_id']);
            $table->index(['event_type']);
            $table->index(['scanned_at']);
            $table->unique(['token_hash', 'event_type', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_events');
    }
};