<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('rules')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('registration_start_at')->nullable();
            $table->timestamp('registration_end_at')->nullable();
            $table->unsignedInteger('max_capacity')->nullable();
            $table->boolean('is_registration_open')->default(true);
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'cancelled'])->default('draft');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
