<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nishon profilda ko'rinadimi: null — foydalanuvchi javobi kutilmoqda, true — ko'rsatiladi, false — yashirilgan.
 * Admin bergan nishon foydalanuvchi roziligidan keyingina ochiq profilda chiqadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->boolean('is_public')->nullable()->after('expires_at');
            $table->timestamp('answered_at')->nullable()->after('is_public');
        });

        // Mavjud nishonlar avvalgidek ko'rinib turadi.
        DB::table('user_badges')->update(['is_public' => true]);
    }

    public function down(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'answered_at']);
        });
    }
};
