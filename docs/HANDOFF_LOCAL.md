# HANDOFF → lokal sessiya (2026-11-05)

Bulut sessiyasi shu yerda tugadi. Ish foydalanuvchi kompyuteridagi lokal Claude Code sessiyasida davom etadi.
Bu fayl `handoff/local` branchida turadi. **main ga merge qilmang, faqat kerakli qismlarni oling**
(media/ papkasi 270+ MB, u faqat ish materiali, saytga kerak emas). Asosiy holat fayli: `docs/HOLAT.md`.

---

## 1. Loyiha holati (qisqa)

- Sayt: https://enagalar.uz, ishlayapti. Backend Laravel 13 (`backend/`), frontend Nuxt 4 (`frontend/`), admin `/admin` (Filament).
- `main` dagi oxirgi kod commiti: `36b47dd` (shu branch undan boshlangan). Testlar 341/341, pint toza, `pnpm build` o'tadi.
- Real foydalanuvchilar hali kam (24-oktabr: 6 ta). Asosiy kontent: Telegram kanallaridan import qilingan e'lonlar (~85–100 ta, AI tarjima bilan).
- Biznes yo'nalishi (Fable tavsiyasi): **avval taklif tomoni — Toshkentda 30 ta real enaga anketasi**, keyin ota-onalar reklamasi.

## 2. Bugun (5-noyabr) qilingan ishlar

