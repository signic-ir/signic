<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibitor_id')->constrained('exhibitors')->onDelete('cascade');
            $table->foreignId('attendee_id')->constrained('attendees')->onDelete('cascade');
            $table->timestamp('scanned_at');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('interest_level')->default('medium'); // low, medium, high
            $table->boolean('follow_up_sent')->default(false);
            $table->boolean('contacted')->default(false);
            $table->json('additional_data')->nullable();
            $table->timestamps();

            $table->unique(['exhibitor_id', 'attendee_id']);
            $table->index(['scanned_at']);
            $table->index(['interest_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
