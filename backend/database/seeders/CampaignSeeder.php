<?php

namespace Database\Seeders;

use App\Models\Campaign;
use Illuminate\Database\Seeder;

/** Marketing rejasidagi (A-qism) kampaniya kodlari. Bonus miqdorlari bo'sh = config/bonus.php standarti. */
class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $campaigns = [
            'SURVEY_NANNY' => "So'rovnoma — enagalar (umumiy)",
            'SURVEY_PARENT' => "So'rovnoma — ota-onalar (umumiy)",
            'SURVEY_ENAGALAR' => "So'rovnoma — @enagalar kanali",
            'SURVEY_MOYA' => "So'rovnoma — Moya kanali",
            'SURVEY_SAMARKAND' => "So'rovnoma — Samarqand kanali",
            'SURVEY_ENAGA_ISH' => "So'rovnoma — Enaga ish kanali",
            'REPOST_ENAGALAR' => "Repost — @enagalar kanali",
            'REPOST_MOYA' => 'Repost — Moya kanali',
            'REPOST_SAMARKAND' => 'Repost — Samarqand kanali',
            'REPOST_ENAGA_ISH' => 'Repost — Enaga ish kanali',
            'REPOST_NYANI' => 'Repost — Nyani kanali',
            'OWNCH_UZ' => "O'z kanalimiz (o'zbekcha)",
            'OWNCH_RU' => "O'z kanalimiz (ruscha)",
            'FRIENDS' => "Do'stlar / tanishlar",
        ];

        foreach ($campaigns as $code => $name) {
            Campaign::firstOrCreate(['code' => $code], ['name' => $name, 'source' => $name, 'is_active' => true]);
        }
    }
}
