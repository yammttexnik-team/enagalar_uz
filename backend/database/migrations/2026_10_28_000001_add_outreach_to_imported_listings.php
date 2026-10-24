<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** E'lon egasiga shaxsiy xabar yozilgani (bir odamga bir marta yozish uchun). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imported_listings', function (Blueprint $table) {
            $table->timestamp('outreach_at')->nullable()->index();
            $table->foreignId('outreach_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('imported_listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outreach_by');
            $table->dropIndex(['outreach_at']);
            $table->dropColumn('outreach_at');
        });
    }
};
