# syntax=docker/dockerfile:1

#######################################
# Stage: vendor — install PHP deps
#######################################
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

#######################################
# Stage: assets — build frontend (Vite)
#######################################
FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

#######################################
# Stage: app — final PHP-FPM runtime
#######################################
FROM php:8.5-fpm-alpine AS app

RUN apk add --no-cache \
        sqlite-libs \
        oniguruma \
        libzip \
        tini \
    && apk add --no-cache --virtual .build-deps \
        sqlite-dev \
        oniguruma-dev \
        libzip-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_sqlite pdo_mysql bcmath pcntl zip \
    && apk del .build-deps

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-fintrax.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/zz-fintrax.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN mkdir -p database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database

ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

#######################################
# Stage: web — nginx serving public/
#######################################
FROM nginx:alpine AS web

COPY --from=app /var/www/html/public /var/www/html/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

#######################################
# Stage: dev — hot-reload local dev image
#######################################
FROM php:8.5-cli-alpine AS dev

RUN apk add --no-cache \
        sqlite-libs \
        oniguruma \
        libzip \
        nodejs \
        npm \
        git \
        unzip \
    && apk add --no-cache --virtual .build-deps \
        sqlite-dev \
        oniguruma-dev \
        libzip-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_sqlite pdo_mysql bcmath pcntl zip \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-fintrax.ini
COPY docker/entrypoint.dev.sh /usr/local/bin/entrypoint.dev.sh
RUN chmod +x /usr/local/bin/entrypoint.dev.sh

WORKDIR /var/www/html

ENTRYPOINT ["/usr/local/bin/entrypoint.dev.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
