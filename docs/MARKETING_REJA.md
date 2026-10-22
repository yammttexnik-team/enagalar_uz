# enagalar.uz — marketing reja: ERTAGA ishga tushiramiz

> **Versiya 0.2** · 2026-yil 22-oktabr. Asoschi qarori: kampaniya **ertaga (23-oktabr)** boshlanadi, natija kunlar ichida koʻrinishi kerak.
> Bu fayl: **A qism** — ertangi ishga tushirish va 7 kunlik sprint (shu yerdan boshlang). **B qism (Ilova)** — uzoq muddatli reja: 90 kunlik taqvim, bonus iqtisodiyoti, PR, SEO, backlog.
> Platforma ertaga tayyor: bonus hamyon (ledger), kampaniya havolalari, `/sorovnoma` ichki soʻrovnoma, avtomatik bonus. Hamma material **platforma havolalariga** yoʻnaltiradi (Google Form yoʻq).
> Belgilar: **[taxmin]** — mening hisobim; **[yurist]** — ishlatishdan oldin yurist koʻrigi tavsiya etiladi.

## A0. Yakuniy bonus miqdorlari (qaror)

Bonus = platforma ichidagi kredit, **naqdga aylanmaydi**, 1 bonus soʻm = 1 soʻm xizmat narxi. Pullik xizmatlar rejalashtirilgan narxi: TOP eʼlon 7 kun **25 000**, Shoshilinch 3 kun **10 000**, tez tekshiruv **20 000**, PRO anketa **30 000/oy**, ota-ona obunasi **35 000/oy** (hammasi [taxmin], 2-bosqichda test).

| Harakat | Bonus | Shart | Nega shu summa |
|---|---|---|---|
| **Soʻrovnoma** (toʻliq tugatilgan) | **50 000** | Avtomatik, soʻrovnoma toʻliq tugaganda. Sifat tekshiruvi (AI/qoʻlda) soxtani aniqlasa — admin bonusni bekor qiladi (revoke) | bolambilan 25–35 ming/oy dan 1,4–2 baravar koʻp; 2 hafta TOP ga teng; 12 daqiqalik ish uchun yetarli |
| **Repost** (eʼlonni koʻchirish) | **30 000** | Avtomatik, birinchi **faol** (moderatsiyadan oʻtgan) eʼlon/anketa chiqqanda; 14 kundan oldin oʻchirilsa — bekor qilinadi | homecare'da qoʻshimcha eʼlon 15–30 ming; 1 TOP + 1 Shoshilinch |
| **Referal** | **15 000 + 15 000** | Taklif etilgan odam tasdiqlangan eʼlon joylasa; maks 5 ta | Ikki tomonlama oʻsish |
| **Pasport tasdigʻi** | **15 000** (+5 000 har hujjatga, maks 30 000) | Nishon berilganda | Ishonch — brend asosi |
| **Sharh** | **5 000** | Haqiqiy chatdan keyin, ≥25 soʻz, maks 3 | Sharh yigʻish |
| **Asoschi nishoni** | nishon + 1 oy PRO | Birinchi 1 000 enaga | Pulsiz qiymat |
| **Kanal-admin** | **200 000/oy** + hamkor belgi, kelajakda daromad ulushi | Haftada 2 post | Adminga ham qiymat |
| **Viloyat elchisi** | **150 000/oy** | Oyiga ≥20 faol eʼlon | Viloyat oʻsishi |

**Bir odam uchun tavan: 200 000.** Birinchi 1 000 soʻrovnoma — 50 000, keyin 40 000, 3 000 dan keyin 30 000 (yangi grantlargina kamayadi, berilganlar emas). Bonus muddati: berilgandan **9 oy**, lekin pullik xizmatlar ochilgandan keyin kamida 3 oy. Real naqd xarajat 0 (AI tekshiruv ≈ 1 000 soʻm/ishtirokchi **[taxmin]**). Batafsil iqtisodiyot — Ilova B1.

## A1. Ertaga (1-kun) — soatma-soat

**Rollar:** **F** = asoschi, **D** = dasturchi (+ dasturchi agent), **C** = Claude (matn, tahlil). **1-kun maqsadi:** 6 admin bilan aloqa, 15 shaxsiy xabar, oʻz kanalga post, 20 ta tanishga yuborish, **25 ta soʻrovnoma**, **10 ta repost**.

**Kampaniya kodlari (admin panelda yarating; har biri uchun miqdor va tavan sozlanadi):**

| Kod | Nima uchun | Havolalar |
|---|---|---|
| `SURVEY_NANNY` / `SURVEY_PARENT` | Soʻrovnoma, umumiy | `https://enagalar.uz/sorovnoma?c=SURVEY_NANNY` · `t.me/enagalaruz_bot?start=c_SURVEY_NANNY` |
| `SURVEY_DM_NANNY` / `SURVEY_DM_PARENT` | Qoʻlda shaxsiy xabar partiyasi | shu uslubda |
| `SURVEY_ENAGALAR`, `SURVEY_MOYA`, `SURVEY_SAMARKAND`, `SURVEY_ENAGA_ISH` | Hamkor kanal postlari (soʻrovnoma) | `https://enagalar.uz/sorovnoma?c=SURVEY_ENAGALAR` |
| `REPOST_ENAGALAR`, `REPOST_MOYA`, `REPOST_SAMARKAND`, `REPOST_ENAGA_ISH`, `REPOST_NYANI` | Repost, kanal boʻyicha | `https://enagalar.uz/kirish?c=REPOST_ENAGALAR` · `t.me/enagalaruz_bot?start=c_REPOST_ENAGALAR` |
| `OWNCH_UZ`, `OWNCH_RU` | Oʻz kanalimiz | |
| `FRIENDS` | Tanishlar (shaxsiy yuborish) | |
| `REF_<foydalanuvchi_kodi>` | Referal (avto) | |
| `ELCHI_SAMARQAND`, `ELCHI_BUXORO`, `ELCHI_FARGONA` | Viloyat elchilari | |

Bonus miqdorlari: `SURVEY_*` = 50 000; `REPOST_*` = 30 000; `FRIENDS`/`OWNCH_*` = tegishli (50 000/30 000). Kampaniya tavani (`budget_cap`): ertaga 10 mln soʻm nominal, keyin kuzatib oshiramiz.

| Vaqt | Kim | Nima | Qancha |
|---|---|---|---|
| 08:00–09:00 | D + F | Platformani tekshirish: `/sorovnoma` (2 toʻplam), `kirish?c=...`, bot `/start c_...`, bonus hamyon, hisobot. Test hisob bilan 1 ta toʻliq oʻtish. Kodlarni admin panelda yaratish | 20 kod |
| 09:00–10:00 | F | Kampaniya Telegram akkaunti (alohida, asosiy emas): "enagalar.uz jamoasi", logo (docs/brand/telegram-avatar.png). Havolalarni telefonda sinash | — |
| 10:00–11:30 | F | **6 ta kanal-adminga** pitch (A3.3), har biriga oʻz kodi: @enagalar, @moya_nyanyauz, @enaga_samarqand, @enaga_ish, @nyanidomashniypersonal, @nyanya_tashkent_rabota | 6 xabar |
| 11:30–12:30 | F | **Oʻz kanalga** post (A3.4, `OWNCH_UZ` / `OWNCH_RU`) + 20 ta ona/enaga tanishga shaxsan (`FRIENDS`) — ular sizni biladi, spam emas | 1 post + 20 xabar |
| 12:30–14:00 | — | Tanaffus | |
| 14:00–16:00 | F | **15 ta shaxsiy xabar** (A3.1/A3.2): faqat 10:00–17:30 oraligʻida, soatiga ≤5. Kimga: oxirgi 7 kunda @enagalar yoki @enaga_ish da **eʼlon bergan** odamlar; har biriga eʼloniga bogʻlab, qoʻlda; kodlar `SURVEY_DM_*` / `REPOST_*` | 15 xabar |
| 16:00–17:00 | F + C | Kelgan savollarga javob; admin panelda birinchi soʻrovnomalar va roʻyxatdan oʻtganlar; shubhali javoblar (dublikat, tez) — Claude'ga ismsiz tashlab tekshirish | ~25 |
| 17:00–17:30 | F + C | Kunlik hisobot (A2 jadvali) — admin panel voronkasidan; ertangi tuzatishlar | 10 daqiqa |

> **Muhim:** 17:30 dan keyin hech kimga reklama xabari yubormang. Odam yozsa — javob berish reklama emas.

## A2. 7 kunlik sprint — kunlik maqsadlar

| Kun | Asosiy ish | Xabar / kontakt | Soʻrovnoma (sifatli) | Repost | Jami roʻyxat | Eslatma |
|---|---|---|---|---|---|---|
| **1** (23.10) | Formalar, 6 admin, oʻz kanal, 20 tanish, 15 DM | 41 | 15 | 5 | 25 | Birinchi javob oqimi |
| **2** | 2 admin rozi boʻlishi kutiladi; **birinchi hamkor posti**; yana 15 DM; 3 ta eslatma (A3.1) | 18 | 30 | 15 | 40 | Savollarni tuzatish |
| **3** | 2-hamkor posti; birinchi sifat tekshiruvi (Claude, ismsiz); shubhali bonuslar revoke; **D**: kredit bilan TOP | 15 | 40 | 25 | 55 | Fraud tekshiruvi |
| **4** | Viloyat elchisi qidirish (Samarqand, Buxoro, Fargʻona): 3 ta xabar; referal (A6) yoqiladi; kanal posti №2 | 18 | 45 | 30 | 70 | Referal start |
| **5** | Haftalik 1-hisobot; eng yaxshi post/kanal aniqlanadi; ikkinchi davra adminlar; blogger №1 xabari | 15 | 50 | 35 | 80 | |
| **6** | Shanba: oʻz kanalda "soʻrovnoma boryapti" natijalari (raqamsiz — faqat jami); ota-onalarga yoʻnaltirilgan post | 10 | 40 | 30 | 70 | |
| **7** | Yakshanba: 1-hafta tahlili (savollar boʻyicha dastlabki xulosa), bonus majburiyatini tekshirish; 2-hafta rejasi | 5 | 20 | 15 | 35 | Qarorlar |
| **Jami 7 kun** | | **~120** | **~240** | **~155** | **~375** | **[taxmin]** |

**Realizm:** shaxsiy xabarlar (15/kun) 7 kunda ~100 ta xolos; asosiy hajm **kanal-adminlar postlari va tanishlar tarmogʻidan** keladi. Agar 3-kunda hech bir admin rozi boʻlmasa → **Reja B**: 1 ta pullik post (≤ 1,5 mln soʻm; narxini adminlardan soʻrang) va tanishlar orqali referal. Maqsadlar tuzatiladi, lekin tuzatishdan oldin kamida 3 kun kuting.

**Natija belgilari (7-kun oxiri):** ≥150 sifatli soʻrovnoma, ≥100 repost, ≥300 roʻyxatdan oʻtgan, 2 hamkor kanal, spam shikoyati = 0.

## A3. Tayyor materiallar (nusxa olib ishlating)

> Hammasida: kim yozayotgani, nega yozayotgani, rad etish yoʻli. "Siz", qisqa, fakt (BRAND.md). Havola/kodlarni almashtiring.

### A3.1 Variant 1 — Soʻrovnoma

**Birinchi xabar (UZ)**
> Assalomu alaykum! Men enagalar.uz jamoasidanman. @kanal'dagi eʼloningiz munosabati bilan yozyapman. Biz enagalar va ota-onalar uchun tekshirilgan, bepul platforma qilyapmiz va 10 daqiqalik soʻrovnoma oʻtkazyapmiz: toʻldirganlarga **50 000 soʻm bonus** (platforma ichida TOP eʼlon va boshqa xizmatlar uchun, naqdga aylanmaydi). Havola: https://enagalar.uz/sorovnoma?c=SURVEY_DM_NANNY
> Qiziq boʻlmasa, uzr — boshqa yozmaymiz.

**Birinchi xabar (RU)**
> Здравствуйте! Я из команды enagalar.uz. Пишу в связи с вашим объявлением в @канал. Мы делаем бесплатную проверенную платформу для нянь и родителей и проводим опрос на 10 минут: за заполнение — **50 000 сум бонусами** (внутри платформы, на ТОП-объявление и другие услуги; не обналичивается). Ссылка: https://enagalar.uz/sorovnoma?c=SURVEY_DM_NANNY
> Если неинтересно — извините, больше не напишем.

**Eslatma (3 kundan keyin, faqat 1 marta) — UZ:** "Salom! Soʻrovnoma hali ochiq — 10 daqiqa, 50 000 bonus (havola oʻsha). Qiziq boʻlmasa, bezovta qilmaymiz." **RU:** «Здравствуйте! Опрос ещё открыт — 10 минут, 50 000 бонусов. Если неактуально — не беспокоим.»

**Rahmat (roʻyxatdan keyin) — UZ:** "Rahmat, {ism}! Javoblaringiz qabul qilindi. 50 000 bonus hisobingizga yozildi (ichki hamyonda koʻrinadi). Anketangizni yarating: @enagalaruz_bot" **RU:** «Спасибо, {имя}! Ответы приняты. 50 000 бонусов зачислены на ваш счёт. Создайте анкету: @enagalaruz_bot»

### A3.2 Variant 2 — Repost

**Birinchi xabar (UZ)**
> Assalomu alaykum! Eʼloningizni @kanal'da koʻrdim. enagalar.uz da shu eʼlonni **bepul** joylashingiz mumkin: havolani bosing, Telegram orqali kirasiz, eʼlon matnini tashlaysiz — AI qolganini oʻzi tayyorlaydi: https://enagalar.uz/kirish?c=REPOST_ENAGALAR . Faol eʼlon uchun **30 000 soʻm bonus** (platforma ichida).
> Qiziq boʻlmasa, uzr — boshqa yozmaymiz.

**RU**
> Здравствуйте! Видел(а) ваше объявление в @канал. На enagalar.uz его можно разместить **бесплатно**: перейдите по ссылке, войдите через Telegram, вставьте текст — AI подготовит остальное: https://enagalar.uz/kirish?c=REPOST_ENAGALAR . За активное объявление — **30 000 сум бонусов** (внутри платформы).
> Если неинтересно — извините, больше не напишем.

**Eslatma:** "Salom! Eʼlonni koʻchirish hali ochiq — 2 daqiqa va 30 000 bonus. Qiziq boʻlmasa, bezovta qilmaymiz." / «Здравствуйте! Перенос объявления всё ещё открыт — 2 минуты и 30 000 бонусов. Если неактуально — не беспокоим.»
**Rahmat:** "Rahmat! Eʼloningiz tekshiruvga yuborildi (odatda 24 soat). 30 000 bonus eʼlon faol boʻlganda avtomatik yoziladi (14 kundan oldin oʻchirilsa bekor qilinadi)." / «Спасибо! Объявление на проверке (обычно 24 часа). 30 000 бонусов зачислятся автоматически, когда объявление станет активным (при удалении раньше 14 дней отменяются).»

