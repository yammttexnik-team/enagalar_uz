<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_login_requests', function (Blueprint $table) {
            // Bot yuborgan bir martalik kodning xeshi va noto'g'ri urinishlar soni
            $table->string('code_hash')->nullable()->after('status');
            $table->unsignedTinyInteger('attempts')->default(0)->after('code_hash');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_login_requests', function (Blueprint $table) {
            $table->dropColumn(['code_hash', 'attempts']);
        });
    }
};
