<?php

// Minimal o'zbekcha tekshiruv xabarlari (qolganlari standart inglizcha matnga tushadi).
return [
    'required' => ':attribute maydoni to\'ldirilishi shart.',
    'string' => ':attribute matn bo\'lishi kerak.',
    'integer' => ':attribute butun son bo\'lishi kerak.',
    'boolean' => ':attribute ha/yo\'q qiymatida bo\'lishi kerak.',
    'array' => ':attribute ro\'yxat bo\'lishi kerak.',
    'exists' => 'Tanlangan :attribute noto\'g\'ri.',
    'in' => 'Tanlangan :attribute noto\'g\'ri.',
    'enum' => 'Tanlangan :attribute noto\'g\'ri.',
    'min' => [
        'numeric' => ':attribute kamida :min bo\'lishi kerak.',
        'string' => ':attribute kamida :min belgidan iborat bo\'lishi kerak.',
        'array' => ':attribute kamida :min ta element bo\'lishi kerak.',
    ],
    'max' => [
        'numeric' => ':attribute :max dan oshmasligi kerak.',
        'string' => ':attribute :max belgidan oshmasligi kerak.',
        'array' => ':attribute :max tadan ko\'p element bo\'lmasligi kerak.',
    ],
    'between' => [
        'numeric' => ':attribute :min va :max orasida bo\'lishi kerak.',
    ],
    'gte' => [
        'numeric' => ':attribute :value dan kam bo\'lmasligi kerak.',
    ],
    'attributes' => [
        'region_id' => 'viloyat',
        'district_id' => 'tuman',
        'bio' => 'o\'zingiz haqingizda',
        'experience_years' => 'tajriba',
        'price_amount' => 'narx',
        'price_unit' => 'narx birligi',
        'work_formats' => 'ish formati',
        'languages' => 'tillar',
        'title' => 'sarlavha',
        'description' => 'tavsif',
        'work_format' => 'ish formati',
        'salary_min' => 'minimal maosh',
        'salary_max' => 'maksimal maosh',
        'children_count' => 'bolalar soni',
        'children_ages' => 'bolalar yoshi',
    ],
];
