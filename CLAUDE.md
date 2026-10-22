# CLAUDE.md

## Muloqot

- Foydalanuvchi xabarlarini ko'pincha **o'zbek tilida audio** orqali yuboradi. Matnga o'girilganda so'zlar buzilishi mumkin: turkcha yoki qozoqcha so'zlarga o'xshab qoladi (masalan, "bileydi" → "bo'ladi", "yolda" → "yo'lda", "Vue Framework Next" → Nuxt). Ma'noni o'zbek tili bo'yicha tushun. Tushunarsiz joy muhim bo'lsa, so'ra.
- Javoblarni **o'zbek tilida (lotin alifbosida)** yoz.

## Loyiha

- Reja: `docs/REJA.md`, bozor tahlili: `docs/BOZOR_TAHLILI.md`.
- `backend/`: Laravel 13 API (`/api/v1`). Admin panel keyinchalik Filament bilan qilinadi.
- `frontend/`: Nuxt 4, Nuxt UI, @nuxtjs/i18n (uz — asosiy, ru).
- Jamoa: 1 dasturchi va Claude. Oddiy, tayyor yechimlarni afzal ko'r.
- **Tokenlarni tejash:** oddiy va o'rtacha ishlarni (CRUD, API, testlar, tarjima, qidiruv) subagentlarga `model: sonnet` yoki `haiku` bilan ber, natijani asosiy sessiya tekshiradi (testlar, diff ko'rib chiqish). Qiyin arxitektura va dizayn qarorlarini asosiy model qiladi.
- **Ikki tomonlama e'lonlar:** ota-ona vakansiya joylaydi ("menga shunday enaga kerak"), enaga anketa joylaydi. Ikkala tomon bir-birini qidiradi, AI matching ikkala yo'nalishda ishlaydi.

## Qoidalar

- Pasport ma'lumotlari faqat `IdentityVerification` modelida, `encrypted` cast bilan saqlanadi. Pasport rasmi tekshiruvdan keyin o'chiriladi. Ochiq API'da hech qachon qaytarilmaydi.
- Biometrik ma'lumot (selfi, yuz) chet eldagi serverda saqlanmaydi (ZRU-1125).
- Nishonlar `badges` jadvalida turadi. Yangi nishon qo'shish kod emas, ma'lumot sifatida qilinadi.
- Backend o'zgarishidan keyin: `php artisan test` va `./vendor/bin/pint --test`. Frontend o'zgarishidan keyin: `pnpm build`.
