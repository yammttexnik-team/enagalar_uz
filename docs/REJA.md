# enagalar.uz — loyiha rejasi

> **Versiya 0.2** (2026-10-22).
> - Frontend Nuxt (Vue) ga o'zgardi.
> - Jamoa: 1 dasturchi va Claude.
> - Yangi qonun (ZRU-1125) hisobga olindi.
> - Qarorlar: server Contabo, kirish faqat Telegram bot orqali, birdan barcha viloyatlarda ishga tushirish, faqat "enaga" kategoriyasi, admin panel Filament'da, dispanser nishoni qo'shildi.
> - Pasport orqali "Tasdiqlangan" nishoni va moslashuvchan nishonlar tizimi qo'shildi.
>
> Batafsil bozor tahlili: [BOZOR_TAHLILI.md](BOZOR_TAHLILI.md)

## 1. Loyiha g'oyasi

**enagalar.uz** — ota-onalar bilan enagalarni bevosita bog'laydigan platforma. Ota-ona tekshirilgan enagani tez topadi, enaga ish topadi, ikkalasi platforma ichida xavfsiz yozishadi, AI yordamchi har bir qadamda yordam beradi.

**Asosiy farqimiz:**

1. **Telegramga o'xshash ichki chat.** Raqobatchilarning hech birida yo'q.
2. **Claude API asosidagi AI yordamchi.** U anketa yozadi, oddiy gap bilan qidiradi, mos nomzodlarni tanlaydi va e'lonlarni moderatsiya qiladi.
3. **Boshida obunasiz va bepul.** bolambilan.uz oyiga 25–35 ming so'm oladi, biz esa avval bozorni egallaymiz.
4. **Ishonch tizimi.** Pasport orqali "Tasdiqlangan" nishoni, admin boshqaradigan qo'shimcha nishonlar, sharhlar va shikoyatlar.
5. **Ota-ona vakansiyasi bilan enaga anketasi teng huquqli.** Otklik (javob berish) tizimi bor.
6. **Butun O'zbekiston.** Viloyatlarda bu xizmat amalda yo'q.
7. **Sayt, Telegram bot, Telegram Mini App, keyinchalik mobil ilova.** Hammasi bitta API ustida ishlaydi.

## 2. Bozor (qisqa)

- Eng katta Telegram kanallarda 80 mingdan ortiq obunachi bor: @enagalar 34,9 ming, @moya_nyanyauz 25,4 ming, @enaga_samarqand 21 ming. Bu kanallarda tekshiruv yo'q.
- **Asosiy raqobatchi bolambilan.uz.** Kontakt obuna bilan ochiladi, ichki chat yo'q.
- **homecare.uz** e'lonlar doskasi: pullik e'lon bor, tekshiruv yo'q. **enagam.uz** hali ishga tushmagan.
- **Narxlar:**
  - kunlik: 100–250 ming so'm;
  - oylik (Toshkent): 3,5–7 mln so'm;
  - yashab turish bilan: 11 mln so'mgacha.

## 3. Rollar

