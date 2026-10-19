<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('phone')->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('dietary_requirements')->nullable();
            $table->string('accessibility_needs')->nullable();
            $table->text('special_instructions')->nullable();
            $table->enum('ticket_type', ['GENERAL', 'VIP', 'SPEAKER', 'PRESS', 'ORGANIZER', 'VOLUNTEER'])->default('GENERAL');
            $table->enum('status', ['REGISTERED', 'CONFIRMED', 'ATTENDED', 'NO_SHOW', 'CANCELLED', 'CHECKED_IN', 'CHECKED_OUT'])->default('REGISTERED');
            $table->string('qr_token_hash')->nullable()->unique();
            $table->timestamp('qr_token_generated_at')->nullable();
            $table->timestamp('qr_token_expires_at')->nullable();
            $table->timestamp('checkin_at')->nullable();
            $table->timestamp('checkout_at')->nullable();
            $table->boolean('badge_printed')->default(false);
            $table->timestamp('badge_printed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'user_id']);
            $table->index(['status']);
            $table->index(['ticket_type']);
            $table->index(['qr_token_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendees');
    }
};
