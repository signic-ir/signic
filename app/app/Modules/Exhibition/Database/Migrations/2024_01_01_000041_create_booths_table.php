<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibitor_id')->constrained('exhibitors')->onDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('number');
            $table->string('section')->nullable();
            $table->string('location')->nullable();
            $table->json('layout_config')->nullable();
            $table->timestamps();

            $table->index(['exhibitor_id']);
            $table->unique(['event_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booths');
    }
};