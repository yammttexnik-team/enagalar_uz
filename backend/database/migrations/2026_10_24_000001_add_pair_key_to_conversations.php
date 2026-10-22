<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Ikki ishtirokchi juftligi ("kichik_id:katta_id"): dublikat suhbatning oldini oladi.
            $table->string('pair_key', 40)->nullable()->unique();
        });

        // Eski suhbatlarga kalit qo'yamiz (ikki ishtirokchili va dublikatsiz bo'lganlarga).
        $pairs = DB::table('conversation_participants')
            ->select('conversation_id', DB::raw('min(user_id) as lo'), DB::raw('max(user_id) as hi'))
            ->groupBy('conversation_id')
            ->havingRaw('count(*) = 2')
            ->orderBy('conversation_id')
            ->get();

        $seen = [];
        foreach ($pairs as $p) {
            $key = $p->lo.':'.$p->hi;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            DB::table('conversations')->where('id', $p->conversation_id)->update(['pair_key' => $key]);
        }
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['pair_key']);
            $table->dropColumn('pair_key');
        });
    }
};
