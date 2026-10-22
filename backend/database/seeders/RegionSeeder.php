<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Support\UzTransliterator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['tashkent-city', 'Toshkent shahri', 'г. Ташкент'],
            ['tashkent', 'Toshkent viloyati', 'Ташкентская область'],
            ['andijan', 'Andijon viloyati', 'Андижанская область'],
            ['bukhara', 'Buxoro viloyati', 'Бухарская область'],
            ['fergana', "Farg'ona viloyati", 'Ферганская область'],
            ['jizzakh', 'Jizzax viloyati', 'Джизакская область'],
            ['kashkadarya', 'Qashqadaryo viloyati', 'Кашкадарьинская область'],
            ['khorezm', 'Xorazm viloyati', 'Хорезмская область'],
            ['namangan', 'Namangan viloyati', 'Наманганская область'],
            ['navoi', 'Navoiy viloyati', 'Навоийская область'],
            ['samarkand', 'Samarqand viloyati', 'Самаркандская область'],
            ['surkhandarya', 'Surxondaryo viloyati', 'Сурхандарьинская область'],
            ['syrdarya', 'Sirdaryo viloyati', 'Сырдарьинская область'],
            ['karakalpakstan', "Qoraqalpog'iston Respublikasi", 'Республика Каракалпакстан'],
        ];

        $districts = require database_path('data/districts.php');

        foreach ($regions as $sort => [$slug, $uz, $ru]) {
            $region = Region::updateOrCreate(['slug' => $slug], [
                'name_uz' => $uz,
                'name_uz_cyrl' => UzTransliterator::toCyrillic($uz),
                'name_ru' => $ru,
                'sort' => $sort,
            ]);

            foreach ($districts[$slug] ?? [] as $districtSort => [$districtUz, $districtRu]) {
                $region->districts()->updateOrCreate(['slug' => Str::slug(str_replace("'", '', $districtUz))], [
                    'name_uz' => $districtUz,
                    'name_uz_cyrl' => UzTransliterator::toCyrillic($districtUz),
                    'name_ru' => $districtRu,
                    'sort' => $districtSort,
                ]);
            }
        }
    }
}
