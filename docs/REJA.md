# enagalar.uz — loyiha rejasi

> Versiya: 0.1 (2026-10-22). Bu birinchi qoralama. Raqobatchilar tahlili ochiq manbalar asosida tuzilgan, bozor raqamlarini so'rovnoma va intervyular bilan tasdiqlash kerak (6-bo'lim).

## 1. Loyiha g'oyasi

**enagalar.uz** — ota-onalar bilan enagalarni bevosita bog'laydigan platforma. Ota-ona tekshirilgan enagani tez topadi, enaga ish topadi, ikkalasi platforma ichida xavfsiz yozishadi. AI yordamchi har bir qadamda yordam beradi.

**Asosiy farqimiz (raqobatchilarda yo'q narsalar):**

1. Telegramga o'xshash ichki chat: ovozli xabar, rasm, "o'qildi" belgisi, onlayn holat.
2. Claude API asosidagi AI yordamchi: anketa yozish, aqlli qidiruv, moslashtirish, moderatsiya.
3. Boshida obunasiz va bepul. Bozorni egallash birinchi, monetizatsiya keyin.
4. Ishonch tizimi: hujjat tekshiruvi, "tasdiqlangan" belgilari, sharhlar, shikoyat qilish.
5. Bir joyning o'zida veb-sayt, Telegram bot va Telegram Mini App.

---

## 2. Raqobatchilar tahlili

### 2.1 Ixtisoslashgan saytlar

| Raqobatchi | Model | Kuchli tomoni | Zaif tomoni |
|---|---|---|---|
| **bolambilan.uz** (+ @bolambilan_bot, Mini App) | Enagalar bepul anketa joylaydi. Ota-ona kontaktni **obuna orqali** ochadi, yangi ota-onaga 48 soatda 2 ta kontakt bepul. Komissiya yo'q. | Moderatsiya, pasportni qo'lda tekshirish, tibbiy ma'lumotnoma va sertifikat yuklash, sharh va reyting, filtrlar (tuman, tajriba, jadval, til, ko'nikma), UZ/RU. | Ichki chat yo'q, aloqa telefon yoki Telegram orqali. Saytda tariflar ochiq ko'rsatilmagan. Ota-ona e'loni (vakansiya) asosiy oqim emas. Statistika ko'rsatilmaydi. |
| **homecare.uz** | E'lonlar doskasi. Oilaga 3 kunda 1 ta vakansiya bepul, qo'shimchasi 20 000 so'm. Uy bog'chasi e'loni 30 000 so'm (birinchisi 15 000). Obuna yo'q. | Faqat enaga emas: uy bekasi, oshpaz, sidelka, er-xotin. 12 tuman bo'yicha filtr. Telegram bot va kanal bilan bog'langan. | Shaxs, hujjat va tajriba **tekshirilmaydi** (saytning o'zi shunday deydi). Ichki chat yo'q. Sharh yoki reyting tizimi ko'rinmaydi. |
| **enagam.uz** | Domen hozir "Parvona — O'zbekistonda ishonchli enaga va parvarish platformasi" shiorini ko'rsatadi, boshqa kontent yo'q. | — | Hali ishga tushmagan yoki ishlab chiqilmoqda. **Kuzatib borish kerak.** |
| **Agentliklar** (masalan, "Planeta Serdtse") | Agentlik orqali tanlash, xizmat haqi olinadi. | Shaxsiy tanlov, hujjatlar va tavsiyalar. | Qimmat, sekin, onlayn tajriba yo'q. |

### 2.2 Umumiy e'lon saytlari

- **OLX.uz**, **hh.uz**, **ishkop.uz**, **salexy.uz**, **uzjobs.uz**: "enaga kerak" va "няня" bo'yicha yuzlab e'lonlar bor (ishkop.uz da faqat Toshkent bo'yicha 164 ta vakansiya).
- **Kamchiligi:** enagaga xos filtrlar yo'q (bolaning yoshi, til, tibbiy ma'lumotnoma), tekshiruv yo'q, ishonch past.

### 2.3 Telegram kanallar (eng katta raqobatchi)

