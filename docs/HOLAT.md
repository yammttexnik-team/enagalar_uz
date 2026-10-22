# enagalar.uz — loyiha holati va keyingi ishlar

> Oxirgi yangilanish: 2026-10-22, kechqurun.
> Branch: `main` (push → CI → avtomatik deploy).
> Yangi sessiyada avval shu faylni va `CLAUDE.md` ni o'qing.

---

## 0. 2026-10-22 kechki yangilanish

- **Sayt ochildi:** `https://161-97-88-95.sslip.io` (Nginx sayt fayli `enagalar-161-97-88-95.sslip.io`, certbot SSL). Admin: `admin@enagalar.uz`, parol serverda `/root/enagalar-admin.txt`.
- **Xavfsizlik tuzatishlari:**
  - Telegram login endi bot yuborgan 6 xonali kod bilan tasdiqlanadi.
  - Bloklangan foydalanuvchi API'ga kira olmaydi, uning tokenlari o'chiriladi.
  - Moderator rollar, nishonlar va bonuslarni o'zgartira olmaydi.
- **Bonus:**
  - So'rovnoma faqat o'z auditoriyasi uchun.
  - Referal bonusi e'lon bonusi bilan birga bekor qilinadi.
  - Limitlar qator qulfi (row lock) bilan himoyalangan.
  - Pasport tasdig'i uchun 15 000, hujjat uchun 5 000 bonus beriladi.
- **API:**
  - Rate limitlar qo'yildi.
  - Ko'rishlar soni bir foydalanuvchidan 6 soatda 1 marta sanaladi.
  - Ikki foydalanuvchi o'rtasida takroriy suhbat ochilmaydi (`pair_key`).
  - Yangi endpoint: `GET /my/vacancies/{id}`.
  - Enaga anketasi tahrirlangach qayta moderatsiyaga tushadi.
  - Otklik holatlari qat'iy tartibda o'zgaradi.
  - `verification:prune` 30 kundan keyin pasport raqami va PINFL'ni o'chiradi.
- **AI:** "Matnni joylash" funksiyasi (`POST /ai/parse-listing`) anketa va vakansiya formalarida ishlaydi.
- **Frontend:**
  - Logout'da oldingi foydalanuvchi ma'lumotlari tozalanadi.
  - `lang` va hreflang to'g'ri qo'yiladi.
  - Mavjud bo'lmagan profil va e'lon 404 qaytaradi, 500 emas.
- **Deploy/CI:**
  - Fork PR'lardan deploy bo'lmaydi.
  - Workflow'larda `permissions` cheklangan, tashqi action'lar SHA bilan qotirilgan.
  - Ixtiyoriy `SERVER_HOST_KEY` secret'i qo'shildi.
  - Oldingi image `:prev` sifatida saqlanadi (rollback uchun).
  - Seeder'lar admin o'zgarishlarini qayta yozib yubormaydi.
- **Qolgan ishlar:**
  - `enagalar.uz` domeni hali NXDOMAIN. Faollashgach **Set domain** va **Nginx site** workflow'larini ishga tushirish kerak.
  - Presence kanali hali umumiy.
  - Sharh/reyting API'si yo'q.

## 1. Qisqa xulosa

