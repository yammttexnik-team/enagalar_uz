<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bog'lanish: suhbatda ota-ona ham, enaga ham kamida bitta xabar yozgan.
 * 3 kundan keyin ikkalasidan "Birga ishladingizmi?" so'raladi, ikkalasi "Ha" desa — bir-birini baholaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('nanny_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('connected_at');
            $table->timestamp('asked_at')->nullable();
            $table->boolean('parent_worked')->nullable();
            $table->timestamp('parent_answered_at')->nullable();
            $table->boolean('nanny_worked')->nullable();
            $table->timestamp('nanny_answered_at')->nullable();
            $table->timestamps();

            $table->index(['asked_at', 'connected_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('connection_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            // Baholangan tomon: nanny yoki parent (bir foydalanuvchida ikkala rol bo'lishi mumkin)
            $table->string('target_role', 10)->default('nanny')->after('target_user_id');
            $table->index(['target_user_id', 'target_role', 'status']);
        });

        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->dropColumn(['rating_avg', 'reviews_count']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['target_user_id', 'target_role', 'status']);
            $table->dropConstrainedForeignId('connection_id');
            $table->dropColumn('target_role');
        });

        Schema::dropIfExists('connections');
    }
};
