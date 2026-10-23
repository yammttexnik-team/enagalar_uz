# Production'ga chiqarish (enagalar.uz)

Bitta Contabo VPS, Docker Compose. Hamma narsa `deploy/` papkasida.

> **Server umumiy (40+ boshqa sayt).** Mavjud Nginx, firewall, PHP va tizim paketlariga tegilmaydi.
> enagalar faqat Docker (`127.0.0.1:8095`) va o'zining Nginx sayt fayli (`enagalar-<domen>`) orqali ishlaydi.

## 0. GitHub Actions orqali (tavsiya etiladi)

Serverni qo'lda sozlash shart emas: GitHub serverga SSH orqali o'zi ulanadi.

**1. Secret'larni qo'shing:** GitHub → repo → **Settings → Secrets and variables → Actions → New repository secret**.

| Nomi | Qiymati |
|---|---|
| `SERVER_HOST` | Server IP manzili |
| `SERVER_HOST_KEY` | Ixtiyoriy, lekin tavsiya etiladi: serverning `known_hosts` qatori (pastdagi "Xavfsizlik sozlamalari" bo'limi) |
| `DEPLOY_SSH_KEY` | Deploy uchun SSH maxfiy kaliti (ed25519, to'liq: BEGIN…END qatorlari bilan) |
| `TELEGRAM_BOT_TOKEN` | @BotFather bergan token |
| `ANTHROPIC_API_KEY` | Claude API kaliti |
| `ADMIN_EMAIL` | Admin panelga kirish emaili |

**2. DNS:** `enagalar.uz` va `www.enagalar.uz` uchun A yozuvini server IP'siga yo'naltiring (2-bo'lim).

**3. Server sozlash va admin:** root paroli GitHub'da saqlanmaydi (2026-10-23 dan). Server SSH kalit bilan qo'lda sozlanadi: `deploy/scripts/server-setup.sh` (Docker, `deploy` foydalanuvchisi), so'ng `php artisan admin:create ... --password-stdin`.

**4. Nginx sayt fayli:** SSH orqali qo'lda: `deploy/nginx/enagalar.conf.template` dan `/etc/nginx/sites-available/enagalar-<domen>` yaratiladi, `nginx -t` o'tsa reload, keyin `certbot --nginx -d <domen> -d www.<domen> --redirect`. Boshqa saytlarga tegilmaydi.

**5. Keyingi yangilanishlar:** **Actions → Deploy → Run workflow**. Avtomatik deploy `main` branch'dagi push'dan keyingi CI muvaffaqiyatidan so'ng ishlaydi, lekin faqat shu repo'dagi `push` hodisasi uchun (fork va pull request'dan emas). `main` branch 2026-10-22 dan beri bor, avtomatik deploy yoqilgan.

### Boshqa workflow'lar

| Workflow | Vazifasi |
|---|---|
| **Set domain** | `.env.production` dagi domenni almashtiradi (masalan, vaqtinchalik `161-97-88-95.sslip.io` dan `enagalar.uz` ga) va qayta deploy qiladi. Kodni rsync qilmaydi, kod o'zgarishi uchun avval **Deploy** ishlating. |
| **Server exec** | Diagnostika buyruqlari (`$DC ps`, `$DC logs --tail 100 app`). Qarang: pastdagi ogohlantirish. |

### Xavfsizlik sozlamalari (bir marta qiling)

- **`production` environment'ga reviewers qo'shing** (Settings → Environments → production → Required reviewers). **Server exec** serverda ixtiyoriy buyruq bajaradi; reviewer talabisiz write huquqi bor har kim uni ishga tushira oladi. Workflow'da `env`/`printenv`/`.env.production`/`cat .env` kabi buyruqlarni rad etuvchi taxminiy himoya bor, lekin u asosiy to'siq emas.
- **SSH host kaliti (`SERVER_HOST_KEY`).** Secret bo'lmasa workflow'lar `ssh-keyscan` ishlatadi (birinchi ulanishga ishonish, TOFU: tarmoqdagi hujumchi soxta serverni ko'rsatishi mumkin). Himoya uchun o'z kompyuteringizdan: `ssh-keyscan -H SERVER_IP`, chiqqan qator(lar)ni serverning haqiqiy barmoq izi bilan solishtirib, to'liq `SERVER_HOST_KEY` secret'iga joylang. Secret bor bo'lsa, workflow'lar uni `known_hosts` sifatida ishlatadi.
- Workflow'larda `permissions: contents: read` bor va uchinchi tomon action'lar (`shivammathur/setup-php`, `pnpm/action-setup`) to'liq commit SHA'ga mahkamlangan. Yangilash uchun: `git ls-remote https://github.com/OWNER/REPO refs/tags/TAG refs/tags/TAG^{}` (annotated tag bo'lsa `^{}` dagi SHA olinadi).

Pastdagi qo'lda bajariladigan bo'limlar shu workflow ichida avtomatik bajariladi; ular muammo bo'lganda tushunish uchun qoldirildi.

## Arxitektura

```
Internet -> host Nginx (80/443, TLS: certbot, X-Forwarded-For = $remote_addr)
  -> 127.0.0.1:8095 -> Caddy (faqat ichki HTTP, auto_https off)
  /api/* /admin* /livewire* /broadcasting/* /filament* /sanctum/* /up -> app (php-fpm)
  /storage/*  -> faqat storage/app/public (statik)
  /app/*      -> reverb:8080 (websocket)
  qolgan hammasi -> nuxt:3000
Ichki tarmoq: app, queue, scheduler, reverb, nuxt, postgres, redis (portlari tashqariga ochilmagan)
```

Volume'lar: `pgdata`, `redisdata`, `storage` (Laravel storage/, pasport fayllari `storage/app/verification` ham shu yerda, veb orqali berilmaydi), `public`, `caddy_data`, `caddy_config`.

Caddy tashqariga to'g'ridan-to'g'ri chiqmaydi: sertifikatni Caddy emas, host Nginx + certbot (4-qadam) oladi.

## 1. Server buyurtma qilish

1. contabo.com da **Cloud VPS** oling. **Region: Germany (EU)**. Shaxsiy ma'lumotlar to'g'risidagi qonun (ZRU-1125) talabi bo'yicha server ishonchli (adequacy) ro'yxatdagi mamlakatda bo'lishi kerak. AQSh yoki Singapur regionini tanlamang.
2. Tavsiya: **Cloud VPS 10** (4 vCPU, 8 GB RAM) boshlanishiga yetadi; foydalanuvchilar ko'payganda **Cloud VPS 20** (6 vCPU, 12 GB). RAM kamida 4 GB bo'lsin (build paytida kerak), disk 75 GB+.
3. OS: **Ubuntu 24.04**. SSH kalit qo'shing (yoki parol, keyin kalitga o'tasiz).

## 2. DNS

`.uz` registratorida (yoki DNS provayderda) A yozuvlar:

| Nom | Tur | Qiymat |
|---|---|---|
| `enagalar.uz` | A | server IPv4 |
| `www.enagalar.uz` | A | server IPv4 |

Tarqalishini tekshiring: `dig +short enagalar.uz`. DNS tayyor bo'lmasa certbot (4-qadam) sertifikat ololmaydi.

## 3. Serverni sozlash (qo'lda; odatda kerak emas, workflow bajaradi)

```bash
ssh root@SERVER_IP
apt-get update && apt-get install -y git
git clone https://github.com/<akkaunt>/enagalar_uz.git /opt/enagalar_uz-tmp   # yoki scriptni scp bilan yuboring
bash /opt/enagalar_uz-tmp/deploy/scripts/server-setup.sh deploy "$(cat ~/.ssh/authorized_keys | head -1)"
```

Script (oddiy rejim): Docker va `deploy` foydalanuvchisini o'rnatadi. Qayta ishga tushirsa ham xavfli emas. **Faqat bo'sh, alohida serverda** `FULL_SETUP=1 bash server-setup.sh ...` bilan ufw (22/80/443), fail2ban, `apt upgrade`, avtomatik yangilanishlar va 2 GB swap ham o'rnatiladi. Umumiy serverda `FULL_SETUP=1` ishlatmang.
Keyin **yangi oynada** `ssh deploy@SERVER_IP` bilan kirib ko'ring, ishlasa ish davom etadi.

## 4. Kodni olish va sozlash

Serverda git yo'q: kod GitHub Actions orqali **rsync** bilan `~/enagalar_uz/` ga yuboriladi. Qo'lda sozlashda ham kodni kompyuterdan yuboring: `rsync -az --exclude .git --exclude node_modules --exclude vendor ./ deploy@SERVER_IP:enagalar_uz/`.

```bash
cd ~/enagalar_uz/deploy
cp .env.production.example .env.production
chmod 600 .env.production
nano .env.production
```

To'ldirish kerak:
- `ACME_EMAIL`, `DB_PASSWORD`, `REDIS_PASSWORD`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `TELEGRAM_WEBHOOK_SECRET`: tasodifiy parollar (`openssl rand -hex 24`).
- `APP_KEY`: `docker compose --env-file .env.production -f docker-compose.prod.yml run --rm --no-deps --entrypoint "" app php artisan key:generate --show` (birinchi build'dan keyin; avval 5-bandni qarang) yoki mahalliy kompyuterda `php artisan key:generate --show`.
- `TELEGRAM_BOT_TOKEN` (@BotFather), `ANTHROPIC_API_KEY` (bo'sh qolsa AI qoidalarga o'tadi).
- `APP_KEY` ni yo'qotmang: shifrlangan ma'lumotlar (pasport) uni bilan ochiladi. Zaxira nusxasini xavfsiz joyda saqlang.

## 5. Birinchi deploy

```bash
cd ~/enagalar_uz/deploy
docker compose --env-file .env.production -f docker-compose.prod.yml build    # 5-10 daqiqa
# APP_KEY hali yo'q bo'lsa shu yerda yarating va .env.production ga yozing:
docker compose --env-file .env.production -f docker-compose.prod.yml run --rm --no-deps --entrypoint "" app php artisan key:generate --show
./scripts/deploy.sh --no-pull
```

`deploy.sh` build'dan oldin joriy image'larni `:prev` deb belgilaydi, so'ng image'larni quradi, hamma xizmatni ko'taradi, migratsiyani bajaradi, Telegram webhook o'rnatadi. Tekshirish: serverda `curl http://127.0.0.1:8095/up`; Nginx site tayyor bo'lgach `https://enagalar.uz/up`.

## 6. Admin yaratish

```bash
cd ~/enagalar_uz/deploy
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan admin:create email@example.com "Ism Familiya"
```

Parolni so'raydi (kamida 10 belgi). Kirish: `https://enagalar.uz/admin`. Moderator uchun `--moderator` qo'shing.

## 7. Telegram webhook

`deploy.sh` har safar buni o'zi bajaradi. Qo'lda:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan telegram:set-webhook
```

Javobda `"ok":true` bo'lishi kerak. Webhook manzili: `https://enagalar.uz/api/v1/telegram/webhook`.

## 8. Backup (cron)

Cron avtomatik o'rnatilmaydi. `deploy` foydalanuvchisi bilan bir marta qo'lda o'rnating:

```bash
mkdir -p ~/backups && chmod 700 ~/backups
chmod +x ~/enagalar_uz/deploy/scripts/backup.sh
( crontab -l 2>/dev/null; echo '15 3 * * * /home/deploy/enagalar_uz/deploy/scripts/backup.sh >> /home/deploy/backups/backup.log 2>&1' ) | crontab -
crontab -l   # tekshirish
```

- Bazaning gzip dump'i va `storage` volume arxivi saqlanadi, 14 kundan eskilari o'chadi.
- Tashqi nusxa uchun: `rclone config` (remote nomi, masalan `eu-s3`), keyin `.env.production` da `RCLONE_REMOTE=eu-s3:enagalar-backups`. Remote **faqat ishonchli (EU) mamlakat serverida** bo'lsin (ZRU-1125). Arxivda pasport ma'lumotlari bor, shuning uchun tashqi nusxa **`rclone crypt` remote** orqali yuborilsin (`rclone config` da `crypt` turidagi remote yarating: fayl nomlari va mazmuni shifrlanadi), masalan `RCLONE_REMOTE=eu-crypt:enagalar-backups`. Crypt parollarini (`password`, `password2`) serverdan tashqarida ham saqlang, aks holda nusxani ochib bo'lmaydi.
- Qo'lda sinab ko'ring: `./scripts/backup.sh`. Tiklash: `./scripts/restore.sh ~/backups/db-....sql.gz ~/backups/storage-....tar.gz`. Tiklashni kamida bir marta sinab ko'ring.

## 9. Shaxsiy ma'lumotlar bazasini ro'yxatdan o'tkazish

Eslatma: ma'lumotlar bazasini **pd.gov.uz** da davlat reestriga ro'yxatdan o'tkazish kerak (operator sifatida). Ishga tushirishdan oldin buni qiling va maxfiylik siyosatini saytda e'lon qiling.

## 10. Yangilash

Odatiy yo'l: **Actions → Deploy → Run workflow**. U kodni serverga `rsync -az --delete` bilan yuboradi (serverda git repo yo'q) va `deploy.sh --no-pull` ni ishga tushiradi. `deploy.sh` build'dan oldin joriy image'larni `enagalar/backend:prev` va `enagalar/frontend:prev` deb belgilaydi (`docker image prune` ularga tegmaydi), so'ng build, `up -d`, migratsiya va webhook ni bajaradi. Kutish: bir necha soniya, `app` sog'lom bo'lguncha.

### Orqaga qaytish (rollback)

Serverda git yo'qligi uchun `git pull`/`git checkout` ishlamaydi. Ikki yo'l:

1. **Tez: oldingi image'ga qaytish.** Server exec yoki SSH orqali:
   ```bash
   cd ~/enagalar_uz/deploy
   docker tag enagalar/backend:prev enagalar/backend:latest
   docker tag enagalar/frontend:prev enagalar/frontend:latest
   docker compose --env-file .env.production -f docker-compose.prod.yml up -d --no-build --force-recreate app queue scheduler reverb nuxt
   ```
   Eslatma: `:prev` faqat bitta oldingi deploy'ni saqlaydi. Migratsiya orqaga qaytmaydi; yangi versiya sxemani buzgan bo'lsa, bazani backup'dan tiklang (8-bo'lim).
2. **Eski commit'ni qayta deploy qilish:** lokal repo'da kerakli commit'dan branch yarating, push qiling va **Deploy** ni `workflow_dispatch` bilan shu branch'dan ishga tushiring.

## 11. Muammolarni topish

Hamma buyruq `deploy/` papkasida: `alias dc='docker compose --env-file .env.production -f docker-compose.prod.yml'`

```bash
dc ps                          # holat
dc logs -f --tail=100 app      # Laravel / php-fpm
dc logs -f caddy               # ichki HTTP, kirish loglari
dc logs -f queue reverb nuxt   # navbat, websocket, frontend
dc exec app php artisan about
dc exec app php artisan queue:failed
dc exec postgres psql -U enagalar enagalar
docker stats                   # RAM/CPU
```

Tez-tez uchraydigan holatlar:
- **Sertifikat olinmayapti**: DNS A yozuvi noto'g'ri yoki 80/443 yopiq. certbot chiqishini tekshiring (Let's Encrypt limiti, masalan sslip.io uchun, tugagan bo'lishi mumkin: u holda sayt HTTP orqali ishlaydi). Caddy sertifikat olmaydi (`auto_https off`).
- **500 xato**: `dc logs app`, `.env.production` o'zgargan bo'lsa `dc up -d --force-recreate app queue scheduler reverb`.
- **Websocket ulanmayapti**: `NUXT_PUBLIC_REVERB_*` va `REVERB_APP_KEY` mosligini tekshiring; frontend env o'zgargach `dc up -d --force-recreate nuxt`.
- **Admin CSS yuklanmayapti**: `dc exec app php artisan filament:assets`, `app` ni qayta ishga tushiring.
- **Disk to'ldi**: `docker system df`, `docker image prune -a`.
- **Texnik ishlar rejimi**: `dc exec app php artisan down` / `up`.
