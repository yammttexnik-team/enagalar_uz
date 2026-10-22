#!/usr/bin/env bash
# Yangilash: git pull, build, up, migratsiya, Telegram webhook. Server'da, deploy foydalanuvchisi bilan.
# Ishlatish: ./deploy/scripts/deploy.sh [--no-pull]
set -euo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPO_DIR="$(dirname "$DEPLOY_DIR")"
cd "$DEPLOY_DIR"

[ -f .env.production ] || { echo "deploy/.env.production topilmadi (.env.production.example dan nusxa oling)"; exit 1; }
if grep -qE '^(APP_KEY=$|.*CHANGE_ME)' .env.production; then
  echo "XATO: .env.production da APP_KEY bo'sh yoki CHANGE_ME qiymatlari qolgan"; exit 1
fi

DC=(docker compose --env-file .env.production -f docker-compose.prod.yml)

if [ "${1:-}" != "--no-pull" ]; then
  echo "==> git pull"
  git -C "$REPO_DIR" pull --ff-only
fi

echo "==> build"
"${DC[@]}" build

echo "==> up -d (app healthy bo'lguncha kutiladi; migratsiya entrypoint'da)"
"${DC[@]}" up -d --remove-orphans --wait --wait-timeout 300

echo "==> migratsiya (xavfsizlik uchun yana bir bor)"
"${DC[@]}" exec -T app php artisan migrate --force --no-interaction

if grep -qE '^TELEGRAM_BOT_TOKEN=.+' .env.production; then
  echo "==> Telegram webhook"
  "${DC[@]}" exec -T app php artisan telegram:set-webhook
else
  echo "TELEGRAM_BOT_TOKEN bo'sh, webhook o'tkazib yuborildi"
fi

echo "==> eski image'larni tozalash"
docker image prune -f >/dev/null

"${DC[@]}" ps
echo "Tayyor."
