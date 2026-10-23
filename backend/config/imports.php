<?php

return [
    /*
    | Ochiq Telegram kanallardan e'lon olish (ImportedListing).
    */

    // false bo'lsa, yangi import qilingan e'lon avval admin tasdig'iga tushadi (birinchi hafta shunday).
    'auto_publish' => (bool) env('IMPORTS_AUTO_PUBLISH', false),

    // Bitta kanal uchun bir ishga tushishda ko'rib chiqiladigan postlar soni (t.me/s sahifalari).
    'max_posts_per_run' => (int) env('IMPORTS_MAX_POSTS', 60),

    // Postning yoshi: bundan eski postlar olinmaydi (kun).
    'max_post_age_days' => (int) env('IMPORTS_MAX_POST_AGE_DAYS', 7),

    // E'lon egasi tasdiqlaganda sovg'a: shuncha kun TOP (Promotable::top_until).
    'claim_top_days' => (int) env('IMPORTS_CLAIM_TOP_DAYS', 7),
];
