<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_telegram')->default(true);
            $table->timestamp('last_match_notified_at')->nullable();
            $table->timestamp('nudged_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_telegram', 'last_match_notified_at', 'nudged_at']);
        });
    }
};
