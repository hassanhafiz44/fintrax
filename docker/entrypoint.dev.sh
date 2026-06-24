#!/bin/sh
set -e

cd /var/www/html

LOCK=/var/www/html/storage/.dev-bootstrap.lock

# Only one container (whichever grabs the lock dir first) runs the
# composer/npm install — concurrent installs into the shared named
# volumes corrupt each other's package downloads.
if mkdir "$LOCK" 2>/dev/null; then
    trap 'rmdir "$LOCK" 2>/dev/null' EXIT

    if [ ! -f .env ]; then
        cp .env.example .env
    fi

    if [ -z "$(ls -A vendor 2>/dev/null)" ]; then
        composer install --no-interaction --prefer-dist --ignore-platform-req=ext-sockets
    fi

    if [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
        npm install
    fi

    if ! grep -q '^APP_KEY=base64' .env; then
        php artisan key:generate --force
    fi

    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/app/public storage/logs bootstrap/cache database

    if [ ! -f database/database.sqlite ]; then
        touch database/database.sqlite
    fi

    php artisan migrate --force

    if [ ! -L public/storage ]; then
        php artisan storage:link
    fi

    rmdir "$LOCK" 2>/dev/null
    trap - EXIT
else
    # Another container is bootstrapping — wait for it to finish.
    while [ -d "$LOCK" ]; do
        sleep 1
    done
fi

exec "$@"
