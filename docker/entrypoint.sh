#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/app/public storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-database/database.sqlite}"
    mkdir -p "$(dirname "$DB_PATH")"
    if [ ! -f "$DB_PATH" ]; then
        touch "$DB_PATH"
    fi
    chown www-data:www-data "$(dirname "$DB_PATH")" "$DB_PATH"
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force --graceful

php artisan db:seed --class=DemoSeeder --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

exec "$@"
