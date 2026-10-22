#!/bin/sh
# Laravel konteyner kirish skripti.
# RUN_BOOTSTRAP=true bo'lgan yagona konteyner (app) migratsiya va keshlarni bajaradi;
# queue / scheduler / reverb faqat kataloglarni tayyorlab, buyruqni ishga tushiradi.
set -eu
cd /var/www/html

mkdir -p storage/app/public storage/app/verification storage/framework/cache/data \
  storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod 700 storage/app/verification 2>/dev/null || true

if [ "${RUN_BOOTSTRAP:-false}" = "true" ]; then
  echo "[entrypoint] public/ ni umumiy volume'ga sinxronlash"
  cp -R /opt/public-dist/. public/

  echo "[entrypoint] migratsiya"
  php artisan migrate --force --no-interaction
  # Seeder'lar idempotent: tuzilmaviy ma'lumot (viloyat, rol) yangilanadi; admin tahrirlaydigan
  # ma'lumot (nishon, ko'nikma, kampaniya, so'rovnoma) firstOrCreate: faqat yetishmaydigani qo'shiladi.
  php artisan db:seed --force --no-interaction

  echo "[entrypoint] keshlar"
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache || true
  php artisan filament:assets || true
  php artisan filament:optimize || true

  # public disk uchun symlink (Caddy /storage/* ni to'g'ridan-to'g'ri storage volume'dan beradi, bu zaxira)
  php artisan storage:link --force || true
fi

exec "$@"
