FROM dunglas/frankenphp:1.12.2-php8.4

# Install git and build dependencies to allow Composer source fallback.
RUN apt-get update && apt-get install -y \
    git \
    build-essential \
    autoconf \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions for this Laravel app.
RUN install-php-extensions pdo_pgsql mongodb gd zip exif ftp opcache

# PHP upload and memory limits.
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 150M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 3G" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

COPY composer.json composer.lock ./

# Pre-install dependencies to ensure they work before copying full app
RUN --mount=type=cache,target=/root/.composer composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader 2>&1 || { echo "First composer install failed, retrying..."; sleep 5; composer install --no-dev --no-interaction --no-scripts --optimize-autoloader; }

COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN --mount=type=cache,target=/root/.composer composer install \
    --no-dev \
    --no-interaction \
    --optimize-autoloader 2>&1

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 755 /app/storage /app/bootstrap/cache

COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
