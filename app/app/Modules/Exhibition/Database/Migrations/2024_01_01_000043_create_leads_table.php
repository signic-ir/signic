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
            $table->foreignId('booth_id')->nullable()->constrained('booths')->onDelete('set null');
            $table->timestamp('scanned_at');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->enum('interest_level', ['LOW', 'MEDIUM', 'HIGH'])->default('MEDIUM');
            $table->enum('status', ['NEW', 'QUALIFIED', 'CONTACTED', 'CONVERTED', 'LOST'])->default('NEW');
            $table->boolean('follow_up_sent')->default(false);
            $table->boolean('contacted')->default(false);
            $table->json('additional_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exhibitor_id']);
            $table->index(['attendee_id']);
            $table->index(['scanned_at']);
            $table->index(['interest_level']);
            $table->index(['status']);
        });

        Schema::create('lead_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('field');
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->timestamps();

            $table->index(['lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_histories');
        Schema::dropIfExists('leads');
    }
};
