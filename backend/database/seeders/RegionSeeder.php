<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['tashkent-city', 'Toshkent shahri', 'Тошкент шаҳри', 'г. Ташкент'],
            ['tashkent', 'Toshkent viloyati', 'Тошкент вилояти', 'Ташкентская область'],
            ['andijan', 'Andijon viloyati', 'Андижон вилояти', 'Андижанская область'],
            ['bukhara', 'Buxoro viloyati', 'Бухоро вилояти', 'Бухарская область'],
            ['fergana', "Farg'ona viloyati", 'Фарғона вилояти', 'Ферганская область'],
            ['jizzakh', 'Jizzax viloyati', 'Жиззах вилояти', 'Джизакская область'],
            ['kashkadarya', 'Qashqadaryo viloyati', 'Қашқадарё вилояти', 'Кашкадарьинская область'],
            ['khorezm', 'Xorazm viloyati', 'Хоразм вилояти', 'Хорезмская область'],
            ['namangan', 'Namangan viloyati', 'Наманган вилояти', 'Наманганская область'],
            ['navoi', 'Navoiy viloyati', 'Навоий вилояти', 'Навоийская область'],
            ['samarkand', 'Samarqand viloyati', 'Самарқанд вилояти', 'Самаркандская область'],
            ['surkhandarya', 'Surxondaryo viloyati', 'Сурхондарё вилояти', 'Сурхандарьинская область'],
            ['syrdarya', 'Sirdaryo viloyati', 'Сирдарё вилояти', 'Сырдарьинская область'],
            ['karakalpakstan', "Qoraqalpog'iston Respublikasi", 'Қорақалпоғистон Республикаси', 'Республика Каракалпакстан'],
        ];

        foreach ($regions as $sort => [$slug, $uz, $cyrl, $ru]) {
            Region::updateOrCreate(['slug' => $slug], [
                'name_uz' => $uz, 'name_uz_cyrl' => $cyrl, 'name_ru' => $ru, 'sort' => $sort,
            ]);
        }

        // Boshlanishda faqat Toshkent shahri tumanlari; viloyatlar tumanlari keyin qo'shiladi.
        $tashkent = Region::where('slug', 'tashkent-city')->first();
        $districts = [
            ['bektemir', 'Bektemir', 'Бектемир', 'Бектемирский'],
            ['chilanzar', 'Chilonzor', 'Чилонзор', 'Чиланзарский'],
            ['mirabad', 'Mirobod', 'Миробод', 'Мирабадский'],
            ['mirzo-ulugbek', "Mirzo Ulug'bek", 'Мирзо Улуғбек', 'Мирзо-Улугбекский'],
            ['olmazor', 'Olmazor', 'Олмазор', 'Алмазарский'],
            ['sergeli', 'Sergeli', 'Сергели', 'Сергелийский'],
            ['shaykhantahur', 'Shayxontohur', 'Шайхонтоҳур', 'Шайхантахурский'],
            ['uchtepa', 'Uchtepa', 'Учтепа', 'Учтепинский'],
            ['yakkasaray', 'Yakkasaroy', 'Яккасарой', 'Яккасарайский'],
            ['yashnobod', 'Yashnobod', 'Яшнобод', 'Яшнабадский'],
            ['yangihayot', 'Yangihayot', 'Янгиҳаёт', 'Янгихаётский'],
            ['yunusabad', 'Yunusobod', 'Юнусобод', 'Юнусабадский'],
        ];

        foreach ($districts as $sort => [$slug, $uz, $cyrl, $ru]) {
            $tashkent->districts()->updateOrCreate(['slug' => $slug], [
                'name_uz' => $uz, 'name_uz_cyrl' => $cyrl, 'name_ru' => $ru, 'sort' => $sort,
            ]);
        }
    }
}
