<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->string('tag_name');
            $table->string('color')->default('#6B7280');
            $table->text('note')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['lead_id']);
            $table->unique(['lead_id', 'tag_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tags');
    }
};