### A3.3 Kanal-admin hamkorlik taklifi

**UZ**
> Assalomu alaykum, {ism}! Men enagalar.uz asoschisiman — enagalar va ota-onalar uchun bepul, tasdiqlangan platforma (pasport nishoni, ichki chat). Kanalingizda eski va tekshirilmagan eʼlonlar koʻp, shu muammoni birga hal qilamiz: obunachilaringizga **bonus** (50 000 soʻrovnoma, 30 000 eʼlonni koʻchirish) taklif qilamiz; sizga — oyiga **200 000 bonus**, "hamkor kanal" belgisi, kanalingiz boʻyicha statistika va kelajakda toʻlovlardan ulush (yozma kelishuv bilan). Siz faqat haftada 2 ta post joylaysiz (matn tayyor). 10 daqiqa gaplashsak boʻladimi? Qiziq boʻlmasa, uzr — boshqa yozmaymiz.

**RU**
> Здравствуйте, {имя}! Я основатель enagalar.uz — бесплатной проверенной платформы для нянь и родителей (значок проверки паспорта, чат внутри). В вашем канале много устаревших и непроверенных объявлений — давайте решим это вместе: предложим вашим подписчикам **бонусы** (50 000 за опрос, 30 000 за перенос объявления), а вам — **200 000 бонусов в месяц**, значок «партнёр», статистику по каналу и в будущем долю с платежей (по письменному соглашению). От вас — 2 поста в неделю (текст готов). Можем созвониться на 10 минут? Если неинтересно — извините, больше не напишем.

### A3.4 Oʻz Telegram kanalimiz uchun post

**UZ**
> **Enaga qidirayapsizmi yoki ish izlayapsizmi?** 🧸
> enagalar.uz — tasdiqlangan enagalar, ichki chat, AI yordamchi. Butun Oʻzbekiston boʻylab. **Bepul.**
> 
> Hozir platformani birga qurayapmiz va sizdan yordam kerak:
> • 10 daqiqalik soʻrovnomaga javob bering — **50 000 soʻm bonus**
> • Telegramdagi eʼloningizni bizga koʻchiring — **30 000 soʻm bonus**
> 
> Bonus platforma ichida TOP eʼlon va boshqa xizmatlar uchun ishlatiladi (naqdga aylanmaydi). Birinchi 1 000 enagaga — "Asoschi" nishoni.
> 👉 Soʻrovnoma: https://enagalar.uz/sorovnoma?c=OWNCH_UZ · Eʼlonni koʻchirish: https://enagalar.uz/kirish?c=REPOST_OWNCH_UZ · Bot: @enagalaruz_bot

**RU**
> **Ищете няню или работу?** 🧸
> enagalar.uz — проверенные няни, чат внутри, AI-помощник. По всему Узбекистану. **Бесплатно.**
> 
> Мы строим платформу вместе с вами:
> • пройдите опрос на 10 минут — **50 000 сум бонусов**
> • перенесите своё объявление из Telegram — **30 000 сум бонусов**
> 
> Бонусы работают внутри платформы (ТОП-объявление и другие услуги), не обналичиваются. Первым 1 000 няням — значок «Основатель».
> 👉 Опрос: https://enagalar.uz/sorovnoma?c=OWNCH_RU · Перенос объявления: https://enagalar.uz/kirish?c=REPOST_OWNCH_RU · Бот: @enagalaruz_bot

## Sorovnoma savollari

