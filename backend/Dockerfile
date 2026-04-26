FROM dunglas/frankenphp:latest-php8.4

# Install git to allow Composer source fallback.
RUN apt-get update && apt-get install -y git && rm -rf /var/lib/apt/lists/*

# PHP extensions for this Laravel app.
RUN install-php-extensions pdo_pgsql mongodb gd zip exif ftp opcache @composer

# PHP upload and memory limits.
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 150M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 3G" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction || \
    (sleep 5 && composer install --no-dev --optimize-autoloader --no-interaction)

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 755 /app/storage /app/bootstrap/cache

COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
