<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [Badge::PHONE_VERIFIED, 'Telefon tasdiqlangan', 'Телефон подтверждён', 'i-lucide-smartphone', 'neutral', 'auto', null],
            [Badge::VERIFIED, 'Tasdiqlangan foydalanuvchi', 'Проверенный пользователь', 'i-lucide-badge-check', 'success', 'manual', null],
            [Badge::MEDICAL, 'Tibbiy ma\'lumotnoma', 'Медицинская справка', 'i-lucide-stethoscope', 'info', 'manual', 365],
            [Badge::CERTIFICATE, 'Sertifikat / ma\'lumot', 'Сертификат / образование', 'i-lucide-graduation-cap', 'primary', 'manual', null],
        ];

        foreach ($badges as $sort => [$slug, $uz, $ru, $icon, $color, $grantType, $validity]) {
            Badge::updateOrCreate(['slug' => $slug], [
                'name_uz' => $uz,
                'name_ru' => $ru,
                'icon' => $icon,
                'color' => $color,
                'grant_type' => $grantType,
                'validity_days' => $validity,
                'sort' => $sort,
            ]);
        }
    }
}