| Ish | Joyi |
|---|---|
| Dizayn: halol bosh sahifa (jonli e'lonlar), mobil "E'lon joylash" paneli, natijalar birinchi, import karta v2, e'lon sahifasi, kirish 3 qadam | `frontend/app/components/home,imported,layout`, `pages/` |
| AI tarjima: import e'lonlar uz/ru (`title_uz/ru`, `description_uz/ru`), `imports:translate` har soat :40 | `ListingImporter`, `ListingTranslator`, `ImportedListingResource` |
| AI maqolalar: 24 mavzu, `articles:generate` har kuni 11:00, `/maqolalar` | `app/Services/Content/*`, `pages/maqolalar` |
| Narxlar: `/narxlar`, `GET /api/v1/prices` (6 soat kesh) | `app/Services/PriceStats.php`, `pages/narxlar` |
| "Qayerda qaraladi" filtri (`care_location`: family_home / nanny_home) | enum `CareLocation`, filtrlar, formalar, importer |
| Telegram Login vidjeti (kodsiz kirish), `POST /auth/telegram/widget` | `TelegramAuthController::widget`, `components/auth/TelegramWidget.client.vue` |
| Bot xabarlari: `notify:matches` 18:30, `notify:nudges` 12:00, `/stop` `/notify` | `app/Services/Telegram/{UserNotifier,MatchDigest}.php` |
| Kanal avtopost: dayjest avtomatik (`TELEGRAM_DIGEST_AUTO` default true), maqola posti, `channel:prices` dushanba 10:00 | `ChannelPoster`, `ChannelDigest` |
| Sitemap, robots, www→301 (Caddy), SSR ichki API + mijoz IP | `SitemapController`, `deploy/Caddyfile`, `stores/auth.ts` |
| Xavfsizlik: import o'chirish faqat egasi/moderator, qora ro'yxat, firibgarlik filtri, token redaksiya | (24-oktabr commitlari) |
| Mini-serial "ENAGA KERAK!" konsepsiya + 5 qism + 1-qism Veo paketi | `docs/series/` |
| Reklama videolari (uz/ru, 4:5, qisqa) | `media/video/` (shu branch) |

## 3. Ochiq vazifalar (navbat bilan)

1. **Avtomatik ishlarni tekshirish** (5-bo'lim) — birinchi kunlik ishlar 5-noyabr kechqurun boshlangan.
2. **Mini-serial 1-qism:** qolgan kliplar C02, C03, C05, C06, C07, C10, C11, C12 (Flow), keyin montaj (7-bo'lim).
3. **Import e'lonlarga "Shikoyat qilish" tugmasi** — serialning 4-qismida ko'rsatiladi, hozir yo'q (faqat o'chirish so'rovi).
4. **Reklama videolari:** `parents.mp4` 26 s (maqsad ~19 s) — VO matnini qisqartirish; ruscha videolar true peak −1,3/−1,4 (−1,5 chegarasidan biroz yuqori) — qayta miks.
5. **`start=claim_<id>` deep link** (e'lon egasi bir bosishda kirib tasdiqlaydi) — kirish tartibini o'zgartiradi, foydalanuvchi tasdig'i kerak.
6. **Admin → Import manbalari:** `@enaga_ish` aslida `@nyanya_toshkent` bilan bir kanal — ortiqchasini o'chirish (AI tokeni ikki marta sarflanyapti).
7. **Yuridik (foydalanuvchi):** pd.gov.uz ro'yxatdan o'tish, Maxfiylik siyosati va Oferta, Telegram import bo'yicha yurist.
8. **Xavfsizlik (foydalanuvchi):** Telegram 2FA, server root paroli almashtirish, "key" Telegram kanalidagi sirlarni tozalash,
   chatga yozilgan ElevenLabs kalit ID'sini rotate qilish; GitHub `production` environment'ga reviewer + faqat `main`.
9. Log vaqt zonalari: `schedule-*.log` Toshkent, `laravel-*.log` UTC (kichik, shoshilinch emas).

## 4. Qabul qilingan qarorlar va sabablari

| Qaror | Sabab |
|---|---|
| Saytda "tekshirilgan/tasdiqlangan enagalar" da'vosi yo'q | Real tasdiqlangan anketa yo'q; halollik ishonchni oshiradi |
| Import e'lonni anonim o'chirish so'rovi → moderatorga; egasi (kontakt mos) → darhol | Aks holda istalgan odam hamma e'lonni o'chira olardi |
| Telegram Login vidjeti asosiy, bot+kod zaxira | Kamroq qadam; vidjet telefon bermaydi, telefon bot orqali |
| Bot+kod usulida token yolg'iz kirishga yetmaydi | Havolani boshqaga yuborib akkauntga kirishning oldini olish |
| Maqolalar avtomatik e'lon qilinadi (draft emas) | Qo'lda ish bo'lmasligi kerak; admin panelda tahrirlash/o'chirish mumkin |
| Kanal dayjesti admin tasdig'isiz | Foydalanuvchi talabi: qo'lda ish yo'q |
| O'zbekcha VO: Davronbek + `eleven_v3` (language_code'siz) | `eleven_multilingual_v2` o'zbek tilini qo'llamaydi → aksent; v3 da aksent kamaydi (foydalanuvchi tasdiqladi) |
| Veo'da dialog ovozi yo'q, VO ElevenLabs bilan | Veo o'zbekcha nutqni yomon qiladi |
| Bola yuzi kadrda yo'q | Etika va Veo bolani noto'g'ri yoshda chizadi |
| Server umumiy (40+ sayt) | Nginx/firewall/PHP/tizim paketlariga tegilmaydi (CLAUDE.md) |

## 5. Tekshirilishi kerak bo'lgan avtomatik ishlar

Hammasi `backend/routes/console.php` da, `scheduler` konteynerida. Natija: serverda `storage/logs/schedule-YYYY-MM-DD.log`.

| Buyruq | Vaqt (Toshkent) | Nimani tekshirish |
|---|---|---|
| `imports:fetch` | 08–22, har 2 soat | yangi/yangilangan e'lonlar soni |
| `imports:translate` | har soat :40 | `untranslated` = 0 |
| `imports:check` | 09:30, 18:30 | arxivlanganlar |
| `articles:generate` | 11:00 | har kuni 1 ta yangi maqola + kanal posti |
| `notify:nudges` | 12:00 | eslatmalar, 403 xatolar |
| `notify:matches` | 18:30 | yuborilgan xabarlar soni, `notify_telegram=false` bo'lganlar |
| `channel:digest` | 19:00 | kanalda post chiqdimi (`channel_posts.status=sent`) |
| `channel:prices` | dushanba 10:00 | narx posti yoki `skipped` |
| `bonus:expire`, `verification:prune`, `connections:ask` | 08:00 / 08:30 / har soat :15 | xato yo'qligi |

Tekshirish buyrug'i (GitHub Actions → **Server exec**, input `command`):
```
$DC exec -T app sh -c 'tail -80 storage/logs/schedule-$(TZ=Asia/Tashkent date +%F).log; grep -c XATO storage/logs/schedule-*.log | tail -3'
```

## 6. Server va GitHub Actions bilan ishlash

- Server: Contabo, umumiy (40+ sayt). enagalar faqat Docker'da: `127.0.0.1:8095` (Caddy) ← host Nginx (o'z sayt fayli) + certbot.
  Kod `~/enagalar_uz` (foydalanuvchi `deploy`), compose: `deploy/docker-compose.prod.yml`, project `enagalar`.
- Workflow'lar (`.github/workflows/`):
  - `ci.yml` — har push'da testlar + build.
  - `deploy.yml` — **main**'da CI o'tsa avtomatik deploy (rsync + `deploy/scripts/deploy.sh`). Shuning uchun main'ga push = saytga chiqish.
  - `server-exec.yml` — qo'lda: serverda buyruq (`$DC` = docker compose ...). Sirlarni chiqaradigan buyruqlar (env, printenv, .env.production, config, inspect) rad etiladi.
  - `set-domain.yml` — domen sozlash.
- GitHub secrets (nomlari): `SERVER_HOST`, `DEPLOY_SSH_KEY`, `SERVER_HOST_KEY` (ixtiyoriy). Qiymatlari faqat GitHub → Settings → Secrets'da.
- Server sirlari: serverdagi `~/enagalar_uz/deploy/.env.production` (repoda emas). Asosiy nomlar: `APP_KEY`, `DB_PASSWORD`,
  `REDIS_PASSWORD`, `REVERB_APP_SECRET`, `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET`, `ANTHROPIC_API_KEY`,
  ixtiyoriy: `TELEGRAM_DIGEST_AUTO`, `TELEGRAM_CHANNEL_ID`, `TELEGRAM_ADMIN_CHAT_ID`, `ANTHROPIC_CONTENT_MODEL`.
- Admin paroli: serverda `/root/enagalar-admin.txt` (HOLAT.md).
- Deploy app konteynerini qayta ishga tushiradi — uzoq ishlaydigan Server exec buyrug'i (masalan `articles:generate --count=3`) paytida main'ga push qilmang.
- Lokal tekshiruv: `cd backend && php artisan test && ./vendor/bin/pint --test`; `cd frontend && pnpm build`.

## 7. Serial va reklama videolari

### Reklama videolari — `media/video/final/`
| Fayl | Format | Davom. | Ovoz |
|---|---|---|---|
| parents.mp4 | 1080×1920 | 26,0 s | Davronbek (uz) |
| parents_short.mp4 | 1080×1920 | 11,0 s | Davronbek |
| nannies.mp4 | 1080×1920 | 18,6 s | Davronbek |
| nannies_4x5.mp4 | 1080×1350 | 18,6 s | Davronbek (Telegram uchun) |
| nannies_short.mp4 | 1080×1920 | 10,8 s | Davronbek |
| parents_ru.mp4 / nannies_ru.mp4 | 1080×1920 | 25,7 / 17,8 s | Elena (ru) |
| nannies_poster.png | 1080×1350 | — | Telegram statik post |

Barchasi −14 LUFS. Zaxira: `media/video/backup/` (`*_asilbek_v2.mp4` — eski uz ovoz; `*_v1.mp4` — birinchi versiya).
Ssenariylar: `media/video/scenario-*.md`.

### Video loyihasi — `media/video/project_v2/`
- Sahnalar: HTML/JS (`assets/*.js`, har video papkasida `index.html`, `cues.json`), shriftlar Mulish/Inter (`*.woff2`).
- `tools/render.py` — Playwright bilan HTML → kadrlar → silent MP4 (chunk'lab). `tools/render_all.sh <papka> <scene.html>` fon rejimida render.
- `tools/sfx.py` — `cues.json` dan UI tovushlarini sintez qiladi. `tools/sheet.py` — kadrlar varag'i (tekshiruv uchun).
- `tools/mix.py <video_nomi>` — parts → `video_silent.mp4`, VO (vo/v3/wav uz, vo/wav ru) joylash, SFX ducking, ikki bosqichli loudnorm −14 LUFS.
  VO vaqtlari `mix.py` ichidagi `VO` lug'atida.
- `tools/finalize.sh` — eski (v1) yakunlash skripti.
- `vo/v3/gen3.py segs.json` — o'zbekcha VO (Davronbek, eleven_v3); `vo/gen.py` — eski (multilingual_v2) va ruscha.
  `parts/` papkalari repoga kiritilmagan (qayta render qilinadi); `video_silent.mp4` bor — faqat ovozni qayta miks qilish uchun yetadi.
- Talab: Python 3, `pip install playwright` + `playwright install chromium`, ffmpeg.

### Mini-serial "ENAGA KERAK!" — `docs/series/`
- `concept.md`, `ep01.md`…`ep05.md` (Fable), `veo/characters.md`, `veo/ep01_shots.md`, `veo/ep01_edit.md`, `veo/flow_rules.md` (sinov qoidalari).
- 1-qism: qabul qilingan kliplar C01, C04 (3-variant), C08 (2-variant, Nilufar_night), C09 (zaxira prompt). Navbatda: C02, C03, C05, C06, C07, C10, C11, C12.
- Flow: Veo 3.1 Fast 9:16 x1 = 20 kredit; 910 kredit qolgan edi.

### Montaj qanday qilinadi (serial)
1. Kliplarni `ep01_edit.md` jadvali bo'yicha tartiblang, trim qiling (ffmpeg yoki istalgan muharrir).
2. C04/C01: 15–20% crop bilan yaqinlashtirish; C08/C10: ko'k tun rangi + vignette (qorong'ilik montajda).
3. Dialoglar: ElevenLabs (8-bo'lim), har qator alohida fayl, `silencedetect` bilan kesish, kerak bo'lsa 1,05–1,1× tezlashtirish.
4. Telefon ekranlari (S01–S06): enagalar.uz ning haqiqiy skrinkastlari (Playwright bilan yozish mumkin), bo'sh "phone plate" ustiga.
5. Subtitrlar (o'zbekcha, katta, xavfsiz zonada y < 1560), musiqa past, bola ovozi SFX (nafas/kulgi).
6. Yakun: −14 LUFS, TP ≤ −1,5, 1080×1920 H.264 + AAC; oxirida klifhenger muzlatma + end card.

## 8. Ovozlar (ElevenLabs)

- Kalit: bulut sessiyasida tarmoq proksisi orqali berilgan edi (qiymat hech qayerda yozilmagan). **Lokal sessiyada** o'z shell'ingizda
  `ELEVENLABS_API_KEY` o'zgaruvchisini o'rnating (repoga va chatga yozmang); `vo/gen.py` va `vo/v3/gen3.py` shu o'zgaruvchini o'qiydi.
- Tarif: starter, 40 000 belgi/oy, ~2 700 sarflangan (5-noyabr). Limitni `GET /v1/user/subscription` bilan ko'ring.

### Tanlangan o'zbekcha diktor (foydalanuvchi tasdiqlagan)
- **Davronbek** — `voice_id: agoC45V78njTIxYzwmSb`
- Model: **`eleven_v3`**, `language_code` **yuborilmaydi** ('uz'/'uzb' rad etiladi, til avtomatik aniqlanadi)
- Sozlama: `voice_settings: {"stability": 0.5}`, `output_format=mp3_44100_128`
- Matn: apostrof oddiy `'` ("O'zbekiston", "e'lon"), domen "enagalar nuqta uz" deb yoziladi.
- Ishlov: boshi/oxiridagi jimlik kesiladi, 1,08× tezlashtirish.

### Boshqa ovozlar
- Ruscha: **Elena – Calm & Friendly** `6uI7rxtNHAo4TLzDgyqr` (`eleven_multilingual_v2`).
- Rad etilganlar (aksent): Asilbek `fytsqG6MLzC2aCSw7MLa`, Mirzabek, Mirzaev, Sarhad, Firdavs (multilingual_v2 da). Namunalar: `media/voice/`.
- Kutubxonada o'zbekcha **ayol** ovozi yo'q (`/v1/shared-voices?language=uz&gender=female` bo'sh).

### Serial qahramonlari ovozi rejasi
| Qahramon | Ovoz tavsifi | Reja |
|---|---|---|
| Nilufar (28–30) | tez, ruscha so'z aralash, iliq | Voice Design (eleven_v3) yoki real ayol ovozidan Instant Voice Clone |
| Mahbuba opa (55–60) | past, qattiq, o'qituvchi ohangi | Voice Design / IVC |
| Gulnora opa (40–45) | sokin, aniq, yengil Farg'ona ohangi | Voice Design / IVC |
| Sardor (30–32) | yumshoq, kinoyali | Davronbek yoki Mirzabek (eleven_v3) |
| Dilnoza, "Zulfiya", Akmal aka | qisqa qatorlar | Voice Design |
| Imron (bola) | faqat SFX | — |

Ayol ovozlari uchun eng ishonchli yo'l: tanish o'zbek ayolning 1–2 daqiqalik toza yozuvidan **Instant Voice Clone**
(starter tarifda bor), so'ng `eleven_v3` bilan. Har ovozni avval 1 qatorda sinab, foydalanuvchiga eshittiring.

## 9. 100 MB dan katta fayllar

Repoga hech qanday 100 MB+ fayl qo'shilmagan (eng kattasi ~34 MB, `media/video/backup/nannies_v1.mp4`).
Kiritilmaganlar (kerak emas yoki qayta yaratiladi): `parts/` render bo'laklari, `check*/`, `review/` kadrlar, `*.log`,
SFX `*.wav` stemlari, earshot klon (https://github.com/Leonxlnx/earshot — o'rnatish bulutda bloklangan edi).
