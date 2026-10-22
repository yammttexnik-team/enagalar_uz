# enagalar.uz

Ota-onalar va enagalarni bog'laydigan platforma: tasdiqlangan enagalar, Telegramga o'xshash chat, AI yordamchi.

## Hujjatlar

- [Loyiha rejasi](docs/REJA.md)
- [Bozor tahlili](docs/BOZOR_TAHLILI.md)

## Tuzilma

| Papka | Texnologiya |
|---|---|
| `backend/` | Laravel 13 (REST API `/api/v1`, Sanctum, Spatie Permission) |
| `frontend/` | Nuxt 4 + Nuxt UI + i18n (uz, ru) |
| `docker-compose.yml` | PostgreSQL, Redis, Meilisearch, Mailpit |

## Lokal ishga tushirish

```bash
# Backend
cd backend
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve            # http://localhost:8000

# Frontend
cd frontend
pnpm install
pnpm dev                     # http://localhost:3000
```

PostgreSQL bilan ishlash uchun: `docker compose up -d`, so'ng `backend/.env` da `DB_CONNECTION=pgsql` va DB_* qiymatlarini oching.

## Testlar

```bash
cd backend && php artisan test && ./vendor/bin/pint --test
cd frontend && pnpm build
```

## Deploy

- [Production'ga chiqarish qo'llanmasi](docs/DEPLOY.md) (Docker Compose, bitta VPS)
- Fayllar: `deploy/` (Dockerfile'lar, `docker-compose.prod.yml`, `Caddyfile`, `.env.production.example`, `scripts/`)
