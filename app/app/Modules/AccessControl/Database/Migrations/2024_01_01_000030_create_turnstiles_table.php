<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnstiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('type')->default('gate'); // gate, general, vip, staff
            $table->string('ip_address')->nullable();
            $table->string('hardware_model')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnstiles');
    }
};