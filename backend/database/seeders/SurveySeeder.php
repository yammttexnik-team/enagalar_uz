<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Survey;
use Illuminate\Database\Seeder;

/**
 * docs/MARKETING_REJA.md ("## Sorovnoma savollari") dan olingan savollar (data/surveys.json).
 * Mavjud so'rovnomani qayta yozmaydi (admin tahrirlarini saqlaydi).
 */
class SurveySeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/surveys.json'), true, flags: JSON_THROW_ON_ERROR);

        $regions = Region::orderBy('sort')->get()->map(fn ($r) => ['value' => $r->slug, 'uz' => $r->name_uz, 'ru' => $r->name_ru])->all();

        $intro = [
            'uz' => "Javoblaringiz enagalar.uz platformasini yaxshilash uchun ishlatiladi. Natijalar faqat umumiy (ismsiz) ko'rinishda e'lon qilinadi. Telefon raqamingiz bonusni hisoblash uchun saqlanadi va uchinchi shaxsga berilmaydi. Istagan payt o'chirishni so'rashingiz mumkin. Bonus platforma ichida ishlatiladi, naqdga aylanmaydi.",
            'ru' => 'Ответы используются для улучшения enagalar.uz. Результаты публикуются только в обобщённом виде. Телефон хранится для начисления бонуса и не передаётся третьим лицам. Можно в любой момент попросить удалить данные. Бонус действует внутри платформы, не обналичивается.',
        ];

        $meta = [
            'nanny' => ['enaga', 'Enagalar uchun so\'rovnoma', 'Опрос для нянь'],
            'parent' => ['ota-ona', 'Ota-onalar uchun so\'rovnoma', 'Опрос для родителей'],
        ];

        foreach ($meta as $audience => [$slug, $titleUz, $titleRu]) {
            $questions = array_map(function (array $q) use ($regions) {
                if (($q['source'] ?? null) === 'regions') {
                    $q['options'] = $regions;
                }
                unset($q['source']);

                return $q;
            }, $data[$audience]);

            Survey::firstOrCreate(['slug' => $slug], [
                'audience' => $audience,
                'title_uz' => $titleUz,
                'title_ru' => $titleRu,
                'intro_uz' => $intro['uz'],
                'intro_ru' => $intro['ru'],
                'questions' => $questions,
                'is_active' => true,
                'bonus_amount' => config('bonus.survey'),
            ]);
        }
    }
}
