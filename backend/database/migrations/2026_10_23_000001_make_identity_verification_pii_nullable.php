<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tekshiruvdan keyin pasport raqami va PINFL tozalanadi (verification:prune), shuning uchun ustunlar nullable. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->text('passport_number')->nullable()->change();
            $table->text('pinfl')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Tozalangan qatorlar bo'lishi mumkin — NOT NULL ga qaytarish xavfsiz emas.
    }
};
