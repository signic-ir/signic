<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('code')->index();
            $table->string('purpose')->default('authentication');
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['phone', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
