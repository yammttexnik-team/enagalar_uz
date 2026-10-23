<?php

namespace Database\Seeders;

use App\Models\ImportSource;
use Illuminate\Database\Seeder;

/** E'lon olinadigan ochiq Telegram kanallar (mavjudlariga tegmaydi — admin paneldan o'zgartiriladi). */
class ImportSourceSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            'enagalar' => 'Enagalar',
            'enaga_ish' => 'Enaga ish',
            'nyanya_toshkent' => 'Няня Ташкент',
            'toshkent_enagauz' => 'Toshkent enagalari',
            'moya_nyanyauz' => 'Моя няня',
            'enaga_samarqand' => 'Enaga Samarqand',
        ];

        foreach ($channels as $channel => $title) {
            ImportSource::firstOrCreate(['channel' => $channel], ['title' => $title, 'is_active' => true]);
        }
    }
}
