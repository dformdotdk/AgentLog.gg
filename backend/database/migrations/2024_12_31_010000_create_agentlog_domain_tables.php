<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('theme_config')->nullable();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('series')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('subject');
            $table->string('grade_band');
            $table->string('language');
            $table->timestamps();
            $table->unique(['series_id','slug']);
        });

        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->unsignedInteger('season_no');
            $table->string('version');
            $table->json('rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['book_id','season_no']);
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('provider');
            $table->string('provider_id');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->json('subtitles')->nullable();
            $table->timestamps();
        });

        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->unsignedInteger('mission_no');
            $table->string('slug');
            $table->unsignedInteger('xp_reward')->default(0);
            $table->boolean('is_boss')->default(false);
            $table->json('topic_tags')->nullable();
            $table->json('assets')->nullable();
            $table->json('validation')->nullable();
            $table->timestamps();
            $table->unique(['season_id','mission_no']);
        });

        Schema::create('mission_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('parent_only')->default(false);
            $table->boolean('teacher_only')->default(false);
            $table->timestamps();
        });

        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->json('settings')->nullable();
            $table->string('wizard_state')->default('not_started');
            $table->string('parent_pin_hash')->nullable();
            $table->timestamps();
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('agent_key')->unique();
            $table->timestamps();
        });

        Schema::create('parent_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('email');
            $table->string('status')->default('pending');
            $table->string('verification_token');
            $table->string('unsub_token');
            $table->json('prefs')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_season_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->unsignedInteger('xp_total')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->string('paired_mode');
            $table->string('paired_ref');
            $table->timestamps();
            $table->unique(['agent_id','season_id']);
        });

        Schema::create('agent_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->string('status');
            $table->unsignedInteger('attempts_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['agent_id','mission_id']);
        });

        Schema::create('mission_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->boolean('success');
            $table->string('answer_hash');
            $table->timestamps();
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('xp_cost');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reward_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('rewards')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->timestamp('available_at');
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->string('class_code');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('class_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('unlinked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_agents');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('reward_claims');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('mission_attempts');
        Schema::dropIfExists('agent_progress');
        Schema::dropIfExists('agent_season_states');
        Schema::dropIfExists('parent_contacts');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('households');
        Schema::dropIfExists('mission_videos');
        Schema::dropIfExists('missions');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('seasons');
        Schema::dropIfExists('books');
        Schema::dropIfExists('series');
    }
};
