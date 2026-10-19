<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exhibitor_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibitor_id')->constrained('exhibitors')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('member');
            $table->text('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['exhibitor_id']);
            $table->index(['user_id']);
            $table->unique(['exhibitor_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exhibitor_members');
    }
};
