<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('source')->nullable();
            // null = standart miqdor (config/bonus.php); 0 = bonus yo'q
            $table->unsignedInteger('survey_bonus')->nullable();
            $table->unsignedInteger('listing_bonus')->nullable();
            $table->unsignedInteger('referral_bonus')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('referred_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('referral_code', 8)->nullable()->unique();
        });

        Schema::table('telegram_login_requests', function (Blueprint $table) {
            $table->string('campaign_code', 32)->nullable();
            $table->string('referral_code', 8)->nullable();
        });

        Schema::create('bonus_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('type', 20);
            $table->string('description')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Idempotentlik: bir foydalanuvchiga bir xil (tur, reference) ikki marta yozilmaydi.
            $table->unique(['user_id', 'type', 'reference']);
            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('audience', 10);
            $table->string('title_uz');
            $table->string('title_ru');
            $table->text('intro_uz')->nullable();
            $table->text('intro_ru')->nullable();
            $table->json('questions');
            $table->boolean('is_active')->default(true);
            // Kampaniya qiymati bo'lmasa beriladigan bonus (so'm)
            $table->unsignedInteger('bonus_amount')->default(50000);
            $table->timestamps();
        });

        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->string('status', 20)->default('completed');
            $table->timestamp('bonus_awarded_at')->nullable();
            $table->string('quality_note')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->unique(['survey_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('bonus_transactions');
        Schema::table('telegram_login_requests', fn (Blueprint $t) => $t->dropColumn(['campaign_code', 'referral_code']));
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('campaign_id');
            $t->dropConstrainedForeignId('referred_by_id');
            $t->dropColumn('referral_code');
        });
        Schema::dropIfExists('campaigns');
    }
};