- **Server ishlamoqda va sayt ochiq** (0-bo'limga qarang).
- Barcha Docker konteynerlari serverda sog'lom: app, queue, scheduler, reverb, nuxt, postgres, redis va caddy.
- Domen (`enagalar.uz`) faollashishi 2026-10-23 da kutilmoqda. Ungacha vaqtinchalik `161-97-88-95.sslip.io` ishlatiladi.
- Ertaga (2026-10-23) marketing kampaniyasi boshlanishi rejalashtirilgan: `docs/MARKETING_REJA.md` A qismi.

---

## 2. Qilingan ishlar

### Hujjatlar
| Fayl | Mazmuni |
|---|---|
| `docs/BOZOR_TAHLILI.md` | Raqobatchilar (bolambilan, homecare, Telegram kanallar), narxlar, xalqaro tajriba, qonunchilik (ZRU-1125) |
| `docs/REJA.md` | Loyiha rejasi, rollar, funksiyalar, texnologiya, bosqichlar |
| `docs/MARKETING_REJA.md` | Bonus asosidagi marketing: ertangi soatma-soat reja, 7 kunlik sprint, UZ/RU shablonlar, so'rovnoma savollari, xavflar |
| `docs/brand/BRAND.md` | Brend: "Quchoq" logotipi, ranglar (firuza va o'rik), shriftlar (Mulish + Inter), ohang |
| `docs/DEPLOY.md` | Deploy qo'llanmasi (GitHub Actions bo'limi ham bor) |
| `docs/design/screenshots/` | Barcha sahifalarning skrinshotlari |

### Backend (Laravel 13, `backend/`), 179 ta test
- **Kirish:** Telegram bot orqali (`@enagalaruz_bot`).
  - Sayt token yaratadi, bot telefon raqamni so'raydi, sayt so'rov yuborib (polling) Sanctum token oladi.
  - Haqiqiy bot bilan sinab ko'rilgan.
- **Rollar va profillar:** ota-ona, enaga, moderator, admin. Bitta foydalanuvchida ikki rol bo'lishi mumkin.
- **Ikki tomonlama e'lonlar:** `/nannies` va `/vacancies` qidiruvi (filtrlar bilan), vakansiya CRUD (moderatsiyaga tushadi, bir vaqtda 5 tagacha), enaga anketasi, otklik, sevimlilar.
- **Maxfiylik:** ochiq API'da ism "Dilnoza K." ko'rinishida chiqadi. Telefon, Telegram va pasport ma'lumotlari hech qachon ochiq qaytmaydi.
- **Tasdiqlash:**
  - Pasport ma'lumotlari shifrlangan holda saqlanadi, rasmi yopiq diskda turadi va tekshiruvdan keyin o'chiriladi.
  - Hujjatlar: tibbiy ma'lumotnoma, dispanser ma'lumotnomasi, sertifikat. Roziliklar yozib boriladi.
- **Nishonlar tizimi:** admin paneldan boshqariladi.
- **AI matching:** Claude Haiku 5.5 ikki yo'nalishda ishlaydi, AI'ga anonim ma'lumot yuboriladi.
  - Javob 6 soat keshda saqlanadi, kunlik limit bor. AI ishlamasa, qoidalarga asoslangan saralash ishlaydi.
  - Haqiqiy API bilan sinab ko'rilgan.
- **Chat (Laravel Reverb):** matn, rasm va ovozli xabar; reply, tahrirlash, o'chirish; o'qildi belgisi, "yozmoqda…", onlayn holat; bloklash va shikoyat.
  - Oflayn foydalanuvchiga Telegram bildirishnoma yuboriladi.
- **Bonus va marketing:**
  - kampaniyalar (14 ta tayyor kod);
  - bonus hamyoni (ledger, 9 oy muddat, 200 000 tavan);
  - so'rovnomalar (enaga uchun 25, ota-ona uchun 26 savol, avtomatik 50 000 bonus, firibgarlikka qarshi tekshiruv);
  - e'lon bonusi 30 000, referal 15 000 + 15 000.
- **Admin panel (Filament, `/admin`), o'zbekcha:**
  - moderatsiya: pasport, hujjatlar, vakansiyalar, shikoyatlar;
  - foydalanuvchilar va enaga anketalari;
  - nishonlar, kampaniyalar, so'rovnomalar (CSV eksport), bonuslar daftari.
- **Telegram:** `telegram:poll` (lokal sinov uchun) va `telegram:set-webhook`.

### Frontend (Nuxt 4, `frontend/`)
- **Bosh sahifa va brend:** brendlangan bosh sahifa, qorong'i rejim, UZ/RU.
- **Qidiruv:** `/enagalar` va `/vakansiyalar`; filtrlar URL'da saqlanadi, SEO sozlangan.
- **Kirish:** `/kirish` (Telegram) va `/boshlash` (rol tanlash).
- **Kabinet:** ota-ona (vakansiyalar, javoblar, AI tanlovi), enaga (4 qadamli anketa, mos vakansiyalar, javoblarim), tasdiqlash markazi, sevimlilar, **xabarlar (Telegramga o'xshash chat)**, **bonuslar**.
- **So'rovnoma:** `/sorovnoma` va `/sorovnoma/[slug]`.
- **Kampaniya va referal:** `?c=KOD` va `?r=KOD` 30 kunlik cookie'da saqlanadi.

### Infratuzilma
- **GitHub Actions workflow'lari** (`.github/workflows/`):
  - `ci.yml`: barcha branch'larda test, pint va frontend build;
  - `server-setup.yml`: bir martalik sozlash (root paroli bilan);
  - `deploy.yml`: rsync va `deploy.sh`;
  - `set-domain.yml`: `.env.production` dagi domenni almashtirish;
  - `nginx-site.yml`: mavjud Nginx'ga alohida sayt fayli va certbot;
  - `server-exec.yml`: diagnostika buyruqlari.
- **Docker Compose** (`deploy/docker-compose.prod.yml`): Caddy faqat `127.0.0.1:8095` da tinglaydi. Tashqi 80/443 portlarni serverdagi mavjud Nginx boshqaradi.
- **GitHub secret'lari qo'shilgan:** `SERVER_HOST`, `SERVER_ROOT_PASSWORD`, `DEPLOY_SSH_KEY`, `TELEGRAM_BOT_TOKEN`, `ANTHROPIC_API_KEY`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`.

---

## 3. Server — MUHIM

Server (Contabo) **umumiy**: unda 40 dan ortiq boshqa sayt ishlaydi (Nginx, PHP-FPM, pm2/Node). Boshqa saytlarga ta'sir qilish **mumkin emas**.

### Nima bo'ldi va tuzatildi
- **Birinchi `server-setup` (2026-10-22, 20:35–20:48, server vaqti) quyidagilarni qildi:**
  - `apt-get upgrade`: PHP paketlarining patch-versiyalari yangilandi;
  - Docker o'rnatildi;
  - **ufw firewall yoqildi** (faqat 22/80/443), fail2ban, swap 2G qo'shildi;
  - `deploy` foydalanuvchisi yaratildi.
- **ufw o'chirildi va fail2ban o'chirildi**, ya'ni oldingi holat tiklandi. Oldin ufw o'chiq edi.
- **Docker va swap qoldirildi:** ular enagalar uchun kerak, boshqa saytlarga ta'sir qilmaydi.
- **`server-setup.sh` endi xavfsiz:** firewall, upgrade, swap va fail2ban faqat `FULL_SETUP=1` bilan ishlaydi.
- **Saytlar tekshiruvi:** ko'pchiligi 200/302 qaytaradi. **503 qaytaradiganlar:** bolalarolami.uz, admin/new-admin.bolalarolami.uz, protsess.uz.
  - Bu bizning ishimizga bog'liqligi aniqlanmadi, ular avvaldan "texnik ishlar" rejimida bo'lishi mumkin.
  - **Egasidan tekshirish so'ralsin.**
- **Nginx'ga hali hech narsa qo'shilmagan va qayta yuklanmagan.**

### Konteynerlar
`deploy` foydalanuvchisi, `~/enagalar_uz/deploy`, compose loyiha nomi `enagalar`. Diagnostika: **Actions → Server exec**, masalan `$DC ps`, `$DC logs --tail 100 app`.

---

## 4. Qilinishi kerak bo'lgan ishlar (navbat bo'yicha)

### A. Saytni ochish (birinchi navbatda)
1. **Set domain** natijasini tekshirish. U `161-97-88-95.sslip.io` bilan ishga tushirilgan edi.
   - Agar Telegram webhook qadamida yiqilgan bo'lsa: `deploy.sh` tuzatildi (webhook xatosi endi deploy'ni to'xtatmaydi). **Deploy** ni qayta ishga tushiring, u yangi `entrypoint.sh` ni ham olib keladi (`db:seed` har ishga tushishda).
   - Set domain kodni rsync qilmaydi, shuning uchun kod o'zgarishlari uchun avval **Deploy** ishga tushiriladi.
2. **Nginx site** workflow'ini `domain = 161-97-88-95.sslip.io` bilan ishga tushirish.
   - U `/etc/nginx/sites-available/enagalar-<domen>` faylini yaratadi. `nginx -t` o'tmasa, faylni o'chiradi va Nginx qayta yuklanmaydi. Keyin certbot ishlaydi.
   - Shablon lokal `nginx -t` dan o'tgan.
   - sslip.io uchun Let's Encrypt limiti tugagan bo'lishi mumkin. U holda sayt HTTP orqali ishlaydi.
3. Tekshirish: `https://161-97-88-95.sslip.io/`, `/admin`, `/up`, Telegram orqali kirish (webhook endi ochiq domenga ulanadi).
4. **Admin yaratish.** `server-setup` ning admin qadami hali bajarilmagan. Ikki yo'l bor:
   - **Server setup** ni `run_server_setup = false` bilan qayta ishga tushirish (kodni yuboradi, deploy qiladi va admin yaratadi);
   - yoki Server exec: `printf '%s' 'PAROL' | $DC exec -T app php artisan admin:create admin@enagalar.uz Admin --password-stdin` (parolni `ADMIN_PASSWORD` secret'idan oling).
5. **Domen faollashganda** (`enagalar.uz` hozir NXDOMAIN, 2026-10-23 da kutilmoqda; whois.uz da tekshiring): **Set domain** `enagalar.uz` bilan, keyin **Nginx site** `enagalar.uz` bilan. DNS yozuvlari tayyor: `@ A 161.97.88.95`, `www CNAME enagalar.uz`.
   - Eslatma: `mail` va MX yozuvlari ham yangi serverga yo'naltirilgan. Pochta eski hostingda bo'lsa, `mail` ni 185.196.212.52 ga qaytarish kerak.

### B. Ertangi marketing uchun (`docs/MARKETING_REJA.md`)
1. **"Telegram e'lonini tashlang, AI e'lon tayyorlaydi"** funksiyasi yo'q, lekin shablonlarda va'da qilingan (2-variant).
   - Kerak: enaga anketasi va vakansiya formasida "Matnni joylash" tugmasi bo'lsin. Claude Haiku orqali matndan maydonlar to'ldirilsin, foydalanuvchi tasdiqlaydi.
2. Shablonlardagi havolalar `enagalar.uz` ga yozilgan. Domen ishlamasa, `161-97-88-95.sslip.io` ga almashtirish kerak.
3. Admin panelda kampaniyalarni tekshirish (14 ta kod seed qilinadi) va so'rovnomani bir marta to'liq o'tib ko'rish.
4. Kampaniya uchun alohida Telegram akkaunt (rejadagi A1). Ommaviy yoki avtomatik DM yuborilmaydi.

### C. Keyingi funksiyalar
- Pasport tasdig'i uchun bonus (15 000) va hujjat uchun bonus (+5 000), sharh uchun bonus. Bular A0 jadvalida bor, lekin kodlanmagan.
- Sharh va reyting tizimi: jadval bor, API va UI yo'q.
- So'rovnoma javoblarini AI bilan tekshirish (hozir faqat qoidalar asosida).
- Telegram kanallarga yangi e'lonlarni avtomatik joylash.
- Viloyat va tuman bo'yicha SEO sahifalar (`/enagalar/[region]`).
- `GET /my/vacancies/{id}` endpointi (hozir frontend ro'yxat ichidan qidiradi).
- MyID integratsiyasi (2-bosqich), to'lovlar (Payme, Click, Uzum), mobil ilova.

### D. Xavfsizlik va tozalash
- Server **root paroli** Telegram'dagi "key" kanalida va GitHub secret'ida turibdi. Server ishga tushgach:
  - root parolini almashtiring va `SERVER_ROOT_PASSWORD` secret'ini yangilang yoki o'chiring;
  - "key" kanalidagi xabarlarni o'chiring.
- Telegram akkauntida **ikki bosqichli tekshiruvni qayta yoqing** (u ulanish uchun o'chirilgan edi).
- **Yuridik ishlar:** pd.gov.uz da ma'lumotlar bazasini ro'yxatdan o'tkazish; maxfiylik siyosati va oferta matnlari (yurist ko'rib chiqishi kerak).
- `main` branch yaratish (yoki PR). Deploy workflow `main` ga push'dan keyin avtomatik ishlaydi, hozir faqat qo'lda ishga tushiriladi.

---

## 5. Foydali buyruqlar va joylar

| Nima | Qayerda |
|---|---|
| Server diagnostikasi | Actions → **Server exec** (`$DC ps`, `$DC logs --tail 100 app`, `$DC exec -T app php artisan ...`) |
| Deploy | Actions → **Deploy** |
| Domen almashtirish | Actions → **Set domain** |
| Nginx sayt fayli | Actions → **Nginx site** |
| Lokal ishga tushirish | `README.md` |
| Lokal bot sinovi | `cd backend && php artisan telegram:poll` |
| Bonus sozlamalari | `backend/config/bonus.php` (`BONUS_*` env), admin → Marketing |
| Kampaniya havolalari | `https://<domen>/sorovnoma?c=KOD`, `https://<domen>/kirish?c=KOD`, `https://t.me/enagalaruz_bot?start=c_KOD` |

## 6. Ish uslubi (`CLAUDE.md` dan)
- Foydalanuvchi o'zbek tilida audio yuboradi, javoblar o'zbek tilida (lotin) yoziladi.
- Oddiy ishlar subagentlarga Sonnet yoki Haiku bilan beriladi, asosiy model natijani tekshiradi.
- Serverda boshqa saytlarga tegilmaydi. Tizim darajasidagi har qanday o'zgarishdan oldin foydalanuvchidan so'raladi.
