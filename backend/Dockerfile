# Production API image (FrankenPHP + Laravel).
# MongoDB is not compiled here: the HTTP API uses PostgreSQL only.
# Run `php artisan import:ventes` from local/Herd or a worker with ext-mongodb.

FROM dunglas/frankenphp:1.12.2-php8.4

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensions required at runtime by the API (no ext-mongodb — avoids long/failed PECL builds on Dokploy).
RUN install-php-extensions pdo_pgsql gd zip exif ftp opcache @composer

RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 150M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_KEY=base64:dGVtcG9yYXJ5a2V5Zm9yZG9ja2VyYnVpbGQxMjM0NTY3ODkw

COPY composer.json composer.lock ./

# --no-scripts: artisan is not present yet. --ignore-platform-req: ext-mongodb only needed for import CLI.
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-req=ext-mongodb

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 755 storage bootstrap/cache

COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
