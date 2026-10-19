<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exhibitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('company_name');
            $table->string('booth_number')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('booth_description')->nullable();
            $table->json('booth_specs')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'inactive'])->default('pending');
            $table->json('contact_person')->nullable();
            $table->timestamps();

            $table->index(['event_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exhibitors');
    }
};