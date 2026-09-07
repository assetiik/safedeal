FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl libzip-dev libsqlite3-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo_sqlite mbstring zip bcmath \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts -o

COPY . .

RUN mkdir -p database storage/framework/{cache,sessions,views} storage/logs storage/app/private/documents bootstrap/cache \
    && touch database/database.sqlite \
    && chmod -R ug+rwx storage bootstrap/cache database \
    && composer dump-autoload -o \
    && php artisan package:discover --ansi || true

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8001

ENTRYPOINT ["/entrypoint.sh"]
