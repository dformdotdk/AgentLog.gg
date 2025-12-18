<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('book_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('max_activations')->default(0); // 0 = unlimited
            $table->unsignedInteger('activation_count')->default(0);
            $table->timestampsTz();

            $table->index(['season_id','is_active']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('book_tokens');
    }
};
