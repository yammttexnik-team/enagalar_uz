#!/usr/bin/env bash
# Tiklash. Ishlatish: ./restore.sh <db-*.sql.gz> [storage-*.tar.gz]
# DIQQAT: mavjud bazani O'CHIRIB, dump bilan almashtiradi.
set -euo pipefail

DB_FILE="${1:-}"
ST_FILE="${2:-}"
[ -f "$DB_FILE" ] || { echo "Ishlatish: $0 <db-*.sql.gz> [storage-*.tar.gz]"; exit 1; }
[ -z "$ST_FILE" ] || [ -f "$ST_FILE" ] || { echo "Storage arxivi topilmadi: $ST_FILE"; exit 1; }

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DEPLOY_DIR"
set -a; . ./.env.production; set +a
DC=(docker compose --env-file .env.production -f docker-compose.prod.yml)

echo "Baza '$DB_DATABASE' o'chiriladi va $DB_FILE bilan almashtiriladi."
read -r -p "Davom etamizmi? 'ha' deb yozing: " ANSWER
[ "$ANSWER" = "ha" ] || { echo "Bekor qilindi"; exit 1; }

echo "==> ilova xizmatlarini to'xtatish"
"${DC[@]}" stop caddy queue scheduler reverb app
"${DC[@]}" up -d postgres

echo "==> bazani qayta yaratish"
"${DC[@]}" exec -T postgres psql -U "$DB_USERNAME" -d postgres -v ON_ERROR_STOP=1 \
  -c "DROP DATABASE IF EXISTS \"$DB_DATABASE\" WITH (FORCE);" \
  -c "CREATE DATABASE \"$DB_DATABASE\" OWNER \"$DB_USERNAME\";"
gunzip -c "$DB_FILE" | "${DC[@]}" exec -T postgres psql -U "$DB_USERNAME" -d "$DB_DATABASE" -v ON_ERROR_STOP=1 -q

if [ -n "$ST_FILE" ]; then
  echo "==> storage volume tiklash"
  ABS_ST="$(readlink -f "$ST_FILE")"
  docker run --rm -v enagalar_storage:/data -v "$(dirname "$ABS_ST")":/backup:ro alpine \
    sh -c "rm -rf /data/* /data/.[!.]* 2>/dev/null; tar xzf /backup/$(basename "$ABS_ST") -C /data && chown -R 82:82 /data"
fi

echo "==> xizmatlarni ishga tushirish"
"${DC[@]}" up -d --wait --wait-timeout 300
echo "Tiklandi."