| Kanal | Obunachi | Xususiyati |
|---|---|---|
| @moya_nyanyauz "Няни и домашний персонал города Ташкента" | ~25 400 | Kuniga o'nlab post, ko'pi rus tilida. Vakansiya ham, rezyume ham. Bitta post ~1000+ ko'rish yig'adi. O'zbek tilidagi postlar alohida kanalga yo'naltiriladi. |
| @nyanidomashniypersonal "Няни, сиделки, домработницы Ташкента" | ~7 300 | Ish beruvchilar uchun reklama kanali, heshteglar (#няня, #сиделка …). Post admin orqali joylanadi. |
| @jobuztashkent | — | Aralash: vakansiya va rezyume. |
| @OLX_Ish, @BOR_ISH va boshqa umumiy ish kanallari | — | Enaga e'lonlari umumiy oqim ichida yo'qolib ketadi. |

**Telegram bo'yicha xulosa:**

- **Talab haqiqatan bor va katta.** Bitta kanalda 25 ming obunachi va kuniga o'nlab e'lon.
- **Muammolar:** filtr ham, qidiruv ham yo'q. Eski e'lonlar o'chmaydi, tekshiruv va sharh yo'q, firibgarlik bor. Post joylash admin orqali qo'lda, ko'pincha pullik. Tillar aralashib ketgan.
- **Bizning yo'limiz:** Telegramga qarshi chiqish emas, uni **kanal sifatida ishlatish**:
  - O'zimizning Telegram kanallarimizga (UZ va RU alohida) yangi e'lonlar avtomatik joylanadi, saytga havola bilan.
  - Telegram bot orqali ro'yxatdan o'tish va bildirishnoma olish.
  - Telegram Mini App: platformaning o'zi Telegram ichida ochiladi.
  - Login Telegram orqali bo'ladi (SMS ga pul ketmaydi).

### 2.4 Bozor narxlari (ochiq e'lonlardan, 2026-oktabr)

| Turi | Narx |
|---|---|
| Kunlik enaga yoki yordamchi | 170 000–250 000 so'm/kun |
| Oylik enaga | 3,5–7 mln so'm/oy |
| Uy bekasi va oshpaz | 5–6 mln so'm/oy |
| Ota-onalar talabidagi o'rtacha diapazon | 4–6 mln so'm/oy |

### 2.5 Bo'sh joy (imkoniyat)

Hech bir raqobatchida bularning **hammasi birga** yo'q:

1. ichki chat;
2. ota-ona e'loni va enaga anketasining ikkalasi teng huquqli;
3. tekshiruv va "tasdiqlangan" belgisi;
4. AI yordamchi;
5. bepul start;
6. o'zbek tiliga birinchi darajali e'tibor.

Bu ayniqsa viloyatlarda va o'zbek tilida so'zlashuvchi auditoriyada sezilarli.

---

## 3. Rollar

| Rol | Imkoniyatlari |
|---|---|
| **Mehmon** | E'lon va anketalarni ko'radi. Kontakt yashirin. Ro'yxatdan o'tishga taklif qilinadi. |
| **Ota-ona** | Profil, bolalar ma'lumoti (yoshi, alohida ehtiyoji), **vakansiya e'loni**, enaga qidirish va filtrlash, sevimlilar, chat, sharh qoldirish, shikoyat, AI yordamchi. |
| **Enaga** | Anketa (foto, tajriba, ko'nikmalar, tillar, tuman, jadval, narx), hujjat yuklash (pasport, tibbiy ma'lumotnoma, sertifikat, tavsiyanoma), vakansiyalarga **otklik** berish, bandlik holati (bo'sh/band), chat, AI yordamchi. |
| **Agentlik va uy bog'chasi** (2-bosqich) | Bir nechta enagani boshqaradi, tashkilot profili. |
| **Moderator** | E'lon va anketalarni tasdiqlash yoki rad etish, hujjat tekshiruvi, shikoyatlar, chatdagi shikoyat qilingan xabarlar. |
| **Admin (to'liq)** | Hammasi: foydalanuvchilar (bloklash, rol berish), kontent, tariflar, to'lovlar, statistika va dashboard, AI sozlamalari va xarajati, bildirishnoma yuborish, Telegram kanalni boshqarish, tizim sozlamalari, audit log. |

---

## 4. Funksiyalar

### 4.1 E'lonlar va anketalar

- **Ota-ona e'loni (vakansiya):** bolalar soni va yoshi, tuman yoki shahar, ish formati (soatbay, kunlik, doimiy, yashab turish bilan), til, talablar, maosh diapazoni, boshlash sanasi.
- **Enaga anketasi:** foto, video tanishtiruv (2-bosqich), tajriba yillari, qaysi yoshdagi bolalar bilan ishlagan, ko'nikmalar (ovqat tayyorlash, dars tayyorlash, birinchi yordam, chaqaloq parvarishi), tillar, jadval, narx.
- **Filtrlar:** viloyat va tuman, narx, tajriba, bolaning yoshi, til, ish formati, "tasdiqlangan", reyting, "hozir bo'sh".
- **Holat:** e'lon muddati tugaydi (masalan, 30 kun), "topildi" tugmasi bor. Telegram kanallardagi "o'lik e'lon" muammosini shu hal qiladi.
- **Otklik (javob berish):** enaga vakansiyaga bir tugma bilan javob beradi, ota-ona javoblar ro'yxatini ko'radi.
- **Sevimlilar**, ko'rilganlar tarixi va saqlangan qidiruv. Saqlangan qidiruvga yangi mos e'lon chiqsa, bildirishnoma keladi.
- **Xarita:** tuman va mo'ljal bo'yicha. Aniq manzil ko'rsatilmaydi.

### 4.2 Telegramga o'xshash chat

- 1:1 chat (ota-ona ↔ enaga). Chat otklik orqali yoki "Yozish" tugmasi bilan ochiladi.
- Matn, **ovozli xabar**, rasm va fayl, **reply**, tahrirlash va o'chirish, emoji reaksiya.
- "Yozmoqda…" belgisi, onlayn yoki oxirgi faollik vaqti, ✓/✓✓ (yetkazildi va o'qildi).
- Push bildirishnoma (brauzer va Telegram bot orqali), o'qilmagan xabarlar soni.
- **Xavfsizlik:** bloklash, shikoyat qilish, birinchi xabarlarda telefon raqam va havolalarni ogohlantirish bilan ko'rsatish (firibgarlikka qarshi), spamga qarshi limit.
- **Shablonlar:** "Suhbatga taklif", "Sinov kuni" tugmalari (2-bosqich).
- **Texnologiyasi:** Laravel Reverb (WebSocket) + Redis, fayllar S3 ga mos xotirada.

### 4.3 AI yordamchi (Claude API)

| Funksiya | Kim uchun | Tavsif | Model |
|---|---|---|---|
| **Anketa va e'lon yozuvchi** | Enaga, ota-ona | Bir nechta savolga javob beradi, AI chiroyli va to'liq matn tayyorlaydi (UZ/RU). | `claude-sonnet-5-5` |
| **Aqlli qidiruv** | Ota-ona | Oddiy gap bilan: "Chilonzorda 3 yoshli o'g'limga rus tilida gaplashadigan, ovqat qila oladigan enaga, 5 mln gacha". AI so'rovni filtrlarga aylantiradi. | `claude-haiku-5-5` |
| **Moslashtirish (matching)** | Ota-ona, enaga | Vakansiya uchun eng mos 5 ta enaga, "nega mos" izohi bilan. Enagaga mos vakansiyalar. | `claude-sonnet-5-5` |
| **Moderatsiya** | Moderator | Yangi e'lon va anketalarni avtomatik tekshirish: spam, haqorat, firibgarlik belgilari, matn ichidagi kontaktlar. Moderatorga tavsiya beradi, yakuniy qarorni inson qiladi. | `claude-haiku-5-5` |
| **Chat tarjimoni** | Hammasi | Bir bosishda UZ ↔ RU tarjima (lotin va kirill). | `claude-haiku-5-5` |
| **Suhbat yordamchisi** | Ota-ona | Enaga bilan suhbat uchun savollar, sinov kunini o'tkazish bo'yicha maslahat, kelishuv (shartnoma) shablonini tayyorlash. | `claude-sonnet-5-5` |
| **Ota-ona maslahatchisi** | Ota-ona | Bola parvarishi bo'yicha umumiy maslahatlar. Tibbiy masalada "shifokorga murojaat qiling" degan ogohlantirish bilan. | `claude-sonnet-5-5` |
| **Sharhlar xulosasi** | Ota-ona | Enaga haqidagi ko'p sharhlarni 2–3 qatorga jamlaydi. | `claude-haiku-5-5` |
| **Admin tahlilchisi** | Admin | "Bu hafta qaysi tumanda talab ko'p?" kabi savollarga statistika asosida javob. | `claude-sonnet-5-5` |

**AI qoidalari:**

- Har bir foydalanuvchiga kunlik limit qo'yiladi (xarajat nazorati). Barcha so'rovlar `ai_requests` jadvaliga yoziladi.
- Claude ga **shaxsiy ma'lumot yuborilmaydi**: pasport, telefon va aniq manzil yuborishdan oldin olib tashlanadi. Foydalanuvchidan rozilik olinadi.
- Promptlar admin panelda tahrirlanadi, modelni almashtirish sozlama orqali qilinadi.
- AI chaqiruvlari Laravel Queue orqali fonda bajariladi. Chat va yozuvchi uchun streaming ishlatiladi.

### 4.4 Ishonch va xavfsizlik

- Telefon tasdiqlash (Telegram login yoki SMS).
- Hujjat tekshiruvi. Moderator qo'lda tekshiradi, keyin belgi beriladi: ✅ "Shaxsi tasdiqlangan", 🩺 "Tibbiy ma'lumotnoma", 🎓 "Sertifikat".
- Sharh va reyting faqat chatda haqiqiy aloqa bo'lgandan keyin qoldiriladi (soxta sharhlarga qarshi).
- Shikoyat tizimi, qora ro'yxat, takroriy akkauntlarni aniqlash.
- Ota-onalar uchun xavfsizlik bo'yicha qo'llanma: sinov kuni, hujjatni ko'rish, oldindan pul bermaslik.

### 4.5 Monetizatsiya (bosqichma-bosqich)

- **1-bosqich (0–6 oy): hammasi bepul.** Maqsad: ikki tomonlama bazani yig'ish. Taxminiy maqsad: 3 000 enaga anketasi va 5 000 ota-ona.
- **2-bosqich:** pullik **qo'shimcha** xizmatlar (asosiy funksiya bepul qoladi):
  - E'lonni TOPga ko'tarish va "Shoshilinch" belgisi.
  - Enaga uchun "PRO anketa": ro'yxatda yuqorida, video tanishtiruv, kengaytirilgan statistika.
  - Tezkor hujjat tekshiruvi (24 soat ichida).
- **3-bosqich:** ota-onalar uchun **obuna** (cheksiz chat ochish, kengaytirilgan AI, "saqlangan qidiruv" bildirishnomalari), agentlik va uy bog'chalari uchun biznes tarif, reklama.
- **To'lov tizimlari:** Payme, Click, Uzum. Birinchi integratsiya 2-bosqichda bo'ladi.

### 4.6 Admin panel (to'liq)

- **Dashboard:** ro'yxatdan o'tishlar, faol e'lonlar, chatlar, konversiya (e'lon → chat → "topildi"), tumanlar bo'yicha talab va taklif, AI xarajati.
- **Foydalanuvchilar:** qidirish, ko'rish, bloklash, rol berish, kirish tarixi.
- **Moderatsiya navbati:** e'lon, anketa va hujjatlar. AI tavsiyasi ko'rinib turadi.
- **Shikoyatlar** va chat xabarlarini ko'rish (faqat shikoyat qilinganlari, audit log bilan).
- **Ma'lumotnomalar:** viloyat va tuman, ko'nikmalar, tillar, ish formatlari.
- **Monetizatsiya:** tariflar, to'lovlar, promo-kodlar.
- **Kontent:** sahifalar, blog va maslahatlar (SEO uchun), FAQ.
- **Bildirishnomalar:** ommaviy xabar (push, Telegram).
- **Telegram:** kanalga avtomatik post yuborishni sozlash va shablonlar.
- **AI:** promptlar, limitlar, modellar, xarajat hisoboti.
- **Rollar va ruxsatlar** (Spatie Permission), audit log.

---

## 5. Texnologiya bo'yicha taklif

### 5.1 Ikki variant

| | **A. Laravel API + Next.js** (tavsiya) | **B. Faqat Laravel** (Inertia + React yoki Livewire) |
|---|---|---|
| SEO (e'lon sahifalari Googleda) | Next.js SSR/ISR, juda yaxshi | Inertia SSR bilan yaxshi |
| Mobil ilova keyinchalik | Bitta API ni React Native yoki Flutter ham ishlatadi | API ni alohida yozish kerak bo'ladi |
| Telegram Mini App | Next.js da qulay | Mumkin, lekin noqulayroq |
| Real-time chat UI | React ekotizimi kuchli | Mumkin |
| Ishlab chiqish tezligi | Biroz sekinroq (2 ta loyiha, 2 ta deploy) | Tezroq, bitta kod bazasi |
| Jamoa | Backend va frontend alohida ishlay oladi | 1–2 dasturchiga qulay |

### 5.2 Tavsiya: **A variant (gibrid)**

Chat, mobil ilova, Telegram Mini App va SEO loyihaning yuragi. Ular uchun API-first arxitektura keyinchalik o'zini oqlaydi. Admin panelni esa **Laravel ichida Filament** bilan qilamiz. Admin uchun alohida frontend yozish shart emas, bu 4–6 hafta vaqtni tejaydi.

```
                ┌─────────────────────────────┐
  Brauzer ───▶ │ Next.js (frontend, SSR, PWA) │──┐
  Telegram ──▶ │ Telegram Mini App (Next.js)  │  │  REST API + WebSocket
  Mobil (3-b)─▶│ React Native / Flutter       │  │
                └─────────────────────────────┘  ▼
                ┌──────────────────────────────────────────────┐
                │ Laravel 12 (API, Sanctum)                    │
                │  • Filament — admin panel                    │
                │  • Reverb — WebSocket (chat)                 │
                │  • Queue + Horizon — AI, bildirishnoma, post │
                │  • Telegram bot (webhook)                    │
                │  • Claude API servisi                        │
                └──────────────────────────────────────────────┘
                   │           │            │            │
               PostgreSQL     Redis    Meilisearch    S3 (MinIO)
```

**Stek:**

- **Backend:** Laravel 12, PHP 8.3+, Sanctum, Reverb, Horizon, Filament 3, Spatie Permission, Scout + Meilisearch.
- **Frontend:** Next.js (App Router), TypeScript, Tailwind CSS, shadcn/ui, TanStack Query, laravel-echo (chat), next-intl (UZ lotin, UZ kirill, RU).
- **Ma'lumotlar bazasi:** PostgreSQL (MySQL ham bo'ladi, lekin PostgreSQL ning JSON va geo imkoniyatlari foydaliroq).
- **Infratuzilma:** Docker, **O'zbekistondagi** server (shaxsiy ma'lumotlar qonuni, 7.3-bo'limga qarang), CI/CD (GitHub Actions), Sentry, zaxira nusxa.
- **Tashqi xizmatlar:** Claude API, Telegram Bot API, Eskiz/PlayMobile (SMS), Payme/Click/Uzum, Firebase (push).

**Agar jamoa 1 kishi bo'lsa va MVP 1 oyda kerak bo'lsa:** B variant (Laravel + Inertia React + Filament). API keyinchalik qo'shiladi.

### 5.3 Ma'lumotlar bazasi (asosiy jadvallar)

```
users            (id, name, phone, telegram_id, role, locale, status, last_seen_at)
parent_profiles  (user_id, region_id, district_id, about)
children         (parent_id, birth_year, gender, special_needs)
nanny_profiles   (user_id, bio, experience_years, price_min, price_max, price_type,
                  work_formats[], languages[], age_groups[], is_available,
                  verification_level, rating_avg, reviews_count)
nanny_skills     (nanny_id, skill_id)
documents        (user_id, type, file, status, reviewed_by, reviewed_at)
vacancies        (parent_id, title, description, district_id, work_format, schedule,
                  salary_min, salary_max, children_info, status, expires_at, is_top)
applications     (vacancy_id, nanny_id, message, status)
favorites        (user_id, favoritable_type, favoritable_id)
saved_searches   (user_id, filters json, notify)
conversations    (id, type, vacancy_id null, last_message_at)
conversation_participants (conversation_id, user_id, last_read_message_id, muted, blocked)
messages         (conversation_id, sender_id, type[text|voice|image|file|system],
                  body, attachment, reply_to_id, edited_at, deleted_at)
reviews          (author_id, target_id, conversation_id, rating, text, status)
reports          (reporter_id, reportable_type, reportable_id, reason, status)
regions, districts, skills, languages
plans, subscriptions, payments, promotions   (2-bosqich)
ai_requests      (user_id, feature, model, input_tokens, output_tokens, cost, created_at)
notifications, audit_logs, settings
```

---

## 6. Talab va taklifni o'rganish (ishni boshlashdan oldin, 2–3 hafta)

1. **Telegram kanallar monitoringi:** @moya_nyanyauz, @nyanidomashniypersonal, @jobuztashkent va o'zbek tilidagi kanallarda 2 hafta davomida postlarni yig'ib (TGStat, Telemetr yoki o'zimizning skript) quyidagilarni hisoblash:
   - vakansiya va rezyume nisbati;
   - tumanlar, maosh, ish formati va til bo'yicha taqsimot;
   - kunlik e'lonlar soni.
2. **OLX, hh.uz, homecare.uz, bolambilan.uz:** e'lonlar soni, narxlar, filtrlar, foydalanuvchi sharhlari.
3. **So'rovnoma** (Google Forms yoki Telegram bot orqali, kamida 100 ota-ona va 100 enaga). Savollar:
   - Hozir qanday qidirasiz?
   - Nimadan qo'rqasiz?
   - Nima uchun pul to'lashga tayyorsiz?
4. **Intervyular:** 10 ota-ona va 10 enaga bilan 20 daqiqalik suhbat.
5. **Raqobatchilarni sinash:** bolambilan.uz va homecare.uz da ota-ona va enaga sifatida ro'yxatdan o'tib, butun yo'lni bosib chiqish, tariflar va kamchiliklarni yozib olish. enagam.uz (Parvona) ishga tushishini kuzatib borish.
6. **Natija:** "Bozor hisoboti" (alohida hujjat), shu asosda MVP ro'yxati yakunlanadi.

---

## 7. Bosqichlar va muddatlar

> Taxmin: 1 backend + 1 frontend dasturchi + dizayner (yarim stavka).

### 0-bosqich: tayyorgarlik (2–3 hafta)

- Bozorni o'rganish (6-bo'lim), so'rovnoma.
- Brending, Figma da dizayn (asosiy ekranlar, mobile-first).
- Repo, Docker, CI/CD, server.

### 1-bosqich: MVP (8–10 hafta)

- Ro'yxatdan o'tish: Telegram login va telefon, rol tanlash.
- Enaga anketasi, ota-ona vakansiyasi, qidiruv va filtrlar, e'lon sahifalari (SEO).
- Otklik, sevimlilar.
- **Chat v1:** matn, rasm, o'qildi belgisi, onlayn holat, push.
- Moderatsiya va hujjat tekshiruvi, belgilar.
- Filament admin panel (foydalanuvchilar, moderatsiya, shikoyatlar, ma'lumotnomalar, dashboard).
- **AI v1:** anketa va e'lon yozuvchi, avto-moderatsiya.
- Telegram bot (bildirishnomalar) va kanalga avtomatik post.
- UZ (lotin) va RU tillari.
- **Ishga tushirish: Toshkent shahri, bepul.**

### 2-bosqich: o'sish (6–8 hafta)

- **Chat v2:** ovozli xabar, reply, tahrirlash, reaksiyalar, tarjima.
- **AI v2:** aqlli qidiruv, moslashtirish, suhbat yordamchisi, sharhlar xulosasi.
- Sharh va reyting, saqlangan qidiruv bildirishnomalari.
- Telegram Mini App.
- To'lovlar (Payme, Click, Uzum): TOP e'lon, PRO anketa.
- Viloyatlarga kengayish, o'zbek kirill alifbosi.

### 3-bosqich: kengayish

- Mobil ilova (iOS va Android).
- Ota-onalar obunasi, agentlik va uy bog'chalari uchun biznes tarif.
- Video tanishtiruv, onlayn suhbat (video qo'ng'iroq).
- Shartnoma shabloni va "xavfsiz ish" funksiyalari.
- Qo'shimcha kategoriyalar: sidelka, uy bekasi, repetitor.

### 7.1 Muvaffaqiyat ko'rsatkichlari (KPI)

- Faol enaga anketalari va faol vakansiyalar soni, ularning tumanlar bo'yicha nisbati.
- Vakansiyadan birinchi otklikkacha o'tgan vaqt (maqsad: < 2 soat).
- "Topildi" bilan yopilgan vakansiyalar ulushi.
- Chat ochilgan juftliklar soni, takror qaytgan foydalanuvchilar.
- 2-bosqichdan keyin: pullik xizmatga o'tish konversiyasi.

### 7.2 Marketing (qisqa)

- O'z Telegram kanallarimiz (UZ va RU), har bir yangi e'lon avtomatik u yerga tushadi.
- Ota-onalar guruhlari, mommy-bloggerlar, Instagram.
- SEO: "enaga kerak Chilonzor", "няня Юнусабад" kabi har bir tuman uchun alohida sahifa.
- Enagalarni jalb qilish: "anketangizni AI 1 daqiqada yozib beradi, bepul".

### 7.3 Xavf va huquqiy masalalar

- **Shaxsiy ma'lumotlar:** O'zbekistonning "Shaxsga doir ma'lumotlar to'g'risida"gi qonuniga ko'ra fuqarolarning ma'lumotlari O'zbekiston hududidagi serverlarda saqlanishi kerak. Bundan tashqari, maxfiylik siyosati va rozilik olish shart. Claude API ga faqat anonimlashtirilgan matn yuboriladi. **Yurist bilan tekshirish kerak.**
- **Bola xavfsizligi:** platforma enaga uchun kafolat bermaydi, lekin tekshiruv darajalarini ochiq ko'rsatadi. Shikoyatga 24 soat ichida javob beriladi.
- **"Tovuq va tuxum" muammosi** (enagasiz ota-ona kelmaydi, ota-onasiz enaga kelmaydi): avval enagalar bazasini yig'ish kerak (AI anketa, bepul, Telegram orqali), keyin ota-onalarni jalb qilish.
- **Raqobat:** bolambilan.uz allaqachon tekshiruv va Mini App ga ega. Bizning ustunligimiz chat, AI, bepul start va UX bo'lishi kerak.
- **AI xarajati:** limitlar, arzon model (Haiku) ko'p ishlatiladigan vazifalar uchun, kesh.

---

## 8. Keyingi qadamlar

1. Ushbu rejani muhokama qilish, A yoki B texnologiya variantini tasdiqlash.
2. Bozorni o'rganishni boshlash (6-bo'lim) va so'rovnoma tayyorlash.
3. Dizayn: asosiy 10–12 ekran (bosh sahifa, qidiruv, anketa, vakansiya, chat, profil, admin).
4. Repo strukturasini yaratish: `backend/` (Laravel) va `frontend/` (Next.js), Docker.
5. MVP vazifalarini GitHub Issues ga bo'lib chiqish.

---

### Manbalar

- [bolambilan.uz](https://bolambilan.uz/), [homecare.uz](https://homecare.uz/), [enagam.uz](https://enagam.uz/)
- [@moya_nyanyauz — TGStat](https://uz.tgstat.com/en/channel/@moya_nyanyauz), [@nyanidomashniypersonal](https://t.me/nyanidomashniypersonal), [@jobuztashkent — Telemetr](https://telemetr.io/en/channels/1119287109-jobuztashkent), [@BOR_ISH](https://t.me/s/BOR_ISH?before=26953)
- [OLX.uz — няня](https://www.olx.uz/rabota/q-%D0%BD%D1%8F%D0%BD%D1%8F/), [hh.uz — няня](https://hh.uz/vacancies/nyanya), [ishkop.uz](https://ishkop.uz/), [salexy.uz](https://salexy.uz/uslugi/uslugi_nyani)
- [Golden Pages — подбор няни](https://www.goldenpages.uz/rubrics/?Id=100836)
