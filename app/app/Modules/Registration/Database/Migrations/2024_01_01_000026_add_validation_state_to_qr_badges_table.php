<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_badges', function (Blueprint $table) {
            $table->unsignedInteger('validation_count')->default(0)->after('is_active');
            $table->timestamp('last_validated_at')->nullable()->after('validation_count');
        });
    }

    public function down(): void
    {
        Schema::table('qr_badges', function (Blueprint $table) {
            $table->dropColumn(['validation_count', 'last_validated_at']);
        });
    }
};
