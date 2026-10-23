<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** AI moderatsiya oldindan bahosi: xavf (0-100), xulosa (ok|check|bad), belgilar va qisqa izoh. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['vacancies', 'nanny_profiles'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedTinyInteger('ai_risk')->nullable();
                $table->string('ai_verdict', 16)->nullable()->index();
                $table->json('ai_flags')->nullable();
                $table->string('ai_summary')->nullable();
                $table->timestamp('ai_checked_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['vacancies', 'nanny_profiles'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['ai_verdict']);
                $table->dropColumn(['ai_risk', 'ai_verdict', 'ai_flags', 'ai_summary', 'ai_checked_at']);
            });
        }
    }
};
