<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('topic_key', 80)->nullable()->unique();
            $table->string('title_uz');
            $table->string('title_ru');
            $table->text('excerpt_uz');
            $table->text('excerpt_ru');
            $table->longText('body_uz');
            $table->longText('body_ru');
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
