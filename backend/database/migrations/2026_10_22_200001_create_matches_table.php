<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nanny_profile_id')->constrained()->cascadeOnDelete();
            // for_vacancy: e'lon uchun enagalar; for_nanny: enaga uchun e'lonlar
            $table->string('direction', 20);
            $table->unsignedTinyInteger('score');
            $table->string('reason_uz');
            $table->string('reason_ru');
            $table->string('source', 10); // ai | rules
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['vacancy_id', 'nanny_profile_id', 'direction']);
            $table->index(['nanny_profile_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
