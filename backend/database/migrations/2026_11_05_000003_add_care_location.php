<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Bola qayerda qaraladi: oilaning uyida (family_home) yoki enaganing uyida (nanny_home). Null — noma'lum. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nanny_profiles', function (Blueprint $table) {
            $table->json('care_locations')->nullable();
        });
        Schema::table('vacancies', function (Blueprint $table) {
            $table->string('care_location', 20)->nullable();
        });
        Schema::table('imported_listings', function (Blueprint $table) {
            $table->string('care_location', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('nanny_profiles', fn (Blueprint $t) => $t->dropColumn('care_locations'));
        Schema::table('vacancies', fn (Blueprint $t) => $t->dropColumn('care_location'));
        Schema::table('imported_listings', fn (Blueprint $t) => $t->dropColumn('care_location'));
    }
};
