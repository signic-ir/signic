<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('class_name');
            $table->text('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_providers');
    }
};