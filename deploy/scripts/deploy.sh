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

# Orqaga qaytish uchun: build'dan oldin joriy image'larni :prev deb belgilaymiz
# (teg bo'lgani uchun `docker image prune` ularga tegmaydi). Rollback: docs/DEPLOY.md.
echo "==> joriy image'larni :prev deb belgilash"
for img in $("${DC[@]}" config --images 2>/dev/null | sort -u); do
  case "$img" in
    enagalar/*)
      if docker image inspect "$img" >/dev/null 2>&1; then
        docker tag "$img" "${img%%:*}:prev" && echo "  $img -> ${img%%:*}:prev"
      fi
      ;;
  esac
done

echo "==> build"
"${DC[@]}" build

echo "==> up -d (app healthy bo'lguncha kutiladi; migratsiya entrypoint'da)"
"${DC[@]}" up -d --remove-orphans --wait --wait-timeout 300

echo "==> migratsiya (xavfsizlik uchun yana bir bor)"
"${DC[@]}" exec -T app php artisan migrate --force --no-interaction

if grep -qE '^TELEGRAM_BOT_TOKEN=.+' .env.production; then
  echo "==> Telegram webhook"
  # Domen hali ishlamasa webhook o'rnatilmaydi, lekin deploy to'xtamasligi kerak
  "${DC[@]}" exec -T app php artisan telegram:set-webhook || echo "OGOHLANTIRISH: webhook o'rnatilmadi (domen/HTTPS tayyormi?)"
else
  echo "TELEGRAM_BOT_TOKEN bo'sh, webhook o'tkazib yuborildi"
fi

echo "==> eski image'larni tozalash"
docker image prune -f >/dev/null

"${DC[@]}" ps
echo "Tayyor."
