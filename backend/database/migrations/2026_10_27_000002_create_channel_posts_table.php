<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_posts', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->default('digest');
            $table->longText('text');
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedSmallInteger('items_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('telegram_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_posts');
    }
};
