<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_uz');
            $table->string('name_ru');
        });

        Schema::create('parent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->text('about')->nullable();
            $table->timestamps();
        });

        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('birth_year');
            $table->string('gender', 10)->nullable();
            $table->text('special_needs')->nullable();
            $table->timestamps();
        });

        Schema::create('nanny_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->unsignedBigInteger('price_amount')->nullable();
            $table->string('price_unit', 10)->default('month');
            $table->json('work_formats')->nullable();
            $table->json('languages')->nullable();
            $table->json('age_groups')->nullable();
            $table->boolean('live_in')->default(false);
            $table->boolean('free_trial_day')->default(false);
            $table->boolean('is_available')->default(true);
            $table->string('status', 20)->default('draft');
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'region_id', 'district_id']);
        });

        Schema::create('nanny_profile_skill', function (Blueprint $table) {
            $table->foreignId('nanny_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['nanny_profile_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nanny_profile_skill');
        Schema::dropIfExists('nanny_profiles');
        Schema::dropIfExists('children');
        Schema::dropIfExists('parent_profiles');
        Schema::dropIfExists('skills');
    }
};
