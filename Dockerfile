# syntax=docker/dockerfile:1
FROM php:8.3-fpm-alpine AS base

# Runtime-библиотеки остаются в образе; build-зависимости удаляются после компиляции.
RUN apk add --no-cache \
        libmemcached \
        zlib \
        icu-libs \
        oniguruma \
        sqlite-libs \
 && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libmemcached-dev \
        zlib-dev \
        icu-dev \
        oniguruma-dev \
        sqlite-dev \
 && docker-php-ext-install -j"$(nproc)" pdo pdo_sqlite pdo_mysql mbstring intl opcache \
 && pecl install memcached redis \
 && docker-php-ext-enable memcached redis \
 && apk del .build-deps \
 && rm -rf /tmp/pear /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ── Development ─────────────────────────────────────────────────────────────
FROM base AS development

COPY docker/php/php.ini     $PHP_INI_DIR/conf.d/99-app.ini
COPY docker/php/php-dev.ini $PHP_INI_DIR/conf.d/99-app-dev.ini

# В dev-режиме исходники монтируются через volume — composer install запускается вручную.
EXPOSE 9000
CMD ["php-fpm"]

# ── Production ───────────────────────────────────────────────────────────────
FROM base AS production

COPY docker/php/php.ini $PHP_INI_DIR/conf.d/99-app.ini

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
 && chown -R www-data:www-data private/storage

EXPOSE 9000
CMD ["php-fpm"]
