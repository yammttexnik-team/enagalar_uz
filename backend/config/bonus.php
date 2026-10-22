<?php

return [
    /*
    | Ichki bonus kreditlari (so'm hisobida, naqd pul EMAS). Faqat platforma xizmatlari uchun.
    */

    // Topilgan bonus necha oydan keyin kuyadi.
    'expiry_months' => (int) env('BONUS_EXPIRY_MONTHS', 9),

    // Kampaniyasiz (yoki kampaniya qiymati bo'lmasa) standart miqdorlar, so'm.
    'survey' => (int) env('BONUS_SURVEY', 50000),
    'listing' => (int) env('BONUS_LISTING', 30000),
    'referral' => (int) env('BONUS_REFERRAL', 15000),

    // Bitta foydalanuvchi taklif qilib olishi mumkin bo'lgan referal bonuslari soni (0 = cheksiz).
    'referral_max_rewards' => (int) env('BONUS_REFERRAL_MAX', 5),

    // Bitta foydalanuvchi hayoti davomida yig'a oladigan jami bonus (survey/listing/referral), so'm. 0 = cheklovsiz.
    'lifetime_cap' => (int) env('BONUS_LIFETIME_CAP', 200000),

    // E'lon shuncha kundan oldin o'chirilsa, listing bonusi bekor qilinadi.
    'listing_revoke_days' => 14,

    // So'rovnoma: shundan tez to'ldirilsa rad etiladi; matnli javob shundan qisqa bo'lsa "past sifat".
    'survey_min_seconds' => (int) env('BONUS_SURVEY_MIN_SECONDS', 60),
    // Shundan tez (lekin rad etilmagan) to'ldirilsa — tekshiruvga (flagged), bonus ushlab turiladi.
    'survey_flag_seconds' => (int) env('BONUS_SURVEY_FLAG_SECONDS', 240),
    'survey_min_text_chars' => 3,
];