> Dasturchi shu bo'limdan `/sorovnoma` ga seed qiladi. Har savolda: `key` (unikal), `type` (single / multi / text / number / scale), `required`, matn UZ va RU, variantlar `kod = UZ / RU`. `max=3` — ko'pi bilan 3 ta tanlash. `source=regions` — viloyat/tuman bazadan. `check` — diqqat savoli (noto'g'ri bo'lsa bonus berilmaydi). Ovozli javob (text turida) keyin.
> **Rozilik (1-ekran, majburiy):** UZ — "Javoblaringiz enagalar.uz platformasini yaxshilash uchun ishlatiladi. Natijalar faqat umumiy (ismsiz) koʻrinishda eʼlon qilinadi. Telefon raqamingiz bonusni hisoblash uchun saqlanadi va uchinchi shaxsga berilmaydi. Istagan payt oʻchirishni soʻrashingiz mumkin. Bonus platforma ichida ishlatiladi, naqdga aylanmaydi." RU — «Ответы используются для улучшения enagalar.uz. Результаты публикуются только в обобщённом виде. Телефон хранится для начисления бонуса и не передаётся третьим лицам. Можно в любой момент попросить удалить данные. Бонус действует внутри платформы, не обналичивается.»
> **Yakuniy qoida:** toʻliq tugaganda avtomatik 50 000 bonus; 1 Telegram akkaunt = 1 marta; min. vaqt 4 daqiqa (undan tez — "tekshiruvga" belgilanadi, bonus ushlab turiladi); admin panelda shubhali javoblarni bekor qilish.

### Enaga

| № | key | type | required | UZ | RU | Variantlar (kod = UZ / RU) |
|---|---|---|---|---|---|---|
| 1 | n_region | single | yes | Qaysi viloyat va tumanda ishlaysiz? | В каком регионе и районе вы работаете? | source=regions |
| 2 | n_age | single | yes | Yoshingiz? | Ваш возраст? | a1 = 18–24; a2 = 25–34; a3 = 35–44; a4 = 45–54; a5 = 55+ |
| 3 | n_exp | single | yes | Enagalik tajribangiz qancha? | Ваш опыт работы няней? | e0 = 1 yildan kam / менее 1 года; e1 = 1–3 yil / 1–3 года; e2 = 3–5 yil / 3–5 лет; e3 = 5–10 yil / 5–10 лет; e4 = 10+ yil / 10+ лет |
| 4 | n_formats | multi | yes | Qaysi formatda ishlaysiz? | В каком формате работаете? | hour = soatbay / почасово; day = kunlik / посуточно; month = oylik / помесячно; live = yashab turish / с проживанием; weekend = hafta oxiri / выходные |
| 5 | n_price_hour | number | no | Hozir yoki oxirgi ishda soatiga qancha olasiz? (soʻm) | Сколько получаете в час? (сум) | min=0 |
| 6 | n_price_day | number | no | Kuniga qancha? (soʻm) | В день? (сум) | min=0 |
| 7 | n_price_month | number | no | Oyiga qancha? (soʻm) | В месяц? (сум) | min=0 |
| 8 | n_price_want | number | yes | Oyiga qancha olishni xohlaysiz? (soʻm) | Сколько хотели бы получать в месяц? (сум) | min=0 |
| 9 | n_find_job | multi | yes | Soʻnggi ishingizni qanday topdingiz? | Как нашли последнюю работу? | tg = Telegram kanal / Telegram-канал; friend = tanish-urugʻ / знакомые; agency = agentlik / агентство; olx = OLX; bolambilan = bolambilan.uz; other = boshqa / другое |
| 10 | n_contacts_3m | number | yes | Oxirgi 3 oyda nechta oilaga yozdingiz? | Скольким семьям вы писали за 3 месяца? | min=0 |
| 11 | n_replies_3m | number | yes | Shulardan nechtasi javob berdi? | Сколько из них ответили? | min=0 |
| 12 | n_problems | multi (max=3) | yes | Ish qidirishdagi eng katta 3 muammo? | Топ-3 проблемы поиска работы? | few = ish kam / мало работы; old = eski yoki soxta eʼlonlar / старые или фейковые объявления; noreply = javob bermaydilar / не отвечают; lowpay = maosh kam / низкая оплата; delay = pulni kechiktirish / задержка оплаты; safety = xavfsizlik / безопасность; where = qayerda qidirishni bilmayman / не знаю, где искать |
| 13 | n_paid_ad | single | yes | Telegram kanalga eʼlon berish uchun pul toʻladingizmi? | Платили ли вы за размещение в Telegram-канале? | no = Yoʻq / Нет; free = Bepul berdim / Размещала бесплатно; paid = Ha, toʻladim / Да, платила |
| 14 | n_danger | text | yes | Ishda xavfli, nohush yoki adolatsiz holat boʻlganmi? Qisqacha yozing ("yoʻq" ham mumkin). | Были ли на работе небезопасные или неприятные ситуации? Кратко (можно «нет»). | minwords=1 |
| 15 | n_family_info | multi | yes | Oila haqida oldindan nimani bilishni xohlaysiz? | Что хотели бы знать о семье заранее? | kids = bolalar yoshi / возраст детей; district = tuman / район; pay = maosh / оплата; schedule = jadval / график; prev = oldingi enaga nega ketgan / почему ушла прошлая няня; cam = uyda kamera bormi / есть ли камеры; reviews = oila haqida sharhlar / отзывы о семье |
| 16 | n_verify_ready | scale | yes | Pasport bilan "Tasdiqlangan" nishonini olishga tayyormisiz? (1 = yoʻq, 5 = tayyorman) | Готовы ли пройти проверку паспорта? (1 = нет, 5 = готова) | 1..5 |
| 17 | n_verify_fear | text | no | Tekshiruvdan xavotir bor boʻlsa, nimadan? | Чего опасаетесь при проверке? | |
| 18 | n_docs | multi | yes | Qaysi hujjatlarni bera olasiz? | Какие документы можете предоставить? | passport = pasport / паспорт; med = tibbiy maʼlumotnoma / медсправка; dispensary = dispanser maʼlumotnomasi / справка из диспансера; cert = sertifikat / сертификат; refs = tavsiyanoma / рекомендации |
| 19 | n_features | multi (max=3) | yes | Platformada eng muhim 3 narsa? | Что важнее всего на платформе? | chat = ichki chat / чат внутри; ai = AI anketa yozuvchi / AI-помощник для анкеты; badges = tasdiqlangan nishonlar / значки проверки; fast = tez javob / быстрые ответы; translate = tarjima / перевод; guarantee = toʻlov kafolati / гарантия оплаты |
| 20 | n_pay_top10 | single | yes | TOP eʼlon (7 kun) 10 000 soʻm boʻlsa, sinab koʻrasizmi? | Попробуете ТОП-объявление (7 дней) за 10 000 сум? | yes = Ha / Да; no = Yoʻq / Нет |
| 21 | n_pay_top25 | single | yes | 25 000 soʻm boʻlsa-chi? | А за 25 000 сум? | yes; no |
| 22 | n_pay_top40 | single | yes | 40 000 soʻm boʻlsa-chi? | А за 40 000 сум? | yes; no |
| 23 | n_pay_pro_max | number | yes | PRO anketa uchun oyiga maksimal qancha toʻlay olasiz? (soʻm, 0 = toʻlamayman) | Сколько максимум готовы платить за PRO-анкету в месяц? (сум, 0 = не буду) | min=0 |
| 24 | n_check | single | yes | (Diqqat savoli) Shu savolda "Ikkinchi variant"ni tanlang. | (Проверка внимания) Выберите «второй вариант». | a = Birinchi / Первый; b = Ikkinchi / Второй; c = Uchinchi / Третий; check=b |
| 25 | n_ideal | text | yes | Ideal ish qidirish jarayoni uchun bitta narsa nima boʻlardi? | Что одно сделало бы поиск работы идеальным? | minwords=5 |

### Ota-ona

| № | key | type | required | UZ | RU | Variantlar (kod = UZ / RU) |
|---|---|---|---|---|---|---|
| 1 | p_region | single | yes | Qaysi viloyat va tumandasiz? | В каком регионе и районе вы? | source=regions |
| 2 | p_kids | number | yes | Nechta bolangiz bor (enaga kerak boʻladigan)? | Сколько у вас детей (для которых нужна няня)? | min=1 |
| 3 | p_kid_age | multi | yes | Bolalar yoshi? | Возраст детей? | b0 = 0–1; b1 = 1–3; b2 = 3–6; b3 = 6+ |
| 4 | p_status | single | yes | Hozir enaga bormi? | Есть ли сейчас няня? | have = Bor / Есть; seeking = Qidiryapman / Ищу; soon = Yaqinda kerak boʻladi / Скоро понадобится |
| 5 | p_formats | multi | yes | Qanday format kerak? | Какой формат нужен? | hour = soatbay / почасово; day = kunlik / посуточно; month = oylik / помесячно; live = yashab turish / с проживанием; half = yarim kun / полдня |
| 6 | p_budget_hour | number | no | Byudjet: soatiga (soʻm) | Бюджет: в час (сум) | min=0 |
| 7 | p_budget_day | number | no | Byudjet: kuniga (soʻm) | Бюджет: в день (сум) | min=0 |
| 8 | p_budget_month | number | no | Byudjet: oyiga (soʻm) | Бюджет: в месяц (сум) | min=0 |
| 9 | p_find | multi | yes | Enagani qanday qidirdingiz? | Как искали няню? | tg = Telegram kanal / Telegram-канал; friend = tanish / знакомые; agency = agentlik / агентство; olx = OLX; bolambilan = bolambilan.uz; insta = Instagram; other = boshqa / другое |
| 9a | p_days | number | yes | Oxirgi safar necha kun qidirdingiz? | Сколько дней искали в последний раз? | min=0 |
| 10 | p_cands | number | yes | Nechta nomzod koʻrdingiz? | Сколько кандидатов рассмотрели? | min=0 |
| 11 | p_met | number | yes | Nechtasi bilan uchrashdingiz? | С сколькими встретились? | min=0 |
| 12 | p_ad_result | text | no | Telegram kanalga eʼlon bergan boʻlsangiz: nechta javob keldi, nechtasi yaroqli edi? | Если давали объявление: сколько откликов, сколько подходящих? | |
| 13 | p_problems | multi (max=3) | yes | Eng katta 3 muammo? | Топ-3 проблемы? | distrust = ishonchsizlik / недоверие; unknown = tajribasi nomaʼlum / опыт неизвестен; time = vaqt koʻp ketadi / уходит много времени; price = narx / цена; old = eski eʼlonlar / старые объявления; noreply = javob bermaydilar / не отвечают; fake = yolgʻon maʼlumot / ложные данные; agency = agentlik qimmat / агентство дорого |
| 14 | p_fear | text | yes | Bolangizni begona kishiga topshirishda sizni eng koʻp nima qoʻrqitadi? | Чего вы больше всего боитесь, доверяя ребёнка няне? | minwords=3 |
| 15 | p_checks | multi | yes | Qaysi tekshiruvlar siz uchun muhim? | Какие проверки важны? | passport = pasport / паспорт; med = tibbiy / медицинская; dispensary = dispanser / диспансер; record = sudlanmaganlik / несудимость; refs = tavsiyanoma / рекомендации; video = video tanishuv / видеознакомство; cert = sertifikat / сертификат |
| 16 | p_bad | text | no | Yomon yoki yoqimsiz tajriba boʻlganmi? Qisqacha ("yoʻq" ham mumkin). | Был ли плохой опыт? Кратко (можно «нет»). | |
| 17 | p_agency | single | yes | Agentlikka murojaat qilganmisiz? | Обращались ли в агентство? | no = Yoʻq / Нет; yes = Ha / Да |
| 18 | p_agency_pay | number | no | Agentlikka qancha toʻlagansiz yoki toʻlashga tayyormisiz? (soʻm) | Сколько заплатили или готовы заплатить агентству? (сум) | min=0 |
| 19 | p_features | multi (max=3) | yes | Platformada eng muhim 3 narsa? | Что важнее всего на платформе? | badges = nishonlar / значки проверки; chat = ichki chat / чат внутри; ai = AI mos nomzod / AI-подбор; fast = tez javob / быстрые ответы; reviews = sharhlar / отзывы; guarantee = toʻlov kafolati / гарантия оплаты; video = video / видео |
| 20 | p_sub15 | single | yes | Obuna (oyiga, cheksiz chat + AI) 15 000 soʻm boʻlsa, olasizmi? | Купили бы подписку (в месяц, безлимитный чат + AI) за 15 000 сум? | yes = Ha / Да; no = Yoʻq / Нет |
| 21 | p_sub35 | single | yes | 35 000 soʻm boʻlsa-chi? | А за 35 000 сум? | yes; no |
| 22 | p_sub60 | single | yes | 60 000 soʻm boʻlsa-chi? | А за 60 000 сум? | yes; no |
| 23 | p_pay_max | number | yes | Oyiga maksimal qancha toʻlaysiz? (soʻm, 0 = toʻlamayman) | Сколько максимум заплатите в месяц? (сум, 0 = не буду) | min=0 |
| 24 | p_check | single | yes | (Diqqat savoli) Shu savolda "Uchinchi variant"ni tanlang. | (Проверка внимания) Выберите «третий вариант». | a = Birinchi / Первый; b = Ikkinchi / Второй; c = Uchinchi / Третий; check=c |
| 25 | p_ideal | text | yes | Ideal enaga qidirish tajribasi qanday boʻlardi? | Каким был бы идеальный поиск няни? | minwords=5 |

> Vaqt: ~10–12 daqiqa. Natijalar qarorlarga qanday taʼsir qilishi — Ilova B (3.5).

## A5. Platforma tekshiruvi (bugun kechqurun) va hafta ichidagi qisqa backlog

**Ishga tushirishdan oldin tekshiring (D + F, 30 daqiqa):**
- [ ] `kirish?c=CODE` va `start=c_CODE` ikkalasi `users.first_campaign` ni yozadi.
- [ ] `/sorovnoma?c=SURVEY_NANNY` — Telegram login, rozilik, 2 ta toʻplam, tugaganda 50 000 avtomatik, ikkinchi marta bonus berilmaydi (idempotent).
- [ ] Birinchi faol eʼlon chiqqanda 30 000 (repost kodlari uchun) yoziladi; 14 kundan oldin oʻchirilsa bekor qilinadi.
- [ ] Admin: hamyon saldosi, bonusni qoʻlda bekor qilish (revoke), kod boʻyicha voronka.
- [ ] Bonus shartlari sahifasi ("naqdga aylanmaydi", 9 oy) saytda; pd.gov.uz ariza boshlangan.

**Hafta ichida kod boʻyicha (qisqa roʻyxat):**

| № | Nima | Qachon | Hajm |
|---|---|---|---|
| 1 | Kredit bilan TOP eʼlon / Shoshilinch (bonus sarflash joyi — qiymatni saqlaydi) | 3–5-kun | M |
| 2 | Soʻrovnoma sifat tekshiruvi (AI baho, dublikat, tez javob belgisi) | 3–5-kun | M |
| 3 | Referal kodlari (`REF_*`, ikki tomonga 15 000, maks 5) | 4–6-kun | S–M |
| 4 | Repost-import: botga matn tashlash → AI qoralama → eʼlon | 5–7-kun | M–L |
| 5 | Hisobot: kod/kanal boʻyicha voronka va bonus majburiyati | 5–7-kun | M |

## A6. Referal (4-kundan)

Soʻrovnoma/eʼlondan keyin odam shaxsiy havola oladi: `t.me/enagalaruz_bot?start=c_REF_<kod>` yoki `https://enagalar.uz/kirish?c=REF_<kod>`. Taklif etilgan odam faol eʼlon joylasa — ikkalasiga **15 000**. Maks 5 ta, oʻz-oʻziga referal bloki.

## A7. Xavf va muvofiqlik (qisqa)

1. **Telegram:** foydalanuvchilar "Report spam" bossa akkaunt cheklanadi (begonalarga yozolmaydi, avval bir necha kun, takror boʻlsa uzoqroq, davom etsa butunlay). Manba: [Telegram Spam FAQ](https://telegram.org/faq_spam). Bot birinchi xabarni yubora olmaydi — foydalanuvchi oʻzi /start bosishi kerak ([Bots FAQ](https://core.telegram.org/bots/faq)).
2. **Xavfsiz limitlar (shaxsiy xabar):** **kuniga ≤15**, **soatiga ≤5**, faqat **10:00–17:30**, har odamga **1 xabar + maks 1 eslatma** (3 kundan keyin). Javob "yoʻq" yoki jim — boshqa yozilmaydi. Guruhga ommaviy tashlash yoʻq. Ommaviy/avtomatik DM yoʻq; kampaniya uchun **alohida akkaunt**, asosiysi emas.
3. **Faqat eʼlon berganlarga**, eʼlonning ochiq postida koʻringan odamga, eʼloniga bogʻlab yoziladi. Guruh a'zolarini yigʻib yozish (scrape) va kontaktlarni rozilik-siz bazaga saqlash — **taqiqlangan**. Faqat platformada roziligini bergan odamlar saqlanadi.
4. **Qonun:** ZRU-547 (rozilik, maqsad, olib tashlash — [Mondaq](https://www.mondaq.com/data-protection/828474/uzbekistan-enacts-first-data-protection-law), [Legal500](https://www.legal500.com/developments/thought-leadership/personal-data-compliance-in-uzbekistan/)); ZRU-1125 (2026-03-26, chet elga uzatish — [kun.uz](https://kun.uz/ru/news/2026/03/28/xraneniye-nekotoryx-personalnyx-dannyx-za-predelami-uzbekistana-razresheno)). anhor.uz maʼlumotiga koʻra **1-noyabr 2026** dan mobil reklamaga yozma/elektron rozilik va **18:00–09:00 oraligʻida reklama taqiqi** kiradi (SMS/qoʻngʻiroq haqida; messenjerlar aytilmagan — [anhor.uz](https://anhor.uz/news/v-uzbekistane-zapretyat-reklamnye-zvonki-i-sms-po-vecheram-i-nochyu/)). Ehtiyot uchun 17:30 dan keyin reklama yozmaymiz. **[yurist]** 100 dan koʻp shaxsiy xabardan oldin fikr oling.
5. **Eʼlon matnidagi kontakt** odam bergan, lekin boshqa maqsad uchun yozish bahsli boʻlishi mumkin → xabar eʼlonga bogʻlangan, qisqa, rad etish oson.
6. **Bonus:** "naqdga aylanmaydi" har joyda yozilgan; soxta javobda bekor qilinadi; soliq masalasi (ayniqsa elchilar) **[yurist/buxgalter]**.
7. **Toʻxtatish qoidasi:** spam shikoyati yoki cheklov 2 tadan oshsa — kampaniya 7 kunga toʻxtaydi, faqat kanal-hamkorlik qoladi.

---

# Ilova (B qism) — uzoq muddatli reja

> Quyida avvalgi toʻliq reja: bonus iqtisodiyoti, soʻrovnoma tahlili, qoʻshimcha variantlar, 90 kunlik taqvim, backlog va xavflar. **Ertangi ish uchun shart emas**; 2-haftadan boshlab qarab chiqing. Bu yerdagi sprint va A qismdagi raqamlar oʻrtasida farq boʻlsa, **A qism ustun**.


## B0. Qisqa xulosa (executive summary, uzoq muddat)

1. **Pul oʻrniga bonus.** Reklamaga naqd pul sarflamaymiz. Foydalanuvchilar, kanal adminlari va elchilar ish evaziga **ichki bonus (platforma ichida ishlatiladigan kredit)** oladi. Naqdga aylantirilmaydi. Hozirgi real xarajatimiz taxminan 0 (AI tekshiruv, moderatsiya vaqti) **[taxmin]**.
2. **1 bonus soʻm = 1 soʻm** (bitta kurs). Kelajakdagi pullik xizmatlar narxi ochiq eʼlon qilinadi: TOP eʼlon (7 kun) **25 000**, Shoshilinch (3 kun) **10 000**, tez tekshiruv **20 000**, PRO anketa **30 000/oy**, ota-ona obunasi **35 000/oy** (bolambilan "Standart" bilan teng).
3. **Variant 1 — So'rovnoma: 50 000 bonus** (2 qismda: 30 000 sifat tekshiruvidan keyin + 20 000 eʼlon faollashgach). Bu bolambilan'ning 1 oylik obunasidan (25–35 ming) va 2 ta homecare eʼlonidan (15–30 ming) qimmatroq koʻrinadi, lekin bizga deyarli tekin.
4. **Variant 2 — Repost: 30 000 bonus** (10 000 moderatsiyadan oʻtganda + 20 000 eʼlon 14 kun faol tursa). Bir tugma: bot havolasi, Telegram eʼlon matnini yopishtirasiz, AI eʼlonni tayyorlaydi.
5. **Yana 8 ta nol-naqd kanal:** referal (15 000 + 15 000), tasdiqlash bonusi (15 000 + 5 000 gacha har hujjatga), sharh bonusi (5 000), "Birinchi 1000 enaga" asoschi nishoni, viloyat elchilari (150 000/oy bonus), kanal-admin hamkorligi, kontent va PR ("Enaga maoshlari 2026" hisoboti), blogger barteri, uy bogʻchalari, qisqa videolar va tuman SEO sahifalari.
6. **Eng katta xavf — spam koʻrinishi.** Telegram begonalarga ommaviy yozishni cheklaydi, Oʻzbekistonda esa reklama xabarlari uchun rozilik talabi kuchaymoqda (2026-yil 1-noyabrdan). Shuning uchun asosiy yoʻl: **kanal-adminlar orqali eʼlon, ochiq izohlar, botga oʻzi kirish (opt-in)**. Shaxsiy akkauntdan ommaviy DM — yoʻq. Qoʻlda, kuniga ≤15 ta, bitta xabar + bitta eslatma.
7. **Bonus qiymatini saqlaymiz:** pullik xizmatlar tugmasi bonus bilan **birinchi kundan** (kredit-only rejimida) ishlaydi, muddat tugashi pullik xizmatlar ochilgandan hisoblanadi, narx jadvali oʻzgarmaydi, kamayish faqat **yangi** kohortalar uchun ("birinchi 1 000 ta — 50 000, keyin 30 000").
8. **Byudjet (nominal qiymat):** 500 ishtirokchi ≈ **21 mln**, 2 000 ≈ **84 mln**, 5 000 ≈ **206 mln** bonus soʻm. Haqiqiy chiqim: ≈ **1–5 mln soʻm naqd** (AI + kontent). Kutilgan sarflanish 35–50% **[taxmin]**.
9. **90 kunlik maqsad:** 3 000 roʻyxatdan oʻtgan, 1 200 faol anketa, 700 faol vakansiya, 150 "topildi", 700 sifatli soʻrovnoma, 1 ta PR hisobot (kun.uz/daryo/gazeta.uz).
10. **Dasturchi uchun P0:** bonus hamyoni va ledger (idempotent), kampaniya kodlari (`?start=c_<kampaniya>_<kod>`), bot ichida soʻrovnoma, repost-import (AI). Taxminan 4–5 hafta ish, shundan 2 hafta P0.
11. **Vaqt:** asoschi ~25 soat/hafta marketing, dasturchi ~8–10 soat/hafta marketing texnikasi (qolgani MVP).
12. **Birinchi 7 kun** — 9-boʻlimdagi roʻyxat: kampaniya kodlari, bot ichida soʻrovnoma (qoʻlda Google Forms emas), 10 ta kanal-adminga xabar, pd.gov.uz reestri, "Bonus dasturi shartlari" sahifasi.

---

## 1. Strategik mantiq

**Muammo (REJA.md 6.3):** "tovuq va tuxum" — enaga ham, ota-ona ham kam. Naqd reklama byudjeti yoʻq.

**Bizning kuchli tomonimiz:** (a) Telegram kanallardagi **80 000+ obunachi** allaqachon shu mavzuda yashaydi (@enagalar 34 923, @moya_nyanyauz 25 447, @enaga_samarqand 20 952, @enaga_ish 15 968 — [BOZOR_TAHLILI.md](BOZOR_TAHLILI.md) 2.4); (b) bu kanallarda eʼlon beruvchilar **allaqachon eʼlon yozib qoʻygan** va muammoni tan olgan (tekshiruv yoʻq, eski postlar, filtr yoʻq); (c) bizning tovarimiz — ichki bonus — bizga deyarli tekin; (d) pullik xizmatlar keyin keladi, shuning uchun bonus kelajakda haqiqiy qiymatga ega boʻladi.

**Oʻyin rejasi:**
1. **Taklif tomoni (enagalar) birinchi.** Enaga anketasi tez yigʻiladi, ota-onalar "boʻsh" platformaga kelmaydi (BRAND/REJA qoidasi).
2. **Ikki tomonlama repost:** kanalda eʼlon bergan *ota-onani ham*, *enagani ham* olib kelamiz. Ota-ona vakansiyasi enagani jalb qiladi, enaga anketasi ota-onani.
3. **Soʻrovnoma = ikki ish bitta harakatda:** (i) bozor maʼlumoti, (ii) ishtirokchi botda roʻyxatdan oʻtadi (Telegram login shart), (iii) PR uchun hisobot (sana: "Enaga maoshlari 2026").
4. **Narx va va'dalar halol:** BRAND.md — "bepul degan boʻlsak — bepul". Bonus "pul" emas, "platforma ichidagi kredit" deb yoziladi; naqdga aylanmasligi birinchi satrda aytiladi.

---

## 2. Bonus iqtisodiyoti

### 2.1 Qiymat birligi va xarid jadvali

**Qoida:** 1 bonus soʻm = 1 soʻm (face value). Bonus **faqat** quyidagilarga sarflanadi (naqdga aylanmaydi, boshqa foydalanuvchiga oʻtkazilmaydi, qaytarilmaydi).

| Xizmat (kelajakdagi pullik) | Rejalashtirilgan narx (naqd) | Bonus bilan | Asos |
|---|---|---|---|
| TOP eʼlon, 7 kun | **25 000** soʻm | 100% bonusda | homecare: qoʻshimcha eʼlon 20 000, uy bogʻcha 30 000; bolambilan 25–35 ming/oy. 7 kunlik TOP shu oraliqda |
| "Shoshilinch" belgisi, 3 kun | **10 000** soʻm | 100% | Qisqa, arzon, impuls xarid |
| Tez tekshiruv (24 soat) | **20 000** soʻm | 100% | Yoopies'da tasdiqlash bir martalik ~4 € (taxm. 55–60 ming soʻm) — biz arzonroq, chunki bepul navbat ham bor |
| PRO anketa (enaga), 30 kun | **30 000** soʻm | **maks 50%** bonus | Naqd daromadni himoya qilish |
| Ota-ona obunasi, 30 kun | **35 000** soʻm | **maks 50%** bonus | bolambilan "Standart" bilan teng |

> Ogohlantirish: narxlar **hali tasdiqlanmagan**, 2-bosqichda A/B test bilan aniqlanadi. Bonus miqdorlari shu narxlarga bogʻlangan. Narx oʻzgarsa, **bonus miqdori yangilanmaydi, faqat yangi grantlar** (7.4-boʻlim).

**Qiymat taqqoslash (nega bu summalar):**

| Bonus | Nimaga teng | Raqobatchi bilan |
|---|---|---|
| 50 000 | 2 hafta TOP (2 × 25 000) yoki 1 oy PRO + 1 hafta TOP | bolambilan 1 oylik (25–35 ming) dan **1,4–2 baravar** koʻp |
| 30 000 | 1 TOP + 1 Shoshilinch (35 000) ga yaqin | homecare'da 1,5 ta pullik eʼlon |
| 15 000 | 1 Shoshilinch + 50% TOP | — |
| 5 000 | yarim Shoshilinch | mayda "rahmat" |

**Ishtirokchining vaqti bilan solishtirish.** Soʻrovnoma ~10–12 daqiqa. Soatbay narx ~50 000 soʻm (BOZOR_TAHLILI 1.6) → 12 daqiqa ≈ 10 000 soʻm naqd. Kredit qiymati naqddan past qabul qilinadi (taxminan 40–60% **[taxmin]**), 50 000 kredit ≈ 20–30 ming "sezilgan" soʻm — ikki baravar ortiq, lekin bu **oʻrta darajada saxiy**, shuning uchun sifat nazorati va bosqichma-bosqich berish shart.

### 2.2 Tavsiya etilgan bonus miqdorlari

| № | Harakat | Bonus | Shart | Kimga |
|---|---|---|---|---|
| V1 | Toʻliq soʻrovnoma (sifat tekshiruvidan oʻtgan) | **50 000** = 30 000 + 20 000 | 30 000 — AI/qoida tekshiruvidan oʻtganda. 20 000 — anketa/eʼlon moderatsiyadan oʻtib 7 kun faol tursa | Enaga va ota-ona |
| V2 | Repost (anketa yoki vakansiya) | **30 000** = 10 000 + 20 000 | 10 000 — moderatsiyadan oʻtganda. 20 000 — 14 kun faol (yopilmagan, oʻchirilmagan) | Enaga va ota-ona |
| R1 | Referal: sizning havolangiz bilan kelgan | **15 000 ikkala tomonga** | Taklif etilgan odam telefonni tasdiqlab, moderatsiyadan oʻtgan eʼlon/anketa joylaydi | Hamma |
| R2 | Tasdiqlash bonusi | **15 000** (pasport) + **5 000** har bir hujjat (tibbiy / dispanser / sertifikat), maks **30 000** | Nishon berilganda (moderator tasdiqlagach) | Enaga (va ixtiyoriy ota-ona pasport tasdigʻi) |
| R3 | Sharh bonusi | **5 000** | Haqiqiy chatdan keyin, sharh ≥ 25 soʻz, moderatsiyadan oʻtadi; maks 3 ta | Ota-ona va enaga |
| R4 | Asoschi nishoni "Birinchi 1 000 enaga" | Bonus emas, **nishon + 1 oy PRO** (PRO ochilganda) | Roʻyxatdan oʻtgan, telefon tasdiqlangan, anketa faol; faqat birinchi 1 000 | Enaga |
| R5 | Viloyat elchisi | **150 000 / oy** bonus + har tasdiqlangan hamkor eʼlon uchun 5 000 | 3 oy, oyiga ≥ 20 faol anketa/vakansiya, maks 14 elchi (har viloyatga 1) | Enaga/ota-ona/adminlar |
| R6 | Kanal-admin hamkorligi | Admin: **200 000 bonus/oy** (kanal ≥ 5 000 obunachi) + "hamkor" belgi + kelajakda **daromad ulushi** (qarang 5.5) | Kanalda taklif postlari + hisobot | Adminlar |

**Bir foydalanuvchi uchun umumiy tavan (kampaniya davrida):** 200 000 bonus (V1 + V2 + R1·5 ta + R2 + R3 ≈ 50 + 30 + 75 + 30 + 15 = 200 000). Elchi va adminlar — alohida limit.
**Kampaniya tavani:** har kampaniya uchun maks bonus summasi (`budget_cap`), tugagach yangi grant berilmaydi.
**Takroriy toʻlov taqiqi:** bir odam bitta turdagi bonusni 1 marta oladi (V1, V2, R2 — faqat 1; R1 — 5; R3 — 3).

### 2.3 Muddat (expiry)

- **Qoida:** bonus **berilgan sanadan 9 oy**, lekin **pullik xizmatlar naqd uchun ochilgandan keyin kamida 3 oy** ishlaydi: `expires_at = max(award_at + 9 oy, paid_launch_at + 3 oy)`. Bu bonus "sotilmay qolib" yonib ketmasligini taʼminlaydi (perceived value).
- Eslatmalar: tugashidan 30 va 7 kun oldin botda xabar.
- Faol foydalanuvchi uchun (har 90 kunda kamida 1 harakat) muddat avtomatik uzaytirilmaydi, lekin **yangi bonus** olganda eski bonusning muddati bilan birga yuradi (FIFO: eng erta tugaydigan birinchi sarflanadi).
- **[yurist]** "Bonus dasturi shartlari" sahifasi (oferta qismi): naqdga aylanmaydi, boʻlishi mumkin boʻlgan oʻzgarishlar, soxta harakatda bekor qilish, muddat.

### 2.4 Majburiyat (liability) hisobi

Majburiyat = berilgan, hali sarflanmagan va muddati tugamagan bonus. Haqiqiy xarajat = faqat sarflangan bonus ekvivalenti × **marjinal xarajat** (u xizmatlar uchun ≈ 0) + yoʻqotilgan daromad (agar foydalanuvchi baribir toʻlardi).

Taxminlar **[taxmin]**: sarflanish (redemption) 40% (bonus berilganlarning yarmi 9 oy ichida bir marta TOP ishlatadi); yoʻqotilgan daromad 30% (ular baribir toʻlardi); AI + moderatsiya ≈ 1 000 soʻm/ishtirokchi.

| Stsenariy | Ishtirokchilar | Berilgan nominal bonus | Kutilgan sarflanish (40%) | Yoʻqotilgan daromad (30% × sarf) | Haqiqiy naqd chiqim (AI + kontent) |
|---|---|---|---|---|---|
| **A** | 500 | **≈ 21,4 mln** | 8,6 mln | 2,6 mln | **≈ 1 mln + 1 mln kontent = 2 mln** |
| **B** | 2 000 | **≈ 83,6 mln** | 33,4 mln | 10,0 mln | **≈ 2 mln + 2 mln = 4 mln** |
| **C** | 5 000 | **≈ 206 mln** | 82,5 mln | 24,8 mln | **≈ 5 mln + 3 mln = 8 mln** |

Stsenariy tarkibi (A / B / C): soʻrovnoma 150/600/1 500 × 50 000; repost 200/800/2 000 × 30 000; referal juftliklari 100/400/1 000 × 30 000; tasdiqlash 150/600/1 500 × 20 000 (oʻrtacha); sharh 100/400/1 000 × 5 000; elchilar 3/8/14 kishi × 150 000 × 3 oy. Kurs: ~12 000 soʻm = 1 $ **[taxmin, tekshiring]** → A ≈ 1 800 $, C ≈ 17 000 $ nominal.

**Xulosa:** nominal summa katta koʻrinadi, lekin real naqd chiqim ≤ 8 mln soʻm. Asosiy "narx" — **moderatsiya vaqti** (pasport/hujjat tekshiruvi) va **bonus sarflanishining daromadga taʼsiri**.

### 2.5 Nega bonus "tekin", lekin qiymatini yoʻqotmasligi kerak

- Bonus ichki hisob-kitob; xizmat ishlatilganda server xarajati ≈ 0. Shuning uchun mavjud tizimda saxiy boʻlish mumkin.
- **Devalvatsiya xavfi:** agar hamma 200 000 bonus yigʻib, sarflanadigan joyi yoʻq boʻlsa, bonus "qogʻoz"ga aylanadi → keyingi kampaniyalar ishlamaydi va repitatsion zarar.
- **Chora:**
  1. **Kredit-only xizmatlar birinchi kundan**: TOP eʼlon, Shoshilinch, tez tekshiruv hafta 3 dan faqat bonusga ochiladi (naqd toʻlov keyin). Foydalanuvchi bonusni **darhol ishlatib koʻradi**.
  2. Narx jadvali **ochiq sahifada** va oʻzgarmas; bonus miqdori oʻzgarishi faqat oldinga.
  3. **Kamayuvchi grant:** birinchi 1 000 soʻrovnoma — 50 000; 1 001–3 000 — 40 000; undan keyin 30 000. Sababi: "erta kelganlarga ortiqcha", yangi grantlar bahosi tushadi, eskilarniki tushmaydi.
  4. Tavan (200 000) va 50% limit PRO/obunada: pullik daromadni kannibalizatsiya qilmaydi.
  5. Panelda **"bonus qiymati" hisobi**: berilgan/sarflangan/yonib ketgan/qolgan majburiyat.
  6. Nishon/status bonusni toʻldiradi: asoschi nishoni, "hamkor" belgi — pulsiz qiymat.

---

## 3. Variant 1 — Soʻrovnoma

### 3.1 Maqsad va jarayon

**Maqsad:** (1) bozorning toʻliq rasmini olish (taklif/talab, narx, ogʻriq nuqtalari, ishonch qoʻrquvlari, qidirish yoʻllari, toʻlov tayyorligi, kerakli funksiyalar); (2) 700 ta sifatli javob 90 kunda; (3) har bir ishtirokchi botda roʻyxatdan oʻtgan boʻlishi.

**Jarayon:**
1. Odam taklifni koʻradi (admin posti, izoh, qoʻlda xabar).
2. `t.me/enagalaruz_bot?start=c_sv_<kod>` → bot: salomlashadi, **rozilik** (maqsad, maʼlumotlar, saqlash muddati, chiqib ketish) → telefonni ulashadi (Telegram "kontakt yuborish").
3. Rol tanlaydi (enaga yoki ota-ona) → bot ichida 16–18 ta savol (tugmali + ochiq, inline tugmalar, ovozli javob ham mumkin — foydalanuvchilar audio yaxshi koʻradi; AI transkript qiladi).
4. Tugagach: "Rahmat! 30 000 bonus hisobingizda (tekshiruvdan keyin)". Bonus ichki hamyonda.
5. 24 soatda AI/qoida tekshiruvi → 30 000 yoziladi. Anketa/eʼlon yaratib 7 kun faol tursa +20 000.

**Davomiylik:** 10–12 daqiqa (nanny: 17 savol; ota-ona: 18 savol). Ovozli javob 30–60 soniya.

### 3.2 Enagalar uchun savollar (17 ta)

| № | Turi | O‘zbekcha | Русский |
|---|---|---|---|
| N1 | yopiq | Qaysi viloyat va tumanda ishlaysiz? (roʻyxat) | В каком регионе и районе вы работаете? (список) |
| N2 | yopiq | Yoshingiz? (18–24 / 25–34 / 35–44 / 45–54 / 55+) | Ваш возраст? |
| N3 | yopiq | Enagalik tajribangiz qancha? (<1 / 1–3 / 3–5 / 5–10 / 10+ yil) | Ваш опыт работы няней? |
| N4 | yopiq (koʻp) | Qaysi formatda ishlaysiz? (soatbay / kunlik / oylik / yashab turish / hafta oxiri) | В каком формате вы работаете? (почасово / посуточно / по месяцам / с проживанием) |
| N5 | raqam | Hozir yoki oxirgi ishda qancha olasiz? Soatiga ___, kuniga ___, oyiga ___ soʻm. Qancha olishni xohlaysiz? | Сколько вы получаете сейчас/на последней работе? В час ___, в день ___, в месяц ___ сум. Сколько хотели бы получать? |
| N6 | yopiq (koʻp) | Soʻnggi ishingizni qanday topdingiz? (Telegram kanal / tanish-urugʻ / agentlik / OLX / bolambilan / boshqa) | Как вы нашли последнюю работу? |
| N7 | raqam | Oxirgi 3 oyda nechta oilaga yozdingiz? Nechtasi javob berdi? Nechtasi bilan uchrashdingiz? | Скольким семьям вы писали за 3 месяца? Сколько ответили? С сколькими встретились? |
| N8 | reyting (top 3) | Ish qidirishdagi eng katta muammo? (ish yoʻq / eʼlonlar eski yoki soxta / javob bermaydilar / maosh kam / toʻlovni kechiktirish / xavfsizlik / qaerda qidirishni bilmaslik / boshqa) | Главные проблемы в поиске работы? (топ-3) |
| N9 | yopiq + raqam | Telegram kanalga eʼlon berish uchun toʻladingizmi? Qancha, qancha vaqt kutdingiz? | Платили ли вы за размещение в Telegram-канале? Сколько? Сколько ждали откликов? |
| N10 | ochiq (majburiy) | Ishda hech qachon xavfli, nohush yoki adolatsiz holat boʻlganmi? Qisqacha yozing yoki ovozli yuboring. ("Yoʻq" ham mumkin) | Была ли на работе небезопасная или неприятная ситуация? |
| N11 | yopiq (koʻp) | Oila haqida nimani oldindan bilishni xohlar edingiz? (haqiqiy ism / manzil tumani / bolalar yoshi / maosh / jadval / oldingi enaga nega ketgan / kamera bor-yoʻqligi) | Что вы хотели бы знать о семье заранее? |
| N12 | shkala 1–5 + ochiq | Pasportingiz bilan "Tasdiqlangan" nishonini olishga tayyormisiz? (1–5). Qoʻrqish yoki xavotir bor boʻlsa, nimadan? | Готовы ли вы пройти проверку паспорта? (1–5). Чего опасаетесь? |
| N13 | yopiq (koʻp) | Qaysi hujjatlarni taqdim eta olasiz? (pasport / tibbiy maʼlumotnoma / dispanser / sertifikat / tavsiyanoma) | Какие документы можете предоставить? |
| N14 | reyting (top 3) | Platformada nima eng kerak? (ichki chat / AI anketa yozuvchi / nishonlar / tez javob / bildirishnoma / tarjima / toʻlov kafolati) | Что важнее всего на платформе? |
| N15 | yopiq | Quyidagi narxlarda TOP eʼlon (7 kun) ni sinab koʻrasizmi? 10 000 / 25 000 / 40 000 soʻm (har biriga: ha/yoʻq) | Попробуете ли вы TOP-объявление (7 дней) за 10 000 / 25 000 / 40 000 сум? |
| N16 | raqam | PRO anketa uchun oyiga maksimal qancha toʻlay olasiz? ___ soʻm. Shundan keyin ham "juda arzon" boʻladigan narx? ___ | Сколько максимум вы готовы платить за PRO-анкету в месяц? |
| N17 | tekshiruv + ochiq | (Diqqat savoli) Bu savolda "Ikkinchi variant"ni tanlang. Soʻnggi: ideal ish qidirish jarayoni uchun bitta narsa nima? | (Проверка внимания) Выберите «второй вариант». И последнее: что одно сделало бы поиск идеальным? |

### 3.3 Ota-onalar uchun savollar (18 ta)

| № | Turi | O‘zbekcha | Русский |
|---|---|---|---|
| P1 | yopiq | Qaysi viloyat va tumandasiz? | В каком регионе и районе вы? |
| P2 | yopiq | Bolalar soni va yoshi? (0–1 / 1–3 / 3–6 / 6+) | Сколько детей и какого возраста? |
| P3 | yopiq | Hozir enaga bormi? (bor / qidiryapman / yaqin kelajakda kerak / kerak emas) | Есть ли няня? (есть / ищу / скоро понадобится) |
| P4 | yopiq (koʻp) | Qanday format kerak? (soatbay / kunlik / oylik / yashab turish / yarim kun) | Какой формат нужен? |
| P5 | raqam | Enaga uchun byudjetingiz: soatiga ___, kuniga ___, oyiga ___ soʻm | Бюджет на няню: час/день/месяц? |
| P6 | yopiq (koʻp) | Enagani qanday qidirdingiz? (Telegram kanal / tanish / agentlik / OLX / bolambilan / Instagram / boshqa) | Как вы искали няню? |
| P7 | raqam | Oxirgi safar necha kun qidirdingiz? Nechta nomzod koʻrdingiz? Nechtasi bilan uchrashdingiz? | Сколько дней искали? Кандидатов? Встреч? |
| P8 | yopiq | Telegram kanalga eʼlon bergan boʻlsangiz: nechta javob keldi, nechtasi yaroqli edi? | Если давали объявление в канале: сколько откликов, сколько подходящих? |
| P9 | reyting (top 3) | Eng katta muammo? (ishonchsizlik / tajribasi nomaʼlum / vaqt koʻp ketadi / narx / eski eʼlonlar / javob bermaydilar / yolgʻon maʼlumot / agentlik qimmat) | Главные проблемы? (топ-3) |
| P10 | ochiq (majburiy) | Bolangizni begona kishiga ishonib topshirishda sizni eng koʻp nima qoʻrqitadi? | Чего вы больше всего боитесь, доверяя ребёнка няне? |
| P11 | reyting | Qaysi tekshiruv siz uchun muhim? (pasport / tibbiy / dispanser / sudlanmaganlik / tavsiyanoma / video tanishuv / sertifikat) | Какие проверки важны? |
| P12 | ochiq (ixtiyoriy) | Yomon yoki yoqimsiz tajriba boʻlganmi? Nima boʻlgan, qanday hal qildingiz? | Был ли плохой опыт? Что произошло? |
| P13 | yopiq + raqam | Agentlikka murojaat qilganmisiz? Qancha toʻladingiz? 300 000 soʻmdan qimmat boʻlsa ham tayyormisiz? | Обращались ли в агентство? Сколько заплатили? |
| P14 | reyting (top 3) | Platformada nima eng kerak? (nishonlar / ichki chat / AI mos nomzod / tez javob / sharhlar / toʻlov kafolati / video) | Что важнее всего? |
| P15 | yopiq | Quyidagi narxlarda ota-ona obunasi (oyiga, cheksiz chat va AI) ni olasizmi? 15 000 / 35 000 / 60 000 (ha/yoʻq) | Купили бы подписку за 15 000 / 35 000 / 60 000 в месяц? |
| P16 | raqam | Eng yaxshi sharoitda maksimal qancha toʻlaysiz (oyiga)? ___; qaysi narxda "arzon, ishonchsiz" boʻladi? ___ | Максимум, который заплатите (в месяц)? При какой цене сомневаетесь в качестве? |
| P17 | yopiq | Enagani tanlashda eng muhim 3 omil? (tajriba / narx / yoshi / til / tuman / sharhlar / nishon) | Топ-3 фактора выбора? |
| P18 | tekshiruv + ochiq | (Diqqat savoli) "Uchinchi variant"ni tanlang. Va: ideal enaga qidirish tajribasi qanday boʻladi? | (Проверка) Выберите «третий вариант». И: каким был бы идеальный поиск няни? |

> **Ovozli javob:** N10, N17, P10, P12, P18 — ovozli ham qabul qilinadi (AI transkript; CLAUDE.md: audio oʻzbekcha matnga oʻgirilganda buzilishi mumkin — tekshiruv shu bois "mazmun" boʻyicha).
> **Russian version:** 3.2–3.3 jadvallarda; RU savollar bot tilini tanlash orqali beriladi (uz — asosiy, ru).

### 3.4 Yakunlash qoidalari va firibgarlikka qarshi

| Qoida | Tafsilot |
|---|---|
| 1 ta Telegram akkaunt = 1 ta soʻrovnoma | `telegram_id` unique; bitta telefon raqami = 1 ta bonus |
| Telefon tasdiqlangan | Bot "kontakt yuborish" tugmasi; +998 raqam, oʻzi uchun ekanligi tekshiriladi (Telegram `contact.user_id == from.id`) |
| Minimal vaqt | ≥ 4 daqiqa umumiy (oʻrtacha 10); 3 soniyadan tez javoblar — shubhali |
| Majburiy savollar | Hammasi (ochiq N10/P10 va N17/P18 mazmunli boʻlishi kerak) |
| Diqqat savoli | N17/P18 ichida; notoʻgʻri → bonus berilmaydi |
| Ochiq javoblar sifati | AI (Claude Haiku) 0–5 baholaydi: mazmun, savolga aloqadorlik, spam/nonsens, kopipast, til. Minimum: har ochiq javob ≥ 10 soʻz yoki ≥ 15 soniya ovoz; oʻrtacha ≥ 3 |
| Dublikat aniqlash | Javoblar embedding/hash yaqinligi (≥ 85% oʻxshash — koʻp akkaunt), bir xil qurilma/IP klasteri (≥ 3 akkaunt), bir xil javob sxemasi |
| Qoʻlda tekshiruv | 10% tasodifiy + barcha shubhali; 2 daqiqa/javob |
| Bosqichli bonus | 30 000 tekshiruvdan keyin; 20 000 faol eʼlon/anketa 7 kun turgach |
| Qaytarib olish (clawback) | Soxta aniqlansa bonus bekor, akkaunt belgilanadi |
| Kampaniya tavani | Tugagach "kvota toʻldi" deymiz, yangi bonus berilmaydi |
| Maxfiylik | Javoblar Claude'ga **ismsiz, telefonsiz** yuboriladi (REJA.md 4.3 qoidasi). Pasport soʻralmaydi. |

### 3.5 Natijalar mahsulot qarorlariga qanday taʼsir qiladi

| Savol (natija) | Qaror |
|---|---|
| N5/P5/P16 — narx va byudjet | Tarif narxlari (TOP, PRO, obuna): median va Van Westendorp oraligʻi; Toshkent/viloyat farqi |
| N8/P9 — muammolar | Hero xabar va onboarding ustuvorligi (ishonch → tasdiqlash; "eski eʼlon" → 30 kun muddat) |
| N10/P10/P12 — ishonch va xavf | Chat ogohlantirishlari, qoʻllanma, shikoyat jarayoni, nishonlar ustuvorligi |
| N12/N13 — hujjatlar tayyorligi | Qaysi nishonni birinchi targʻib qilish; pasport rozilik matni |
| N14/P14 — funksiya reytingi | Chat v2, AI, tarjima ustuvorligi |
| N15/N16/P15/P16 — toʻlov tayyorligi | PRO/TOP narxi, obuna narxi, 2-bosqich vaqti |
| N6/P6 — qidirish yoʻllari | Qaysi kanal/hamkorni kuchaytirish |
| N7/P7 — javob darajasi | Boshlangʻich "birinchi javob 2 soat" KPI uchun asos |
| Hudud (N1/P1) | Elchilar va SEO sahifa ustuvorligi |

**Takrorlash:** hisobot (har 100 ta javobdan keyin) — Metabase/Filament sahifa. Hisobot 300 ta sifatli javobdan keyin PR uchun (5.7).

---

## 4. Variant 2 — Repost ("Eʼloningizni kanalda koʻrdim")

### 4.1 Gʻoya

> "Sizning eʼloningizni @kanal'da koʻrdim — shu eʼlonni enagalar.uz ga joylang va 30 000 bonus oling."

### 4.2 Shartlar

| Parametr | Qiymat | Sabab |
|---|---|---|
| Bonus | **30 000** = 10 000 + 20 000 | 1 TOP + 1 Shoshilinch; homecare bir eʼloni 15–30 ming |
| Tur | Enaga anketasi **yoki** ota-ona vakansiyasi | Ikki tomonlama |
| 1-qism (10 000) | Eʼlon moderatsiyadan oʻtganda (AI + moderator) | Sifat |
| 2-qism (20 000) | Eʼlon **14 kun** faol tursa; yopilmagan; kamida bitta profil kirish (ota-ona/enaga koʻrishi) | Soxta eʼlon + oʻlik eʼlon oldini olish |
| Tasdiqlangan telefon | Majburiy | Dublikat/fraud |
| Cheklov | 1 odam = 1 repost bonusi; 1 eʼlon = 1 bonus | |
| Maʼlumot | Matnda telefon raqamlari/linklar AI moderatsiyadan oʻtadi (REJA 4.3) | Firibgarlik |
| Bekor | Eʼlon rad etilsa yoki 14 kundan oldin oʻchirilsa 2-qism yoʻq | |

### 4.3 Bir bosishli oqim (one-tap flow)

1. Taklif: "Eʼloningizni 30 soniyada koʻchiring" + tugma → `https://t.me/enagalaruz_bot?start=c_rp_<kanalkodi>` (kanalkodi: `enaga`, `moya`, `sam`, `ish`...).
2. Bot: "Salom! Telegramdagi eʼloningiz matnini shu yerga yopishtiring (yoki uzatib yuboring — forward)". **Forward** qilingan postdan ham olinadi (kanal username kampaniya manbasi sifatida yoziladi).
3. AI (Claude Sonnet, REJA 4.3 "Anketa va eʼlon yozuvchi") matndan **anketa/vakansiya qoralamasi** yaratadi: rol, tuman, narx, tajriba, jadval, til. Telefon/Telegram username **olib tashlanadi** (chat platforma ichida).
4. Bot qoralamani koʻrsatadi: "Toʻgʻrimi?" Inline tugmalar: [Tasdiqlash] [Tahrirlash] [Tuman oʻzgartirish].
5. Foydalanuvchi rozilik beradi (maʼlumotni qayta ishlash) → eʼlon `pending` → moderatsiya.
6. Bot: "Eʼlon tekshiruvga yuborildi. 24 soatda javob beramiz. 10 000 bonus tasdiqlangach tushadi."
7. 14-kun: eslatma "Eʼloningiz hali faol. 20 000 bonus hisobingizda!".

**Texnik:** `/start c_rp_<kod>` → `users.first_campaign`; import jarayoni `ListingDraft` jadvalida; AI xarajati `ai_requests`; draft maks 2 000 belgi; kuniga 3 ta urinish limit.

**Axloq va qonun:** boshqa odamning *chala-ochiq* Telegram eʼlonini bizning nomdan emas, **egasining oʻz roziligi bilan** koʻchiramiz (oʻzi yuboradi). Biz kanaldan *skraping qilmaymiz* va boshqa kishi eʼlonini egasisiz joylamaymiz **[yurist]**.

---

## 5. Qoʻshimcha nol-naqd variantlar

### 5.1 Referal (R1) — 15 000 + 15 000

- Har foydalanuvchida shaxsiy havola: `t.me/enagalaruz_bot?start=c_rf_<userKod>`.
- Taklif etilgan odam telefonni tasdiqlab, moderatsiyadan oʻtgan eʼlon/anketa joylasa — ikkalasi 15 000.
- Cheklov: 5 ta (maks 75 000), oʻz-oʻziga referal yoʻq, bir qurilmadan 2 tadan koʻp yoʻq.
- Kim uchun: ota-onaning doʻsti/qoʻshnisi, enaganing hamkasbi ("yaxshi enaga hamkasbi boʻlsa, uni ham chaqir").

### 5.2 Tasdiqlash bonusi (R2) — 15 000 + 5 000/hujjat

- Pasport orqali "Tasdiqlangan": 15 000. Tibbiy, dispanser, sertifikat: har biri 5 000. Maks 30 000.
- Nishon berilganda (moderator tasdiqlagach) beriladi.
- Maqsad: ishonchni *birinchi kundan* oshirish (brend va'dasi — "Xotirjamlik"). Moderatsiya sigʻimi cheklangan: kuniga ≤ 40 ta pasport (1 moderator) — navbat boʻlsa, "tez tekshiruv" (20 000) kredit bilan.
- Eslatma: pasport rasmi tekshiruvdan keyin oʻchiriladi, ma'lumot shifrlanadi (CLAUDE.md qoidasi).

### 5.3 Sharh bonusi (R3) — 5 000

- Faqat haqiqiy chat boʻlgan juftlik; sharh ≥ 25 soʻz; moderatsiyadan oʻtadi; maks 3.
- Sharh bonusi sharhning *ijobiy yoki salbiyligiga bogʻliq emas* (xolislik: "5 000 — fikringiz uchun, baho uchun emas").

### 5.4 "Birinchi 1 000 enaga" — asoschi nishoni (R4)

- Nishon `badges` jadvalida (ma'lumot sifatida, kod emas — CLAUDE.md qoidasi): "Asoschi enaga / Основатель".
- Shart: telefon tasdiqlangan, anketa faol. Kamyob: 1 000 dan keyin yopiladi. Perceived value: ortiq, narxi 0.
- Qoʻshimcha: PRO ochilganda 1 oy bepul (30 000 qiymat).
- Hisoblagich saytda ("Asoschi 1000 ta: 412 band") — shoshiltiradi.

### 5.5 Kanal-admin hamkorligi (R6)

Qaysi kanallar: @enagalar (34 923), @moya_nyanyauz (25 447), @enaga_samarqand (20 952), @enaga_ish (15 968), @nyanidomashniypersonal (7 336), @nyanya_tashkent_rabota (4 005) va viloyat kanallari. Obunachi soni [BOZOR_TAHLILI.md](BOZOR_TAHLILI.md) boʻyicha, 2026-10-22.

**Admin nimani oladi (naqdsiz):**
1. **200 000 bonus/oy** (kanal ≥ 5 000 obunachi) — adminning oʻz eʼlonlari uchun TOP va PRO.
2. "Hamkor kanal" belgisi platformada, kanalga havola.
3. Kanalining oʻz kampaniya kodi (`c_ch_<kanal>`) va **statistikasi** (kliklar, roʻyxatdan oʻtganlar).
4. **Kelajakda daromad ulushi:** kanal orqali kelgan foydalanuvchilarning birinchi 12 oydagi pullik toʻlovlaridan 20% (naqd, 2-bosqichdan). Boshida yozma kelishuv ("niyat xati"), summa va'da qilinmaydi.
5. Kanalda "Eʼlon berish — bepul, tasdiqlangan": adminning ish yuki kamayadi (moderatsiya bizda).

**Admin nima qiladi:** haftada 2 post (soʻrovnoma taklifi / repost taklifi), pinlangan post 7 kun, izoh ostida yordam.
**Haqiqat:** adminlar pullik reklamadan daromad oladi (@enaga_samarqand — pullik reklama, BOZOR_TAHLILI 2.4). Bonus ular uchun **past qiymatli** boʻlishi mumkin. Shuning uchun asosiy pitch — "kanalingiz eski postlar va firibgarlik bilan shikoyat oladi; biz moderatsiya va tasdiqlashni olib beramiz". Rad javobi kutiladi (taxm. 6 ta adminning 2 tasi rozi).
**Reja B (ixtiyoriy mikro-byudjet):** 1,5 mln soʻmgacha — bitta pullik post narxini sinash (CAC oʻlchash). Faqat bonus yondashuvi 30 kunda ishlamasa. Narxni adminlardan soʻrash kerak (BOZOR_TAHLILI 6).

### 5.6 Viloyat elchilari (R5)

- Har viloyatga 1 ta (14 gacha), avval Samarqand, Buxoro, Fargʻona, Andijon, Namangan, Qashqadaryo (Toshkentdan tashqari — raqobat yoʻq).
- Kim: ona/enaga, mahalliy Telegram guruhlarida faol, yoki uy bogʻcha egasi.
- **150 000 bonus/oy** (3 oy) + har tasdiqlangan hamkor eʼlon uchun 5 000.
- Vazifa: oyiga ≥ 20 faol anketa/vakansiya, mahalliy guruhlarda (ruxsat bor joyda) taklif, 2 ta mahalliy video.
- **[yurist/buxgalter]** Bonus pul emas, lekin ish evaziga berilgani soliq qarashi mumkin; elchi bilan "ixtiyoriy hamkorlik" shartnomasi.

### 5.7 Kontent va PR — "Enaga maoshlari 2026"

- Manba: 3-boʻlim soʻrovnomasidan (N5, P5, P16, N15, P15).
- Shart: ≥ 300 sifatli javob, usul va namuna haqida ochiq izoh ("platforma foydalanuvchilari orasida onlayn soʻrov; vakillik emas").
- Format: 1 sahifa infografika + "Tumanlar boʻyicha maosh xaritasi". Toshkent vs viloyat. Soatbay/kunlik/oylik/yashab turish.
- Ommaviy axborot: kun.uz, daryo.uz, gazeta.uz, spot.uz, anhor.uz (bozor tahlilida anhor.uz ishlatilgan). Yagona "eksklyuziv" taklif bitta nashrga 48 soatga, keyin boshqalarga.
- Qoʻshimcha: "Ota-onalar uchun xavfsizlik qoʻllanmasi" (kamera, sinov kuni, oldindan pul toʻlamaslik — BRAND: qoʻrqitmay), "Enagaga oylik toʻlov hisoblagich".
- Natija PR: backlink, SEO, ishonch. Talab: shaxsiy maʼlumotlar yoʻq, faqat agregat.

### 5.8 Mommy-blogger barteri

- Taklif: blogger va jamoasi uchun 200 000 bonus + "Oila hamkori" belgi + oʻz kampaniya kodi; kontent: "Men enagani qanday topdim" (haqiqiy tajriba, reklama belgisi bilan).
- Mezonlar: 10–100 ming obunachi, oʻzbek/ rus ona auditoriyasi; **reklama belgisi** majburiy (Oʻzbekiston reklama qonuni **[yurist]**).
- Hajm: 5–8 ta blogger. Natija oʻlchovi: kod bilan.

### 5.9 Uy bogʻchalari hamkorligi

- Oilaviy nodavlat bogʻchalar: 28 729 ta (2024 oxiri, BOZOR_TAHLILI 2.5). Bogʻcha egalari ota-onalar bilan kundalik muloqotda; ular yordamchi/enaga manbai.
- Taklif: bogʻcha sahifasida "Tavsiya etilgan" (bepul) + 100 000 bonus 1 oy; ota-onalarga QR-plakat "Enaga kerakmi? Tasdiqlangan — enagalar.uz".
- Maqsad: 30 ta bogʻcha Toshkent + 10 viloyat 90 kunda. (3-bosqichda uy bogʻcha profili.)

### 5.10 Instagram va TikTok qisqa videolar

- 3 ustun: (1) "Enaga qidirish — 3 ta xato" (xavfsizlik maslahati, qoʻrqitmasdan); (2) "Bir kun enaga bilan" (enaga hikoyalari, ruxsat bilan); (3) "Platformada anketa 60 soniyada" (AI yozuvchi demosi).
- Hafta 3 reel + 3 stories; oʻzbekcha va ruscha. Kanallarga koʻchirma.
- Bolalar yuzini koʻrsatmaymiz yoki yozma ruxsat bilan (BRAND 9).
- Maqsad: 90 kunda 100 000 koʻrish **[taxmin]**.

### 5.11 SEO — tuman sahifalari

- Har viloyat + har yirik tuman uchun "Enaga [tuman]" sahifa, tarkib: haqiqiy faol anketa/vakansiya soni, oʻrtacha narx (soʻrovnomadan), FAQ.
- Toshkent: 12 tuman (Chilonzor, Yunusobod, Mirzo Ulugʻbek, Yakkasaroy, Shayxontohur, Sergeli, Yashnobod, Mirobod, Olmazor, Uchtepa, Bektemir, Yangihayot) + shaharlar: Samarqand, Buxoro, Fargʻona, Andijon, Namangan, Qarshi, Nukus... REJA 6: SSR tayyor.
- Birinchi 90 kunda 40 sahifa; "kaltakesak"emas, haqiqiy maʼlumot bilan. Natija 3–6 oyda (SEO sekin) — shuning uchun KPI uzoq muddatli.

---

## 6. Aloqa qoidalari (outreach) — halol xavf tahlili

### 6.1 Telegram qoidalari (tasdiqlangan)

- Telegram **spam**ga qarshi: foydalanuvchi "Report spam" bossa, moderatorlar koʻrib chiqadi; spam deb topilsa akkaunt **cheklanadi** (begonalarga yozolmaydi, guruhga post qoʻya olmaydi). Birinchi marta odatda bir necha kun, takror boʻlsa uzoqroq, davom etsa begonalarga yozish **butunlay** yopilishi mumkin; cheklangan akkaunt kontaktlarga va oʻziga birinchi yozganlarga javob bera oladi. Manba: [Telegram Spam FAQ](https://telegram.org/faq_spam).
- Telegram: username qidiruvi "yangi doʻst orttirish vositasi emas"; xabar faqat **kutayotgan** odamlarga yuborilishi kerak; ommaviy reklama, havola, taklif havolalari — shikoyat sababi, ayniqsa tijorat uchun. Guruhlarda biroz yumshoq, lekin reklama baribir cheklanadi. Manba: [telegram.org/faq_spam](https://telegram.org/faq_spam), [EU DSA qoʻllanmasi](https://telegram.org/tos/eu-dsa) (nomaqbul reklama taqiqi).
- **Bot:** botlar odatda foydalanuvchi birinchi `/start` bosgandan keyingina yoza oladi; bildirishnoma limiti: bitta chatga ~1 xabar/soniya, guruhga 20 xabar/daqiqa, umumiy ~30 xabar/soniya (pullik broadcast — alohida). Manba: [Telegram Bots FAQ](https://core.telegram.org/bots/faq). **Bot orqali begonalarga birinchi xabar yuborib boʻlmaydi — bu yaxshi, shuning uchun opt-in botdan boshlanadi.**
- Maslahat: yangi akkauntlarning cheklanishi ehtimoli yuqori; shikoyat boʻlsa @SpamBot orqali apellyatsiya, lekin "aybini tan olish" cheklovni erta olib tashlamaydi.

### 6.2 Oʻzbekiston huquqi (qisman tasdiqlangan; [yurist])

- **Shaxsga doir maʼlumotlar toʻgʻrisidagi qonun (ZRU-547, 2019-yil 1-oktabr kuchga kirgan)**: ishlov berish *roziligi*ga asoslanadi; rozilik qaytarib olinsa maʼlumot yoʻq qilinadi; uchinchi shaxsga rozilik-siz berish taqiqlangan; chet elga uzatish qoidalari bor. Manbalar: [Mondaq — Uzbekistan enacts first data protection law](https://www.mondaq.com/data-protection/828474/uzbekistan-enacts-first-data-protection-law), [Legal500 — Personal data compliance in Uzbekistan](https://www.legal500.com/developments/thought-leadership/personal-data-compliance-in-uzbekistan/), [DLA Piper](https://www.dlapiperdataprotection.com/?c=UZ&t=law). Yana: ConsentStack xulosasiga koʻra internet/telekom orqali *reklama tarqatishga* rozilik kerak ([ConsentStack](https://www.consentstack.io/regulations/uz-pdl)) — **ikkilamchi manba; lex.uz'da tekshirish shart**.
- **ZRU-1125 (2026-yil 26-mart)** — oʻzgartirishlar: biometrik/genetik/operator maʼlumotlari mamlakatda saqlanishi; boshqa maʼlumotlar uchun adekvat himoya ([kun.uz](https://kun.uz/ru/news/2026/03/28/xraneniye-nekotoryx-personalnyx-dannyx-za-predelami-uzbekistana-razresheno), [spot.uz](https://www.spot.uz/ru/2026/08/04/personal-data-abroad/) — BOZOR_TAHLILI 4.1).
- **Reklama xabarlari:** anhor.uz maʼlumotiga koʻra, yangi hukumat qarori (raqami maqolada koʻrsatilmagan) **2026-yil 1-noyabrdan** mobil reklamani tartibga soladi: reklama SMS faqat **yozma yoki elektron rozilikdan** keyin; reklama qoʻngʻiroq va xabarlari **18:00–09:00 oraligʻida taqiqlangan**; reklama yuboruvchi kompaniyalar axborot tizimlarini shaxsiy maʼlumotlar bazalari davlat reestrida roʻyxatdan oʻtkazishi kerak. Maqolada Telegram/messenjerlar haqida aytilmagan. Manba: [anhor.uz](https://anhor.uz/news/v-uzbekistane-zapretyat-reklamnye-zvonki-i-sms-po-vecheram-i-nochyu/), shuningdek [spot.uz (16.07.2026)](https://www.spot.uz/ru/tag/закон о рекламе/).
- **Bizning xulosamiz (ehtiyotkor):** messenjerlar haqida aniq norma boʻlmasa ham, shaxsiy maʼlumot qonuni (rozilik, maqsad) shaxsiy xabarlarga ham tegishli; tijoriy xabar **18:00–09:00 oraligʻida yubormaymiz** (ehtiyot), kampaniya bazasi uchun **pd.gov.uz reestri** (bepul, ~15 kun — BOZOR_TAHLILI 4.1) ishga tushirishdan **oldin**.
- **Eʼlon matnidagi kontakt** — odam bu raqamni *oʻsha eʼlonga javob uchun* qoldirgan. Unga *boshqa* maqsadda tijoriy taklif yuborish — rozilikning maqsadga bogʻliqligi sababli **bahsli** boʻlishi mumkin. Shuning uchun birinchi xabar *eʼlonning oʻziga bogʻlangan* ("eʼloningiz uchun bepul joy"), qisqa, rad etish oson, kam hajmda; 100 dan koʻp shaxsiy xabar yuborishdan oldin yurist fikri.

### 6.3 Tavsiya etilgan (muvofiq) yondashuv — ustuvorlik tartibida

| # | Yoʻl | Tavsif | Xavf |
|---|---|---|---|
| a | **Kanal-admin hamkorligi** (asosiy) | Admin oʻz kanalida taklif postini joylaydi (soʻrovnoma/repost), pinlangan | Past: admin oʻz auditoriyasiga yozadi, Telegram qoidalariga muvofiq |
| b | **Ochiq izohlar / guruhlar** (ruxsat bor joyda) | Kanalga bogʻlangan muhokama guruhida, qoidalarga mos, 1 ta izoh/post. Guruh qoidasini oʻqing; reklamani taqiqlasa — yozmaymiz | Oʻrta: guruh admin bloklashi |
| c | **Qoʻlda, shaxsiylashtirilgan, kam hajmli birinchi xabar** | Faqat eʼlon egasiga, eʼloniga bogʻlab. Maks **15 ta/kun**, **5 ta/soat**, 10:00–17:30 oraligʻida; 1 xabar + maks 1 eslatma (3 kundan keyin); "yoʻq" yoki javobsizlik → boshqa yozmaymiz; rad etgan raqamni *bloklash-roʻyxatiga* (faqat "yozmang" belgisi, boshqa maʼlumotsiz) | Oʻrta: shikoyat → akkaunt cheklovi. Shaxsiy emas, **maxsus kampaniya akkaunti** (asosiy akkaunt emas) |
| d | **Bot opt-in** | Barcha asosiy oqim `t.me/enagalaruz_bot?start=c_...` orqali, odam oʻzi bosadi, rozilik bot ichida, `/stop` har doim ishlaydi | Past |

**Taqiqlangan:** (1) shaxsiy akkauntdan ommaviy/avtomatik DM; (2) guruhlardan a'zolar ro'yxatini yig'ib yozish (scrape); (3) kontaktlarni bazaga rozilik-siz saqlash; (4) soxta "oldin tanishmiz" ohangi; (5) tungi xabarlar; (6) bir xil matnni 20 martadan koʻp yuborish; (7) kanal adminining izni siz kanal ichida postni oʻzgartirish.

**Agar avtomatlashtirish ishlatilsa** (faqat qoralama va navbat uchun, **yuborishni insonga qoldirib**): AI shaxsiylashtirilgan qoralama tayyorlaydi (eʼlon matniga tayanib), inson har birini koʻrib "Yuborish" bosadi (human-in-the-loop). Avtomatik yuborish yoʻq. Kunlik tavan 15, soatlik 5; shikoyat yoki "spam" belgisi 2 tadan oshsa kampaniya 7 kunga toʻxtatiladi.

**Maʼlumot saqlash:** faqat roziligini bergan (botga kirgan) kishining maʼlumoti saqlanadi. Roziligi yoʻqlar uchun: faqat "kontakt qilingan/qilinmagan" belgisi va "yozmang" bayrogʻi, 90 kundan keyin oʻchiriladi **[yurist]**.

### 6.4 Xabar shablonlari (UZ + RU, brend ohangida)

> Qoidalar: "siz", qisqa, fakt, qoʻrqitmaydi. Birinchi xabarda *kim yozayotgani*, *nimaga yozayotgani* va *rad etish yoʻli* bor.

#### 6.4.1 Variant 1 — Soʻrovnoma

**Birinchi xabar (UZ)**
> Assalomu alaykum! Men enagalar.uz jamoasidanman. Siz @kanal'da enaga eʼlonini bergansiz — shu munosabat bilan yozyapman. Biz enagalar va ota-onalar uchun tekshirilgan, bepul platforma qilyapmiz va 10 daqiqalik soʻrovnoma oʻtkazyapmiz: toʻldirganlarga **50 000 soʻm bonus** (platforma ichida TOP eʼlon va boshqa xizmatlar uchun, naqdga aylanmaydi). Qiziq boʻlsa: t.me/enagalaruz_bot?start=c_sv_xxx
> Qiziq boʻlmasa, uzr — boshqa yozmaymiz.

**Birinchi xabar (RU)**
> Здравствуйте! Я из команды enagalar.uz. Пишу в связи с вашим объявлением о работе няни в @канал. Мы делаем бесплатную проверенную платформу для нянь и родителей и проводим опрос на 10 минут: за заполнение — **50 000 сум бонусами** (внутри платформы, на ТОП-объявление и другие услуги; не обналичивается). Если интересно: t.me/enagalaruz_bot?start=c_sv_xxx
> Если неинтересно — извините, больше не напишем.

**Eslatma (UZ), 3 kundan keyin, 1 marta**
> Salom! Eslatib qoʻyaman: soʻrovnoma hali ochiq, 10 daqiqa, 50 000 bonus. Yozmasangiz ham — hammasi joyida, boshqa bezovta qilmaymiz. 🙂

**Eslatma (RU)**
> Здравствуйте! Напоминаю: опрос ещё открыт, 10 минут, 50 000 бонусов. Если не актуально — всё в порядке, больше не побеспокоим.

**Roʻyxatdan keyin rahmat (UZ, botdan)**
> Rahmat, {ism}! Javoblaringiz qabul qilindi. Tekshiruvdan keyin (24 soat) 30 000 bonus hisobingizda boʻladi. Qolgan 20 000 — anketangiz tasdiqlanib, 7 kun faol tursa. Anketa yaratish: [AI bilan yozish]

**(RU)**
> Спасибо, {имя}! Ответы приняты. После проверки (до 24 часов) на счёт придут 30 000 бонусов. Остальные 20 000 — когда анкета пройдёт проверку и проработает 7 дней. Создать анкету: [Написать с AI]

#### 6.4.2 Variant 2 — Repost

**Birinchi xabar (UZ)**
> Assalomu alaykum! Eʼloningizni @kanal'da koʻrdim. enagalar.uz da shu eʼlonni **30 soniyada** bepul joylashingiz mumkin: matnni botga tashlaysiz, AI qolganini oʻzi yozadi. Tasdiqlangan eʼlon — yana **30 000 soʻm bonus** (platforma ichida). Havola: t.me/enagalaruz_bot?start=c_rp_xxx
> Qiziq boʻlmasa, uzr — boshqa yozmaymiz.

**(RU)**
> Здравствуйте! Видел(а) ваше объявление в @канал. На enagalar.uz можно разместить его бесплатно за **30 секунд**: пришлите текст боту, остальное AI сделает сам. После проверки — **30 000 сум бонусов** (внутри платформы). Ссылка: t.me/enagalaruz_bot?start=c_rp_xxx
> Если неинтересно — извините, больше не напишем.

**Eslatma (UZ):** "Salom! Eʼlonni koʻchirish hali ham ochiq — 30 soniya va 30 000 bonus. Qiziq boʻlmasa, bezovta qilmaymiz." / **(RU):** «Здравствуйте! Перенос объявления всё ещё доступен — 30 секунд и 30 000 бонусов. Если неактуально — не беспокоим.»

**Rahmat (UZ):** "Rahmat! Eʼloningiz tekshiruvga yuborildi, odatda 24 soat ichida javob beramiz. 10 000 bonus tasdiqlangach tushadi, 20 000 — 14 kundan keyin." **(RU):** «Спасибо! Объявление отправлено на проверку, обычно отвечаем в течение 24 часов. 10 000 бонусов придут после проверки, ещё 20 000 — через 14 дней.»

#### 6.4.3 Referal (botdan, taklif etuvchi tarqatadi)

**UZ:** "Men enagalar.uz da anketa/eʼlon joyladim. Siz ham sinab koʻring: tasdiqlangan enagalar, ichki chat, bepul. t.me/enagalaruz_bot?start=c_REF_xxx"
**RU:** «Я разместил(а) анкету/объявление на enagalar.uz. Попробуйте и вы: проверенные няни, чат внутри, бесплатно. По моей ссылке — по 15 000 бонусов нам обоим: t.me/enagalaruz_bot?start=c_rf_xxx»

**Rahmat (referal):** "Rahmat! {ism} sizning havolangiz orqali qoʻshildi. 15 000 bonus hisobingizda." / «Спасибо! {имя} присоединился(лась) по вашей ссылке. 15 000 бонусов на вашем счёте.»

#### 6.4.4 Kanal-admin hamkorligi (birinchi xabar)

**UZ:**
> Assalomu alaykum, {ism}! Men enagalar.uz asoschisiman — enagalar va ota-onalar uchun bepul, tasdiqlangan platforma. Kanalingizda koʻp eski va tekshirilmagan eʼlon bor, buni yaxshilashda yordam bera olamiz: sizning obunachilaringizga **bonus** (50 000 soʻrovnoma, 30 000 eʼlon koʻchirish) taklif qilamiz, sizga — kanal uchun **200 000 bonus/oy**, hamkor belgisi va statistika. Kelajakda toʻlovlardan ulush haqida ham gaplashamiz. 10 daqiqa suhbatlashsak boʻladimi? Qiziq boʻlmasa, uzr — boshqa yozmaymiz.

**RU:**
> Здравствуйте, {имя}! Я основатель enagalar.uz — бесплатной проверенной платформы для нянь и родителей. В вашем канале много устаревших и непроверенных объявлений, и мы можем помочь: предложим вашим подписчикам **бонусы** (50 000 за опрос, 30 000 за перенос объявления), а вам — **200 000 бонусов в месяц**, значок партнёра и статистику. В будущем обсудим долю с платежей. Можем созвониться на 10 минут? Если неинтересно — извините, больше не напишем.

**Hamkor posti (kanal uchun, UZ):**
> 📌 Eʼlon berasizmi yoki enaga qidiryapsizmi? enagalar.uz — tasdiqlangan enagalar, ichki chat, bepul. Eʼloningizni bizga koʻchirsangiz, **30 000 soʻm bonus** (platforma ichida). 10 daqiqalik soʻrovnomaga javob bersangiz — **50 000 bonus**. Bot: @enagalaruz_bot

**(RU):** «Размещаете объявление или ищете няню? enagalar.uz — проверенные няни, чат внутри, бесплатно. Перенесёте объявление к нам — **30 000 сум бонусов**. Пройдёте опрос (10 минут) — **50 000 бонусов**. Бот: @enagalaruz_bot»

#### 6.4.5 Ochiq izoh (kanal muhokamasida, admin ruxsati bilan)

**UZ:** "Eʼlon berganlarga: enagalar.uz da bepul, tasdiqlangan joylash + 30 000 bonus. Bot: @enagalaruz_bot (reklama)." / **RU:** «Тем, кто разместил объявление: на enagalar.uz — бесплатное размещение + 30 000 бонусов. Бот: @enagalaruz_bot (реклама)». Faqat admin ruxsati bilan, 1 ta izoh/post.

---

## 7. Voronka va KPI

### 7.1 Kampaniya kodlari va kuzatuv

Format: `https://t.me/enagalaruz_bot?start=c_<kampaniya>_<kod>` (Telegram `start` parametri: lotin harf, raqam, `_` va `-`, maks 64 belgi — **tekshiring**).

| Kampaniya | Prefiks | `<kod>` misol |
|---|---|---|
| Soʻrovnoma | `c_sv_` | `enagalar`, `moya`, `dm01` (qoʻlda xabar partiyasi) |
| Repost | `c_rp_` | `enagalar`, `sam`, `ish` |
| Referal | `c_rf_` | `u12345` (foydalanuvchi kodi) |
| Kanal hamkori | `c_ch_` | `enagalar`, `moya` |
| Elchi | `c_am_` | `sam01`, `buh01` |
| Blogger | `c_bl_` | `blog01` |
| Bogʻcha | `c_kg_` | `kg_chil01` |
| Video/Instagram | `c_ig_`, `c_tt_` | `reel03` |
| SEO/sayt | `utm_*` | sayt → botga `c_web_<sahifa>` |

Kuzatuv: `users.first_campaign`, `users.first_code`, `campaign_events` (start, consent, phone, survey_start, survey_done, listing_draft, listing_approved, listing_active_14d, chat_started). Panelda har kod uchun voronka.

### 7.2 Voronkalar va maqsadlar

**Variant 1 (soʻrovnoma):** kontakt/koʻrish → kirish → rozilik+telefon → boshlash → tugatish → sifatli → faol eʼlon
**Variant 2 (repost):** kontakt → bot start → qoralama → yuborildi → tasdiqlandi → 14 kun faol → chat

| Bosqich (30 kun, taxminiy konversiya) | V1 kanal-postlari | V1 qoʻlda DM | V2 repost (DM + post) |
|---|---|---|---|
| Koʻrish / kontakt | 6 post × ~8 000 = 48 000 koʻrish | 300 kontakt | 300 DM + 4 post |
| Bosish / javob | 1,5% → 720 | 20% → 60 | 15% → 45 + 300 bosish |
| Bot start + rozilik | 55% → 400 | 70% → 42 | 70% → ~240 |
| Boshlash / qoralama | 60% → 240 | 60% → 25 | 60% → 145 |
| Tugatish / yuborish | 65% → 156 | 70% → 18 | 70% → 100 |
| Sifatli / tasdiqlangan | 80% → 125 | 80% → 14 | 85% → 85 |
| Faol (7/14 kun) | 60% → 75 | 60% → 8 | 80% → 68 |
| Chatgacha | — | — | ~50% → 34 |

> Bu **[taxmin]**, birinchi 2 haftada toʻgʻrilanadi. Chunki qoʻlda DM hajmi (kuniga 15) cheklangan, **asosiy hajm kanal-postlar va referaldan** keladi.

### 7.3 Umumiy maqsadlar (90 kunlik)

| Koʻrsatkich | 30-kun | 60-kun | 90-kun |
|---|---|---|---|
| Roʻyxatdan oʻtgan (jami) | 400 | 1 200 | **3 000** |
| Sifatli soʻrovnoma (shu jumladan enaga/ota-ona) | 150 (90/60) | 400 | **700** |
| Repost orqali tasdiqlangan eʼlon | 100 | 300 | 600 |
| Faol enaga anketasi | 250 | 600 | **1 200** |
| Faol vakansiya | 120 | 350 | **700** |
| Boshlangan chat | 150 | 600 | 1 500 |
| "Topildi" bilan yopilgan | 5 | 40 | **150** |
| Tasdiqlangan (pasport) enagalar ulushi | 15% | 25% | 30% |
| Faol kanal-hamkorlar | 2 | 4 | 6 |
| Elchilar | 2 | 6 | 10–14 |
| Berilgan nominal bonus | 8 mln | 40 mln | 110 mln |
| Birinchi javob vaqti (vakansiya) | < 12 s | < 6 s | < 2 s (REJA.md maqsadi) |
| 7-kun retention | 25% | 30% | 35% |

(REJA 4.5: 6 oyda 3 000 enaga va 5 000 ota-ona; 90 kunda 1 200 enaga — yoʻl xaritasining 40%.)

**Nazorat koʻrsatkichlari (haftalik):** CAC-ekvivalent (berilgan bonus / faol ro'yxat), fraud ulushi (< 8%), moderatsiya navbati (< 24 soat), bonus sarflanishi, spam shikoyat (0 ta maqsad), "stop"ga javoblar ulushi.

---

## 8. 90 kunlik taqvim (haftama-hafta)

> **Hafta 0–4: MVP qurilmoqda (REJA 1-bosqich), hafta 5 — beta/ochilish.** Marketing MVPdan oldin boshlanadi: soʻrovnoma botda ishlaydi (login bot allaqachon bor).
> Quyida "H" — marketing haftasi (bugundan, 2026-10-22 dan).

| Hafta | Asosiy ish | Natija |
|---|---|---|
| **H1** | Birinchi 7 kun (9-boʻlim); kampaniya kodlari; soʻrovnoma matni; bonus shartlari sahifasi | Tayyor material |
| **H2** | Botda soʻrovnoma beta (20 kishi tanish); 6 kanal-adminga xabar; pd.gov.uz ariza | Sinov, 20 javob |
| **H3** | **Soʻrovnoma ochilishi**: 2 hamkor kanalda post; qoʻlda 15 DM/kun (maks 5 kun); kredit-only TOP yoqiladi | 60 javob |
| **H4** | Hamkorlik kelishuvlari; 3 elchi (Samarqand, Buxoro, Fargʻona); sifat nazorati; birinchi hisobot | 150 javob, 100 ro'yxat |
| **H5** | **Beta/ochilish**: repost oqimi (V2) yoqiladi; referal; asoschi nishoni (1 000) | 200 anketa |
| **H6** | Instagram/TikTok: 3 reel; blogger №1; Toshkent tuman SEO (10 sahifa) | +300 ro'yxat |
| **H7** | 2-davra post (rus tilli kanallar); elchilar hisobi; moderatsiya navbati | 600 ro'yxat |
| **H8** | **PR tayyorlash**: ≥300 javob → "Enaga maoshlari 2026" qoralamasi; yurist ko'rik | Hisobot v1 |
| **H9** | PR: kun.uz/daryo/gazeta.uz'ga eksklyuziv; bogʻchalar №1–10 | Chop etish |
| **H10** | PR tarqatish (boshqa nashrlar); blogger №2–3; viloyat SEO (20 sahifa) | +500 ro'yxat |
| **H11** | Tasdiqlash kampaniyasi (R2); sharh bonusi; "Topildi" hikoyalari | 25% tasdiqlangan |
| **H12** | Natijalarni baholash: bonus sarflanishi, fraud, narx testi; PRO/TOP narxini aniqlash | Qaror |
| **H13** | 2-bosqichga tayyorlik: pullik xizmatlar, narx E'lon, bonus qoidalari yangilash; keyingi kvartal rejasi | Reja |

**Jamoa rollari (1 dasturchi + asoschi + Claude):**

| Rol | Kim | Vazifa | Haftalik soat |
|---|---|---|---|
| Growth/hamkorlik | Asoschi | Adminlar, elchilar, blogger, qoʻlda DM (15/kun) | ~12 |
| Kontent/PR | Asoschi + Claude | Postlar, reel scenariylar, hisobot, press-reliz | ~8 |
| Moderatsiya/support | Asoschi | Eʼlon/pasport navbati, shikoyatlar | ~5 |
| Texnika (marketing) | Dasturchi | Bonus hamyon, kodlar, soʻrovnoma, import (10-bo'lim) | ~8–10 |
| Tahlil/matnlar | Claude (subagentlar sonnet/haiku) | Soʻrovnoma tahlili, matn UZ/RU, SEO sahifa qoralamalari | — |

**Jami:** asoschi ~25 soat/hafta, dasturchi ~8–10 soat/hafta (CLAUDE.md: oddiy vazifalar subagentlarga — sonnet/haiku; asosiy model arxitekturani hal qiladi).

---

## 9. Birinchi 7 kun — amaliy roʻyxat

- [ ] **Kun 1:** bonus qoidalari yakunlash (shu hujjat 2.2) va "Bonus dasturi shartlari" sahifasi qoralamasi (naqdga aylanmaydi, muddat, bekor qilish). **[yurist]**ga yuborish.
- [ ] **Kun 1:** `pd.gov.uz` da maʼlumotlar bazasini ro'yxatdan o'tkazishni boshlash (bepul, ~15 kun — BOZOR_TAHLILI 4.1).
- [ ] **Kun 2:** kampaniya kodlari jadvali (7.1) va Telegram `start` parametri chegarasi bo'yicha test.
- [ ] **Kun 2:** dasturchi: bonus hamyon/ledger (10-boʻlim P0), kampaniya atributsiyasi.
- [ ] **Kun 3:** soʻrovnoma savollari (3.2–3.3) botga kiritish; 5 tanish bilan sinov (vaqt o'lchash: maqsad ≤ 12 daqiqa).
- [ ] **Kun 3:** Telegramda **alohida kampaniya akkaunti** yaratish (asosiy emas, eski raqam; profilda logo + "enagalar.uz jamoasi").
- [ ] **Kun 4:** 10 ta kanal-admin ro'yxati (6.4.4 shablon): @enagalar, @moya_nyanyauz, @enaga_samarqand, @enaga_ish, @nyanidomashniypersonal, @nyanya_tashkent_rabota + 4 viloyat; xabar yuborish (kuniga ≤ 5).
- [ ] **Kun 5:** UZ/RU post va 3 reel scenariylari; "50 000 bonus" bannerlari (BRAND ranglari, Telegram avatar).
- [ ] **Kun 6:** oʻz Telegram kanallari (UZ/RU) ochish, qoidalar (BRAND 5 — nom tekshiruvi); pinlangan post.
- [ ] **Kun 7:** KPI panelini yoqish (har kod bo'yicha), birinchi haftalik hisobot, qarorlar: kim rozi boʻldi, qaysi post ishladi.

---

## 10. Mahsulot/texnika backlogi (dasturchi uchun)

Ustuvorlik: **P0** (kampaniyadan oldin), **P1** (birinchi 4 hafta), **P2** (keyin). Hajm: **S** (≤ 1 kun), **M** (2–4 kun), **L** (1–2 hafta).

| # | Vazifa | Ustuvorlik | Hajm | Izoh |
|---|---|---|---|---|
| 1 | **Bonus hamyoni va ledger**: `bonus_ledger` (user_id, amount imzoli, type [award/spend/expire/revoke], reason_code, campaign_id, ref_type/ref_id, `idempotency_key` UNIQUE, expires_at, remaining), `bonus_campaigns` (kod, miqdor, budget_cap, spent, boshlanish/tugash, qoidalar JSON). Saldo — ledgerdan hisoblanadi. FIFO sarflash. Idempotent berish (qayta ishga tushsa ikki marta yozilmaydi). Muddat jobi + eslatma | **P0** | L | Asosiy |
| 2 | **Kampaniya kodlari**: `/start c_<kamp>_<kod>` ni tahlil qilish, `users.first_campaign/first_code`, `campaign_events` | **P0** | S–M | Atributsiya |
| 3 | **Bot ichida soʻrovnoma moduli**: savollar ma'lumot sifatida (`surveys`, `survey_questions`, `survey_responses`, `answers`), inline tugmalar, ovozli javob → transkript, UZ/RU, davom ettirish, rozilik | **P0** | L | Google Forms emas — telefon/ID bilan bog'lash kerak |
| 4 | **Soʻrovnoma sifat tekshiruvi**: AI (Haiku) baholash, dublikat aniqlash (hash/embedding), qoidalar, `quality_score`, bonus 1-qismi avtomat | **P1** | M | Anti-fraud |
| 5 | **Repost-import**: botga matn/forward → AI qoralama (anketa/vakansiya) → tahrirlash → `pending`; telefon/username olib tashlash | **P0** | M–L | Variant 2 asosi |
| 6 | **Bonus trigger'lari**: eʼlon tasdiqlandi, 14 kun faol, nishon berildi, sharh, referal — hodisalar bilan | **P1** | M | `ledger` ga ulanadi |
| 7 | **Kredit-only xizmatlar**: TOP eʼlon, Shoshilinch, tez tekshiruv — bonus bilan sotib olish | **P1** | M | Bonus qiymati (2.5) |
| 8 | **Referal kodlari**: shaxsiy havola, qoidalar, limit, oʻz-oʻziga referal bloki | **P1** | S–M | |
| 9 | **Admin/Filament hisobotlar**: kod bo'yicha voronka, bonus berilgan/sarflangan/yonib ketgan, majburiyat, fraud navbati, soʻrovnoma natijalari | **P1** | M | |
| 10 | **Rozilik/"yozmang" ro'yxati**: `consents` ga kampaniya roziligi, `/stop`, qora ro'yxat | **P0** | S | Huquqiy |
| 11 | **Soʻrovnoma tahlili**: agregat dashboard, CSV eksport, AI xulosa (anonim) | **P2** | M | PR hisoboti uchun |
| 12 | **Hamkor/elchi paneli**: kanal statistikasi, hisobotlar | **P2** | M | |
| 13 | **Nishon "Asoschi"** (ma'lumot, `badges`), hisoblagich | **P1** | S | CLAUDE.md: nishon = ma'lumot |
| 14 | **SEO tuman sahifalari** (shablon, faol soni) | **P1** | M | REJA 6 |
| 15 | **Bot bildirishnomalari**: bonus, eslatma, 30/7 kun muddat | **P1** | S | |

**P0 yig'indisi:** 1, 2, 3, 5, 10 → ~3–4 hafta (dasturchi MVP bilan parallel; Claude + sonnet subagentlar yordamida). Tavsiya: P0 ni MVPning "bot + auth" qatlamiga ulab, birinchi navbatda 1, 2, 10, 3.
**Test:** `php artisan test` va `./vendor/bin/pint --test` (CLAUDE.md); idempotent berish, FIFO sarflash, muddat uchun testlar majburiy.

---

## 11. Xavflar va choralar

| Xavf | Ehtimol | Taʼsir | Chora |
|---|---|---|---|
| **Fraud** (koʻp akkaunt, soxta javob/eʼlon) | Yuqori | Bonus yo'qotish, ma'lumot buzilishi | 1 telegram_id + tel, AI sifat, dublikat, bosqichli berish, clawback, 10% qoʻlda tekshiruv (3.4) |
| **Past javob** (obunachilar bosmaydi, adminlar rad etadi) | O'rta–yuqori | Maqsad kechikadi | 6 admin va 3 elchi bir vaqtda; post matni A/B; Reja B mikro-byudjet 1,5 mln; referal; mavjud tanishlar |
| **Telegram cheklovi / akkaunt ban** | O'rta | Aloqa kanali yo'qoladi | Kampaniya akkaunti, 15/kun, qoʻlda, rad etgan — qayta yozilmaydi; asosiy oqim — bot opt-in (6) |
| **Kanal adminlari bilan nizo** (kanal ichida raqobat sifatida qabul qilinadi) | O'rta | Hamkor yo'qoladi | Hamkorlik niyat xati; admin daromadini himoya qilish (revenue share); kanalga kontent trafik beramiz |
| **Bonus devalvatsiyasi** (sarflanadigan joy yoʻq) | O'rta | Ishonch yo'qoladi | Kredit-only xizmatlar hafta 3 dan; narx jadvali ochiq; kamayuvchi grant faqat yangi; expiry pullik ochilgandan keyin (2.3, 2.5) |
| **Spam obro'si** | O'rta | Brend ishonchiga zarar (BRAND: ishonch asosiy) | Kam hajm, shaxsiylashtirilgan, rad etish oson, har kampaniyada "reklama" belgisi; shikoyat > 2 → 7 kun pauza |
| **Huquqiy** (ZRU-547/1125, reklama qarori, bonus solig'i) | O'rta | Jarima, to'xtatish | pd.gov.uz reestr, rozilik, yurist ko'rik (shartlar, soliq, DM), 18:00–09:00 xabar yo'q |
| **Soʻrovnoma noxolisligi** (faqat Telegram faol odamlar) | Yuqori | PR da noto'g'ri xulosa | Hisobotda "vakillik emas" izohi, n va usul ochiq |
| **Moderatsiya yuki** (pasport/hujjat/eʼlon) | Yuqori | Navbat uzayadi, ishonch pasayadi | Tasdiqlash bonusi kuniga ≤ 40; AI avto-moderatsiya (REJA 4.3); tez tekshiruv kredit bilan |
| **Bola xavfsizligi hodisasi** (asosiy xavf, REJA 6.3) | Past, juda og'ir | Brend yo'q bo'lishi | Tasdiqlash targʻibi ustuvor, qo'llanma, shikoyatga 24 soat, "e'lon platformasi" pozitsiyasi; qo'rqitmaydigan kommunikatsiya |
| **bolambilan javobi** (bepul davr yoki chat) | O'rta | Raqobat | Viloyatlar, AI, chat, tezlik; 2-bosqichga tezroq o'tish |

---

## 12. Ochiq savollar (asoschi/yurist uchun)

1. Bonus dasturi soliq nuqtai nazaridan qanday qaraladi (ayniqsa elchilar va adminlar uchun)? **[yurist/buxgalter]**
2. Yangi mobil reklama qarori (1-noyabr 2026) Telegram shaxsiy xabarlarini qamrab oladimi? Qaror raqami va matnini lex.uz da topish kerak.
3. Kanal adminlarining narxi va rozilik darajasi (BOZOR_TAHLILI 6): haftada 10 ta suhbat bilan aniqlash.
4. Telegram `start` parametri 64 belgigacha — kodlar sxemasini tekshirish (7.1).
5. Narxlarni (TOP 25 000 va h.k.) 2-bosqichdan oldin soʻrovnomadagi N15/N16/P15/P16 bilan tasdiqlash.
6. Soʻrovnoma uchun alohida roziga yozma matn (pasport emas, lekin maxfiy fikrlar: zo'ravonlik/xavf tajribasi).

---

## Manbalar

- Telegram — [Spam FAQ](https://telegram.org/faq_spam), [EU DSA qoʻllanma](https://telegram.org/tos/eu-dsa), [Bots FAQ (limitlar)](https://core.telegram.org/bots/faq).
- Oʻzbekiston — [anhor.uz: reklama qoʻngʻiroqlari va SMS cheklovi (1.11.2026)](https://anhor.uz/news/v-uzbekistane-zapretyat-reklamnye-zvonki-i-sms-po-vecheram-i-nochyu/); [spot.uz — "закон о рекламе"](https://www.spot.uz/ru/tag/закон о рекламе/); [kun.uz — ZRU-1125](https://kun.uz/ru/news/2026/03/28/xraneniye-nekotoryx-personalnyx-dannyx-za-predelami-uzbekistana-razresheno); [spot.uz — adekvat davlatlar](https://www.spot.uz/ru/2026/08/04/personal-data-abroad/).
- ZRU-547 — [Mondaq](https://www.mondaq.com/data-protection/828474/uzbekistan-enacts-first-data-protection-law), [Legal500](https://www.legal500.com/developments/thought-leadership/personal-data-compliance-in-uzbekistan/), [DLA Piper](https://www.dlapiperdataprotection.com/?c=UZ&t=law), [ConsentStack](https://www.consentstack.io/regulations/uz-pdl) (ikkilamchi).
- Ichki hujjatlar — [REJA.md](REJA.md), [BOZOR_TAHLILI.md](BOZOR_TAHLILI.md) (raqobatchi narxlari, kanal obunachi soni), [brand/BRAND.md](brand/BRAND.md).
