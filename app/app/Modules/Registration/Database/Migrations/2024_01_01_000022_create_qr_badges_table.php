<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendee_id')->constrained('attendees')->onDelete('cascade');
            $table->string('token_hash')->unique()->index();
            $table->string('design_version')->default('v1');
            $table->boolean('is_active')->default(true);
            $table->timestamp('generated_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('design_config_json')->nullable();
            $table->timestamps();

            $table->index(['attendee_id']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_badges');
    }
};
