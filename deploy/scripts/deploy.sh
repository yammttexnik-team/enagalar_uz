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

# Deploy natijasi Telegram'ga: .env.production da DEPLOY_NOTIFY_CHAT_ID bo'lsa (bot shu chatga yozadi)
STARTED_AT=$(date +%s)
REVISION="$(cat "$REPO_DIR/REVISION" 2>/dev/null | cut -c1-7 || true)"
notify() {
  local token chat
  token="$(grep -E '^TELEGRAM_BOT_TOKEN=' .env.production | cut -d= -f2- || true)"
  chat="$(grep -E '^DEPLOY_NOTIFY_CHAT_ID=' .env.production | cut -d= -f2- || true)"
  [ -n "$token" ] && [ -n "$chat" ] || return 0
  curl -s -m 10 -o /dev/null "https://api.telegram.org/bot${token}/sendMessage" \
    --data-urlencode "chat_id=${chat}" --data-urlencode "text=$1" || true
}
trap 'notify "❌ enagalar deploy yiqildi${REVISION:+ ($REVISION)}, $(( $(date +%s) - STARTED_AT ))s. Loglar: Actions → Deploy"' ERR

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

# Caddyfile bitta fayl sifatida mount qilingan: rsync uni yangi inode bilan almashtiradi,
# ishlab turgan konteyner esa eskisini ko'radi. O'zgargan bo'lsa Caddy qayta ishga tushiriladi.
CADDY_SUM="$(sha256sum Caddyfile | cut -d' ' -f1)"
if [ "$(cat .caddyfile.sha 2>/dev/null || true)" != "$CADDY_SUM" ]; then
  echo "==> Caddyfile o'zgargan: caddy qayta ishga tushirilmoqda"
  "${DC[@]}" restart caddy
  echo "$CADDY_SUM" > .caddyfile.sha
fi

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
notify "✅ enagalar deploy tayyor${REVISION:+ ($REVISION)}, $(( $(date +%s) - STARTED_AT ))s"
echo "Tayyor."
