<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Import qilingan e'lonlarning o'zbekcha (lotin) va ruscha matni. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imported_listings', function (Blueprint $table) {
            $table->string('title_uz', 160)->nullable();
            $table->text('description_uz')->nullable();
            $table->string('title_ru', 160)->nullable();
            $table->text('description_ru')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('imported_listings', function (Blueprint $table) {
            $table->dropColumn(['title_uz', 'description_uz', 'title_ru', 'description_ru']);
        });
    }
};
