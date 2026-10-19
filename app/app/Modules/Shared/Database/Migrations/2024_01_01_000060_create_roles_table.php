<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('guard_name')->default('web');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->foreignId('parent_role_id')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->index(['guard_name']);
            $table->index('parent_role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
