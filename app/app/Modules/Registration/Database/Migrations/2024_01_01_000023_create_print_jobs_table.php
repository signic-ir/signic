<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendee_id')->constrained('attendees')->onDelete('cascade');
            $table->foreignId('printed_by_user_id')->constrained('users')->nullable();
            $table->string('status')->default('pending'); // pending, printing, printed, failed
            $table->string('qr_code')->nullable();
            $table->string('badge_data')->nullable();
            $table->json('print_settings')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['attendee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};