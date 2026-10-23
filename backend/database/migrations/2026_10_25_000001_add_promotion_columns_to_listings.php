<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pullik xizmatlar (bonus bilan): TOP e'lon va "Shoshilinch" belgisi muddati.
 * Vakansiyadagi `is_urgent` — ota-onaning bepul belgisi bo'lib qoladi; `urgent_until` — sotib olingan belgi.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['vacancies', 'nanny_profiles'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->timestamp('top_until')->nullable()->index();
                $table->timestamp('urgent_until')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['vacancies', 'nanny_profiles'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropIndex($name.'_top_until_index');
                $table->dropColumn(['top_until', 'urgent_until']);
            });
        }
    }
};
