# syntax=docker/dockerfile:1.7
# Laravel 13 (PHP 8.3-fpm-alpine). Bitta image: app, queue, scheduler, reverb.
# Build context: ../backend ; additional context "deploy": ./deploy (compose beradi).

# ---------- 1) Composer (prod bog'liqliklar) ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
    --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ---------- 2) Runtime ----------
FROM php:8.3-fpm-alpine AS runtime
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN apk add --no-cache fcgi tini \
 && install-php-extensions pdo_pgsql redis intl gd zip exif pcntl bcmath opcache

# php.ini (production) + opcache + upload limitlari (Caddy limiti 25MB bilan mos)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=deploy docker/php.ini "$PHP_INI_DIR/conf.d/99-app.ini"
# php-fpm to'liq non-root ishlaydi (user/group direktivalari olib tashlanadi), port 9000
RUN sed -i -E '/^(user|group) *=/d' /usr/local/etc/php-fpm.d/www.conf \
 && printf '[www]\nlisten = 9000\npm = dynamic\npm.max_children = 20\npm.start_servers = 4\npm.min_spare_servers = 2\npm.max_spare_servers = 6\npm.max_requests = 500\nclear_env = no\n' > /usr/local/etc/php-fpm.d/zz-app.conf

WORKDIR /var/www/html
COPY --chown=www-data:www-data --from=vendor /app /var/www/html
COPY --from=deploy docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# public/ ning nusxasi: app konteyner ishga tushganda umumiy "public" volume'ga sinxronlanadi (Caddy statik fayllarni o'sha yerdan beradi)
RUN chmod +x /usr/local/bin/entrypoint.sh \
 && cp -R public /opt/public-dist \
 && mkdir -p storage/app/public storage/app/verification storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache public /opt/public-dist \
 && chmod 700 storage/app/verification

USER www-data
# paketlar manifestini oldindan yaratish (xato bo'lsa ishga tushganda o'zi yaratadi)
RUN php artisan package:discover --ansi || true
EXPOSE 9000
ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "--nodaemonize"]