| Rol | Imkoniyatlari |
|---|---|
| **Mehmon** | E'lon va anketalarni ko'radi. Kontakt va chat yopiq. |
| **Ota-ona** | Profil, bolalar (yoshi, alohida ehtiyoj), **vakansiya e'loni**, qidiruv va filtr, sevimlilar, chat, sharh, shikoyat, AI yordamchi. |
| **Enaga** | Anketa (foto, tajriba, ko'nikma, til, tuman, jadval, narx), hujjatlar, nishon olish uchun so'rov, vakansiyaga otklik, "bo'sh/band" holati, chat, statistika, AI yordamchi. |
| **Uy bog'chasi va agentlik** (3-bosqich) | Tashkilot profili, bir nechta xodim. |
| **Moderator** | E'lon va anketa moderatsiyasi, pasport va hujjat tekshiruvi, nishon berish, shikoyatlar. |
| **Admin (to'liq)** | Hammasi: foydalanuvchilar, rollar va ruxsatlar, nishon turlari, ma'lumotnomalar, tariflar va to'lovlar, statistika, AI sozlamalari va xarajati, Telegram, ommaviy bildirishnomalar, audit log. |

Bitta foydalanuvchi ham ota-ona, ham enaga bo'lishi mumkin. Rol ro'yxatdan o'tganda tanlanadi, keyin ikkinchisini qo'shish mumkin.

## 4. Funksiyalar

### 4.1 E'lonlar va anketalar

- **Vakansiya:** bolalar soni va yoshi, viloyat va tuman, ish formati (soatbay, kunlik, doimiy, yashab turish bilan), jadval, til, talablar, maosh (soat, kun yoki oy uchun), boshlash sanasi, "shoshilinch" belgisi.
- **Enaga anketasi:** foto, tajriba yillari, qaysi yoshdagi bolalar bilan ishlagan, ko'nikmalar, tillar, jadval, narx, "bepul sinov kuni" taklifi.
- **Filtrlar:** viloyat va tuman, narx, tajriba, bolaning yoshi, til, ish formati, yashab turish, nishonlar, reyting, "hozir bo'sh".
- **Muddat:** e'lon 30 kundan keyin tugaydi va uni yangilash mumkin. "Topildi" tugmasi bor. Bu "o'lik e'lon" muammosini hal qiladi.
- **Otklik:** bir tugma bilan javob beriladi, ota-ona javoblar ro'yxatini ko'radi, javob chatga aylanadi.
- **Qo'shimcha:** sevimlilar, saqlangan qidiruv va unga mos yangi e'lon chiqsa bildirishnoma, ko'rishlar statistikasi.
- **Manzil:** faqat tuman va mo'ljal ko'rsatiladi, aniq manzil hech qachon ko'rsatilmaydi.

### 4.2 Telegramga o'xshash chat

- **v1:** 1:1 suhbat, matn, rasm, ✓/✓✓ (yetkazildi va o'qildi), "yozmoqda…", onlayn yoki oxirgi faollik, o'qilmagan xabarlar soni, push va Telegram bot orqali bildirishnoma, bloklash va shikoyat.
- **v2:** ovozli xabar, reply, tahrirlash va o'chirish, reaksiya, fayl, UZ↔RU tarjima (AI), "suhbatga taklif" va "sinov kuni" shablonlari.
- **Xavfsizlik:**
  - Birinchi xabarlarda telefon raqam va havolalar ogohlantirish bilan ko'rsatiladi, chunki firibgarlik ko'pincha boshqa joyga olib chiqishdan boshlanadi.
  - Spam limiti qo'yiladi.
  - Moderator faqat shikoyat qilingan suhbatni ko'radi, bu audit log'ga yoziladi.
- **Texnologiyasi:** Laravel Reverb (WebSocket) + Redis, frontendda Laravel Echo.

### 4.3 AI yordamchi (Claude API)

| Funksiya | Kim uchun | Bosqich | Model |
|---|---|---|---|
| Anketa va e'lon yozuvchi (UZ/RU) | Enaga, ota-ona | 1 | `claude-sonnet-5-5` |
| Avto-moderatsiya (spam, firibgarlik, matndagi kontakt) | Moderator | 1 | `claude-haiku-5-5` |
| Aqlli qidiruv ("Chilonzorda 3 yoshli bolaga, rus tilida, 5 mln gacha") | Ota-ona | 2 | `claude-haiku-5-5` |
| Moslashtirish: eng mos 5 nomzod, "nega mos" izohi bilan | Ikkalasi | 2 | `claude-sonnet-5-5` |
| Chat tarjimoni (lotin, kirill, rus) | Hammasi | 2 | `claude-haiku-5-5` |
| Suhbat savollari, sinov kuni qo'llanmasi, kelishuv shabloni | Ota-ona | 2 | `claude-sonnet-5-5` |
| Sharhlar xulosasi | Ota-ona | 2 | `claude-haiku-5-5` |
| Ota-ona maslahatchisi (tibbiy ogohlantirish bilan) | Ota-ona | 3 | `claude-sonnet-5-5` |
| Admin tahlilchisi ("qaysi tumanda talab ko'p?") | Admin | 3 | `claude-sonnet-5-5` |

**AI qoidalari:**
- Foydalanuvchi va funksiya bo'yicha kunlik limit bor. Har bir so'rov `ai_requests` jadvaliga yoziladi (tokenlar, narx).
- Claude'ga pasport, telefon va aniq manzil yuborilmaydi.
- Promptlar va modellar admin paneldan o'zgartiriladi.
- So'rovlar Queue orqali fonda bajariladi. Yozuvchi uchun streaming ishlatiladi.

### 4.4 Ishonch: tekshiruv va nishonlar

**Nishonlar tizimi moslashuvchan bo'ladi.** Admin yangi nishon turini paneldan qo'shadi (nomi, ikonka, rangi, talablari, amal qilish muddati), kod yozish shart emas.

Boshlang'ich nishonlar:

| Nishon | Qanday beriladi | Bosqich |
|---|---|---|
| 📱 Telefon tasdiqlangan | Telegram bot orqali raqam yuborilganda, avtomatik | 1 |
| ✅ **Tasdiqlangan foydalanuvchi** | Foydalanuvchi pasport ma'lumotlarini kiritadi (seriya va raqam, JShShIR, F.I.Sh., tug'ilgan sana), pasport rasmini yuklaydi, moderator tekshiradi | 1 |
| 🩺 Tibbiy ma'lumotnoma | Hujjat yuklanadi va tekshiriladi, muddati bor (masalan, 1 yil) | 1 |
| 🛡 Dispanser hisobida turmaydi | Narkologik va psixonevrologik dispanserdan ma'lumotnoma yuklanadi, moderator tekshiradi, 1 yil amal qiladi | 1 |
| 🎓 Sertifikat yoki ma'lumot (pedagog, hamshira, birinchi yordam) | Hujjat yuklanadi va tekshiriladi | 1 |
| ⭐ Tavsiya qilingan | 3+ ijobiy sharh va 4,5+ reyting, avtomatik | 2 |
| ⚡ Tez javob beradi | O'rtacha javob vaqti 1 soatdan kam, avtomatik | 2 |
| 🛡 Sudlanmaganlik ma'lumotnomasi | Hujjat yuklanadi va tekshiriladi | 2 |
| 🆔 MyID orqali tasdiqlangan | MyID integratsiyasi | 2–3 |
| *Boshqa nishonlar* | Siz keyin aytasiz, admin paneldan qo'shiladi | — |

**Pasport ma'lumotlari xavfsizligi:**
- Maydonlar bazada **shifrlangan holda** saqlanadi (Laravel encrypted cast).
- Pasport rasmi alohida yopiq xotirada turadi. Tekshiruvdan keyin u o'chiriladi va faqat natija qoladi: kim, qachon tekshirgan, holat.
- Ochiq profilda pasport ma'lumoti hech qachon ko'rinmaydi, faqat nishon ko'rinadi.
- Ro'yxatdan o'tishda alohida rozilik olinadi: maqsad, ma'lumotlar ro'yxati, muddat.
- Tibbiy hujjat uchun alohida yozma (elektron) rozilik olinadi.
- Selfi va yuz rasmi biometrik ma'lumot hisoblanadi. Uni O'zbekistondan tashqarida saqlamaymiz (4.6-bo'lim).

**Boshqa ishonch elementlari:**
- Sharhni faqat haqiqiy chat bo'lgan foydalanuvchi qoldira oladi.
- Shikoyat, bloklash, qora ro'yxat va takroriy akkauntlarni aniqlash bor.
- Ota-onalar uchun xavfsizlik qo'llanmasi: sinov kuni o'tkazing, oldindan pul to'lamang.

### 4.5 Monetizatsiya

- **1-bosqich (birinchi 6 oy): hammasi bepul, obuna yo'q.** Maqsad: 3 000 enaga va 5 000 ota-ona.
- **2-bosqich: ixtiyoriy pullik xizmatlar.**
  - E'lonni TOPga ko'tarish va "Shoshilinch" belgisi.
  - Enaga uchun PRO anketa: ro'yxatda yuqorida, kengaytirilgan statistika.
  - Tez tekshiruv (24 soat ichida). Yoopies'dagi kabi kichik to'lov sifatsiz anketalarni ham filtrlaydi.
- **3-bosqich:**
  - ota-ona obunasi (cheksiz chat, kengaytirilgan AI);
  - uy bog'chalari va agentliklar uchun biznes tarif;
  - "konsyerj" xizmati: AI va moderator 3–5 nomzod tanlab beradi;
  - reklama.
- **To'lov:** Payme, Click, Uzum. Click Mini App ham bo'lishi mumkin (bolambilan shunday qilgan).

### 4.6 Admin panel (to'liq)

- **Dashboard:**
  - ro'yxatdan o'tishlar, faol e'lonlar, chatlar;
  - voronka: e'lon → otklik → chat → "topildi";
  - viloyat va tumanlar bo'yicha talab va taklif;
  - AI xarajati.
- **Foydalanuvchilar:** qidirish, bloklash, rol berish, kirish tarixi.
- **Moderatsiya navbati:** e'lon, anketa, pasport va hujjatlar, AI tavsiyasi bilan.
- **Nishonlar:** turlarini yaratish va tahrirlash, berish va qaytarib olish, muddatlar.
- **Shikoyatlar:** shikoyat qilingan chatlarni ko'rish (audit log bilan).
- **Ma'lumotnomalar:** viloyatlar, tumanlar, ko'nikmalar, tillar, ish formatlari.
- **To'lov va kontent:**
  - tariflar, to'lovlar, promo-kodlar;
  - sahifalar, blog va maslahatlar (SEO), FAQ;
  - ommaviy bildirishnoma, Telegram kanalga avto-post shablonlari.
- **AI:** promptlar, limitlar, modellar, xarajat.
- **Tizim:** rollar va ruxsatlar, audit log, sozlamalar.

## 5. Texnologiya

### 5.1 Tanlov: **Laravel 13 (API) + Nuxt 4 (Vue 3)**

Frontend Nuxt bo'lishiga qaror qildingiz, bu to'g'ri tanlov:
- **SEO:** SSR tufayli "enaga Chilonzor" kabi sahifalar Google'da yaxshi chiqadi.
- **Bitta API hamma uchun:** Telegram Mini App ham, keyinchalik mobil ilova ham shu API'ni ishlatadi.

Bitta dasturchi bo'lgani uchun **admin panelni alohida yozmaymiz.** Uni Laravel ichida **Filament** bilan qilamiz. Bu kamida 4–6 hafta tejaydi.

```
  Brauzer, Telegram Mini App ──▶ Nuxt 4 (SSR, Nuxt UI, Tailwind, i18n, Pinia)
                                          │ REST API (Sanctum) + WebSocket (Echo)
                                          ▼
                       Laravel 13 ── Filament (admin /admin)
                         │  Reverb (chat) · Queue/Horizon · Telegram bot (webhook)
                         │  Claude API servisi · Scout
           ┌─────────────┼──────────────┬──────────────┐
       PostgreSQL      Redis        Meilisearch     S3 (MinIO / R2)
```

| Qism | Texnologiya |
|---|---|
| Backend | Laravel 13, PHP 8.3+, Sanctum, Reverb, Horizon, Scout + Meilisearch, Spatie Permission, Filament |
| Frontend | Nuxt 4, Vue 3, TypeScript, Nuxt UI (Tailwind), @nuxtjs/i18n (UZ lotin, UZ kirill, RU), Pinia, Laravel Echo |
| Ma'lumotlar bazasi | PostgreSQL 16 |
| Infratuzilma | Docker Compose, GitHub Actions (CI/CD), Sentry, kunlik backup |
| Server | **Contabo** (Yevropa Ittifoqi, Germaniyadagi data-markaz tanlanishi kerak, chunki u "adekvat" ro'yxatda). Pasport rasmlari shu serverdagi yopiq papkada turadi, tekshiruvdan keyin o'chiriladi. Biometrik ma'lumot saqlamaymiz. |
| Tashqi xizmatlar | Claude API, Telegram Bot API (kirish shu orqali, SMS kerak emas), Payme, Click, Uzum, keyinchalik MyID |

### 5.2 Repozitoriya tuzilishi

```
enagalar_uz/
├── backend/          Laravel 13 (API + Filament admin + Reverb)
├── frontend/         Nuxt 4
├── docs/             Reja, bozor tahlili, API hujjatlari
├── docker-compose.yml   PostgreSQL, Redis, Meilisearch, Mailpit
└── CLAUDE.md
```

### 5.3 Ma'lumotlar bazasi

```
users              id, name, phone, telegram_id, email, locale, status, last_seen_at
roles/permissions  (Spatie) parent, nanny, moderator, admin
regions, districts id, name_uz, name_uz_cyrl, name_ru, slug
parent_profiles    user_id, region_id, district_id, about
children           parent_profile_id, birth_year, gender, special_needs
nanny_profiles     user_id, region_id, district_id, bio, experience_years,
                   price_amount, price_unit[hour|day|month], work_formats[], languages[],
                   age_groups[], live_in, free_trial_day, is_available, status,
                   rating_avg, reviews_count, views_count
skills, nanny_skill
vacancies          parent_profile_id, title, description, region_id, district_id,
                   work_format, schedule, salary_min, salary_max, salary_unit,
                   children_count, children_ages[], languages[], is_urgent,
                   status[draft|pending|active|closed|found|expired], expires_at
applications       vacancy_id, nanny_profile_id, message, status
identity_verifications user_id, passport_series, passport_number, pinfl, full_name,
                   birth_date (shifrlangan), document_path, status, reviewed_by, reviewed_at
badges             slug, name_uz, name_ru, icon, color, description, auto_rule, validity_days
user_badges        user_id, badge_id, granted_by, granted_at, expires_at, source
documents          user_id, type, path, status, reviewed_by
conversations      id, vacancy_id?, last_message_at
conversation_participants conversation_id, user_id, last_read_message_id, muted, blocked
messages           conversation_id, sender_id, type[text|image|voice|file|system],
                   body, attachment_path, reply_to_id, edited_at, deleted_at
reviews            author_id, target_user_id, conversation_id, rating, text, status
reports            reporter_id, reportable_type, reportable_id, reason, status
favorites, saved_searches, consents, ai_requests, audit_logs, settings
plans, subscriptions, payments, promotions     (2-bosqich)
```

## 6. Bosqichlar (1 dasturchi va Claude)

Claude bilan ishlaganda kod yozish tezlashadi. Lekin dizayn, test, moderatsiya va marketing vaqt oladi, shuning uchun muddatlar realistik qo'yildi.

### 0-bosqich: poydevor (1–2 hafta) — **boshlandi**

- [x] Bozor tahlili
- [x] Laravel va Nuxt karkasi
- [ ] Docker Compose, CI (lint va testlar)
- [ ] Asosiy migratsiyalar va modellar, viloyat va tumanlar seed'i
- [x] Auth: Telegram bot orqali kirish (raqam bot orqali yuboriladi), rol tanlash API
- [x] Filament admin panel: pasport va hujjat tekshiruvi, nishonlar, foydalanuvchilar, anketa va vakansiya moderatsiyasi
- [x] Barcha viloyatlar, tuman va shaharlar (UZ lotin, UZ kirill, RU)
- [ ] Brend, logotip va dizayn tizimi (dizayn agentlari ishlamoqda)
- [ ] Dizayn: 10–12 asosiy ekran (Nuxt UI asosida, mobile-first)
- [ ] Ma'lumotlar bazasini pd.gov.uz da ro'yxatdan o'tkazish, maxfiylik siyosati va oferta

### 1-bosqich: MVP (6–8 hafta)

- Hafta 1–2: enaga anketasi, ota-ona profili va vakansiya, Filament'da moderatsiya.
- Hafta 3: qidiruv va filtrlar (Meilisearch), SEO sahifalar (tuman va kategoriya).
- Hafta 4: otklik, sevimlilar, pasport orqali tekshiruv va nishonlar tizimi.
- Hafta 5–6: **chat v1** (Reverb), push va Telegram bot bildirishnomalari.
- Hafta 7: **AI v1** (yozuvchi va avto-moderatsiya), Telegram kanalga avto-post.
- Hafta 8: test, xatolarni tuzatish, beta (50–100 foydalanuvchi), keyin **barcha viloyatlarda birdan ishga tushirish**.

### 2-bosqich: o'sish (6–8 hafta)

- Chat v2, AI v2 (qidiruv, moslashtirish, tarjima).
- Sharh va reyting, avtomatik nishonlar, saqlangan qidiruv.
- Telegram Mini App.
- To'lovlar: TOP e'lon, PRO anketa, tez tekshiruv.
- O'zbek kirill alifbosi.

### 3-bosqich: kengayish

- Mobil ilova (Capacitor yoki React Native/Flutter, o'sha API bilan).
- MyID, obunalar, uy bog'chalari va agentliklar, konsyerj xizmati.
- Video tanishtiruv va video qo'ng'iroq, "Enagalar akademiyasi" (kurslar).

### 6.1 KPI

- Faol enagalar va faol vakansiyalar soni, viloyatlar bo'yicha.
- Vakansiyadan birinchi otklikkacha o'tgan vaqt (maqsad: < 2 soat).
- "Topildi" bilan yopilgan vakansiyalar ulushi.
- "Tasdiqlangan" nishonli enagalar ulushi.
- 7 va 30 kunlik retention.

### 6.2 Marketing

- **Telegram:** o'z kanallarimiz (UZ va RU) va avto-post, katta kanallarda reklama (@enagalar, @moya_nyanyauz, @enaga_samarqand).
- **Viloyatlar:** u yerda raqobat yo'q, Samarqand, Buxoro va Farg'ona uchun alohida kanal ochish.
- **Instagram va bloggerlar:** ota-onalar guruhlari, mommy-bloggerlar.
- **SEO:** har bir tuman uchun alohida sahifa, blog.
- **Enagalarni jalb qilish:** "AI anketangizni 1 daqiqada yozib beradi, bepul".

### 6.3 Xavflar

| Xavf | Chora |
|---|---|
| "Tovuq va tuxum" (enaga ham, ota-ona ham kam) | Avval enagalar: bepul, AI anketa, Telegram orqali jalb qilish |
| Bola xavfsizligi bilan bog'liq holat | Nishonlar, shikoyatga 24 soatda javob, qo'llanma, platforma o'zini "e'lon platformasi" deb ko'rsatadi |
| Huquqiy masalalar (ZRU-501, shaxsiy ma'lumotlar) | Bazani reestrga kiritish, rozilik, yurist maslahati |
| bolambilan yoki boshqa raqobatchi chat qo'shishi | Tezlik, UX, AI, viloyatlarni egallash |
| Bitta dasturchiga yuklama | Filament, Nuxt UI, tayyor paketlar, Claude, qat'iy MVP doirasi |
| AI xarajati | Limitlar, ko'p ishlatiladigan vazifalar uchun Haiku, kesh |

## 7. Ochiq savollar

[README](../README.md) va chatdagi savollarga qarang. Javoblar kelgach, reja yangilanadi.
