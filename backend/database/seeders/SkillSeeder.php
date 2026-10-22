<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    // Ko'nikmalar nomini admin o'zgartirishi mumkin: mavjudlari qayta yozilmaydi.
    public function run(): void
    {
        $skills = [
            ['newborn-care', 'Chaqaloq parvarishi', 'Уход за новорождённым'],
            ['cooking', 'Ovqat tayyorlash', 'Приготовление еды'],
            ['homework', 'Dars tayyorlashga yordam', 'Помощь с уроками'],
            ['early-development', 'Erta rivojlantirish', 'Раннее развитие'],
            ['first-aid', 'Birinchi yordam', 'Первая помощь'],
            ['special-needs', 'Alohida ehtiyojli bolalar', 'Дети с особыми потребностями'],
            ['housekeeping', 'Uy yumushlari', 'Помощь по дому'],
            ['walks', 'Sayr va to\'garaklarga olib borish', 'Прогулки и сопровождение на кружки'],
            ['english', 'Ingliz tili', 'Английский язык'],
            ['music', 'Musiqa', 'Музыка'],
        ];

        foreach ($skills as [$slug, $uz, $ru]) {
            Skill::firstOrCreate(['slug' => $slug], ['name_uz' => $uz, 'name_ru' => $ru]);
        }
    }
}
