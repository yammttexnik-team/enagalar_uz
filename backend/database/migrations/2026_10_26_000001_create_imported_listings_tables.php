<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ochiq Telegram kanallardan olingan e'lonlar (egasi hali tasdiqlamagan).
 * Kontakt (telefon, username) ochiq saqlanmaydi: faqat HMAC xeshi — egasi kirganda moslashtirish uchun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_sources', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 64)->unique(); // @siz username, masalan "enagalar"
            $table->string('title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('last_post_id')->nullable(); // oxirgi ko'rilgan post
            $table->timestamp('last_fetched_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('imported_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 16); // vacancy (ota-ona enaga qidiradi) | nanny (enaga ish qidiradi)
            $table->string('status', 16)->default('pending')->index();

            // Manba
            $table->string('source_channel', 64);
            $table->unsignedBigInteger('source_post_id');
            $table->string('source_url');
            $table->timestamp('source_posted_at')->nullable();
            $table->text('raw_text'); // encrypted cast: ichida kontakt bor, ochiq API'da hech qachon qaytmaydi
            $table->string('content_hash', 64)->index(); // takrorni aniqlash

            // AI ajratgan maydonlar (Vacancy / NannyProfile ga yaqin)
            $table->string('title')->nullable();
            $table->text('description')->nullable(); // tozalangan, kontaktsiz
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->string('work_format', 16)->nullable();
            $table->string('schedule')->nullable();
            $table->unsignedInteger('price_min')->nullable(); // vacancy: maosh dan; nanny: narx
            $table->unsignedInteger('price_max')->nullable();
            $table->string('price_unit', 8)->nullable();
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->json('children_ages')->nullable();
            $table->json('age_groups')->nullable();
            $table->json('languages')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->unsignedSmallInteger('person_age')->nullable(); // enaganing yoshi
            $table->boolean('live_in')->default(false);
            $table->boolean('is_urgent')->default(false);

            // Kontakt xeshlari (ImportedListing::contactHash) — egasini topish uchun
            $table->json('contact_hashes')->nullable();

            // Hayot sikli
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('archive_reason', 32)->nullable(); // deleted | found | expired | admin | removal_request | claimed
            $table->timestamp('removal_requested_at')->nullable();
            $table->string('removal_reason', 500)->nullable();
            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('claim_dismissed_at')->nullable(); // egasi "bu meniki emas/kerak emas" dedi
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->unique(['source_channel', 'source_post_id']);
            $table->index(['type', 'status', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_listings');
        Schema::dropIfExists('import_sources');
    }
};
