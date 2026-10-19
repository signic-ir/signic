<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('roles', 'inherits_all')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('inherits_all')->default(false);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'inherits_all')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('inherits_all');
            });
        }
    }
};
