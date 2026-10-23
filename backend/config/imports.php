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

    // Bir ishga tushishda (imports:fetch) ko'pi bilan shuncha Claude chaqiruvi (AI xarajati chegarasi).
    'max_ai_per_run' => (int) env('IMPORTS_MAX_AI_PER_RUN', 80),

    // t.me so'rovlari orasidagi pauza (millisekund) — Telegram'ga og'irlik bermaslik uchun.
    'request_delay_ms' => (int) env('IMPORTS_REQUEST_DELAY_MS', 1000),

    'user_agent' => env('IMPORTS_USER_AGENT', 'Mozilla/5.0 (compatible; EnagalarBot/1.0; +https://enagalar.uz)'),

    // E'lon egasi tasdiqlaganda sovg'a: shuncha kun TOP (Promotable::top_until).
    'claim_top_days' => (int) env('IMPORTS_CLAIM_TOP_DAYS', 7),
];
