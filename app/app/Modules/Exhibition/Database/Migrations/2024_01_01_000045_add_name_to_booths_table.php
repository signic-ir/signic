<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booths', function (Blueprint $table) {
            if (! Schema::hasColumn('booths', 'name')) {
                $table->string('name')->nullable()->after('number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booths', function (Blueprint $table) {
            if (Schema::hasColumn('booths', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
