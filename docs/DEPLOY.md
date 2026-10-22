# Production'ga chiqarish (enagalar.uz)

Bitta Contabo VPS, Docker Compose. Hamma narsa `deploy/` papkasida.

## Arxitektura

```
Internet -> Caddy (80/443, avtomatik HTTPS)
  /api/* /admin* /livewire* /broadcasting/* /filament* /sanctum/* /up -> app (php-fpm)
  /storage/*  -> faqat storage/app/public (statik)
  /app/*      -> reverb:8080 (websocket)
  qolgan hammasi -> nuxt:3000
Ichki tarmoq: app, queue, scheduler, reverb, nuxt, postgres, redis (portlari tashqariga ochilmagan)
```

Volume'lar: `pgdata`, `redisdata`, `storage` (Laravel storage/, pasport fayllari `storage/app/verification` ham shu yerda, veb orqali berilmaydi), `public`, `caddy_data`, `caddy_config`.

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

Tarqalishini tekshiring: `dig +short enagalar.uz`. DNS tayyor bo'lmasa Caddy sertifikat ololmaydi.

## 3. Serverni sozlash

```bash
ssh root@SERVER_IP
apt-get update && apt-get install -y git
git clone https://github.com/<akkaunt>/enagalar_uz.git /opt/enagalar_uz-tmp   # yoki scriptni scp bilan yuboring
bash /opt/enagalar_uz-tmp/deploy/scripts/server-setup.sh deploy "$(cat ~/.ssh/authorized_keys | head -1)"
```

Script: Docker, ufw (22/80/443), fail2ban, avtomatik yangilanishlar, 2 GB swap, `deploy` foydalanuvchisini yaratadi. Qayta ishga tushirsa ham xavfli emas.
Keyin **yangi oynada** `ssh deploy@SERVER_IP` bilan kirib ko'ring, ishlasa ish davom etadi.

## 4. Kodni olish va sozlash

```bash
cd ~
git clone https://github.com/<akkaunt>/enagalar_uz.git
cd enagalar_uz/deploy
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

`deploy.sh` image'larni quradi, hamma xizmatni ko'taradi, migratsiyani bajaradi, Telegram webhook o'rnatadi. Tekshirish: `https://enagalar.uz` va `https://enagalar.uz/up`.

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

```bash
mkdir -p ~/backups && chmod 700 ~/backups
crontab -e
```

Qo'shing:

```
15 3 * * * /home/deploy/enagalar_uz/deploy/scripts/backup.sh >> /home/deploy/backups/backup.log 2>&1
```

- Bazaning gzip dump'i va `storage` volume arxivi saqlanadi, 14 kundan eskilari o'chadi.
- Tashqi nusxa uchun: `rclone config` (remote nomi, masalan `eu-s3`), keyin `.env.production` da `RCLONE_REMOTE=eu-s3:enagalar-backups`. Remote **faqat EI/EU serverda** bo'lsin (ZRU-1125), arxivda pasport ma'lumotlari bor, shuning uchun `rclone crypt` tavsiya etiladi.
- Qo'lda sinab ko'ring: `./scripts/backup.sh`. Tiklash: `./scripts/restore.sh ~/backups/db-....sql.gz ~/backups/storage-....tar.gz`. Tiklashni kamida bir marta sinab ko'ring.

## 9. Shaxsiy ma'lumotlar bazasini ro'yxatdan o'tkazish

Eslatma: ma'lumotlar bazasini **pd.gov.uz** da davlat reestriga ro'yxatdan o'tkazish kerak (operator sifatida). Ishga tushirishdan oldin buni qiling va maxfiylik siyosatini saytda e'lon qiling.

## 10. Yangilash

```bash
cd ~/enagalar_uz
./deploy/scripts/deploy.sh
```

Script `git pull`, build, `up -d`, migratsiya, webhook ni bajaradi. Kutish: bir necha soniya, `app` sog'lom bo'lguncha. Orqaga qaytish: `git checkout <oldingi-commit> && ./deploy/scripts/deploy.sh --no-pull`.

## 11. Muammolarni topish

Hamma buyruq `deploy/` papkasida: `alias dc='docker compose --env-file .env.production -f docker-compose.prod.yml'`

```bash
dc ps                          # holat
dc logs -f --tail=100 app      # Laravel / php-fpm
dc logs -f caddy               # HTTPS, kirish loglari
dc logs -f queue reverb nuxt   # navbat, websocket, frontend
dc exec app php artisan about
dc exec app php artisan queue:failed
dc exec postgres psql -U enagalar enagalar
docker stats                   # RAM/CPU
```

Tez-tez uchraydigan holatlar:
- **Sertifikat olinmayapti**: DNS A yozuvi noto'g'ri yoki 80/443 yopiq (`sudo ufw status`). `dc logs caddy`.
- **500 xato**: `dc logs app`, `.env.production` o'zgargan bo'lsa `dc up -d --force-recreate app queue scheduler reverb`.
- **Websocket ulanmayapti**: `NUXT_PUBLIC_REVERB_*` va `REVERB_APP_KEY` mosligini tekshiring; frontend env o'zgargach `dc up -d --force-recreate nuxt`.
- **Admin CSS yuklanmayapti**: `dc exec app php artisan filament:assets`, `app` ni qayta ishga tushiring.
- **Disk to'ldi**: `docker system df`, `docker image prune -a`.
- **Texnik ishlar rejimi**: `dc exec app php artisan down` / `up`.
