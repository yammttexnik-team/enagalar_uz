#!/usr/bin/env bash
# PostgreSQL dump (gzip) + storage volume arxivi. 14 kundan eskisi o'chiriladi. RCLONE_REMOTE bo'lsa, u yerga ham yuklanadi.
# Cron (deploy foydalanuvchisi, har kuni 03:15):
#   15 3 * * * /home/deploy/enagalar_uz/deploy/scripts/backup.sh >> /home/deploy/backups/backup.log 2>&1
# DIQQAT: arxivda pasport/verifikatsiya fayllari bor. Papka 700, fayllar 600; tashqi remote faqat EI/EU serverda va rclone crypt bilan.
set -euo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DEPLOY_DIR"
set -a; . ./.env.production; set +a

BACKUP_DIR="${BACKUP_DIR:-$HOME/backups}"
KEEP_DAYS="${BACKUP_KEEP_DAYS:-14}"
STAMP="$(date +%Y%m%d-%H%M%S)"
DC=(docker compose --env-file .env.production -f docker-compose.prod.yml)

umask 077
mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

DB_FILE="$BACKUP_DIR/db-$STAMP.sql.gz"
ST_FILE="$BACKUP_DIR/storage-$STAMP.tar.gz"

echo "[$(date -Is)] pg_dump -> $DB_FILE"
"${DC[@]}" exec -T postgres pg_dump -U "$DB_USERNAME" -d "$DB_DATABASE" --no-owner | gzip -9 > "$DB_FILE"
gzip -t "$DB_FILE"

echo "[$(date -Is)] storage volume -> $ST_FILE"
docker run --rm -v enagalar_storage:/data:ro -v "$BACKUP_DIR":/backup alpine \
  tar czf "/backup/storage-$STAMP.tar.gz" -C /data .
chmod 600 "$ST_FILE"

echo "[$(date -Is)] $KEEP_DAYS kundan eski nusxalarni o'chirish"
find "$BACKUP_DIR" -maxdepth 1 -type f \( -name 'db-*.sql.gz' -o -name 'storage-*.tar.gz' \) -mtime +"$KEEP_DAYS" -delete

if [ -n "${RCLONE_REMOTE:-}" ]; then
  echo "[$(date -Is)] rclone -> $RCLONE_REMOTE"
  rclone copy "$DB_FILE" "$RCLONE_REMOTE"
  rclone copy "$ST_FILE" "$RCLONE_REMOTE"
  rclone delete "$RCLONE_REMOTE" --min-age "${KEEP_DAYS}d"
fi

echo "[$(date -Is)] tayyor"
