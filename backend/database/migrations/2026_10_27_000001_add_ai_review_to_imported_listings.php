<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imported_listings', function (Blueprint $table) {
            $table->string('ai_decision', 16)->nullable()->after('status'); // approve|reject|manual
            $table->string('ai_reason')->nullable()->after('ai_decision');
            $table->timestamp('ai_reviewed_at')->nullable()->after('ai_reason');
        });
    }

    public function down(): void
    {
        Schema::table('imported_listings', function (Blueprint $table) {
            $table->dropColumn(['ai_decision', 'ai_reason', 'ai_reviewed_at']);
        });
    }
};
