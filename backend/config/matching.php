<?php

return [
    // Saqlangan natijalar shuncha soat yangi hisoblanadi.
    'fresh_hours' => (int) env('MATCHING_FRESH_HOURS', 6),

    // Rang bo'yicha saralashga beriladigan nomzodlar soni (AI ga ketadigan).
    'candidate_limit' => 30,

    // SQL bosqichida olinadigan maksimal havza.
    'pool_limit' => 300,

    // Natijada nechta mos tushadi.
    'top' => 5,

    // Narx byudjetdan shuncha foizga oshsa ham nomzod hisoblanadi.
    'price_tolerance' => 1.2,
];
