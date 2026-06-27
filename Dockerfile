# syntax=docker/dockerfile:1

# This Dockerfile is for LOCAL DEVELOPMENT ONLY (docker-compose.yml + Pest
# browser tests). Production no longer uses Docker — it deploys a CI-built
# artifact onto bare-metal PHP-FPM + nginx. See deploy/ for the production setup.

#######################################
# Stage: dev — hot-reload local dev image
#######################################
FROM php:8.5-cli-bookworm AS dev

# Node 24 — Debian Bookworm's apt ships Node 18 which is too old for Vite.
# npm/npx are symlinks in the source image; recreate them so __dirname resolves correctly.
COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Debian/glibc base (not Alpine) so Playwright's downloaded Chromium can run
# for Pest browser tests — Alpine/musl can't dynamically link glibc binaries.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libsqlite3-0 \
        libonig5 \
        libzip4 \
        git \
        unzip \
        libsqlite3-dev \
        libonig-dev \
        libzip-dev \
        libglib2.0-0 \
        libnspr4 \
        libnss3 \
        libatk1.0-0 \
        libatk-bridge2.0-0 \
        libdbus-1-3 \
        libcups2 \
        libxcb1 \
        libxkbcommon0 \
        libgbm1 \
        libx11-6 \
        libxext6 \
        libcairo2 \
        libpango-1.0-0 \
        libxcomposite1 \
        libxdamage1 \
        libxfixes3 \
        libxrandr2 \
        libatspi2.0-0 \
        libasound2 \
    && docker-php-ext-install pdo_sqlite pdo_mysql bcmath pcntl zip sockets \
    && apt-get purge -y libsqlite3-dev libonig-dev libzip-dev \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.dev.ini /usr/local/etc/php/conf.d/zz-fintrax.ini
COPY docker/entrypoint.dev.sh /usr/local/bin/entrypoint.dev.sh
RUN chmod +x /usr/local/bin/entrypoint.dev.sh

WORKDIR /var/www/html

ENTRYPOINT ["/usr/local/bin/entrypoint.dev.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
