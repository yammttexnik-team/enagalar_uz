<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_uz');
            $table->string('name_uz_cyrl');
            $table->string('name_ru');
            $table->unsignedSmallInteger('sort')->default(0);
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name_uz');
            $table->string('name_uz_cyrl');
            $table->string('name_ru');
            $table->unsignedSmallInteger('sort')->default(0);

            $table->unique(['region_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regions');
    }
};
