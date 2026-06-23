#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/app/public storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi
chown www-data:www-data database database/database.sqlite

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force --graceful

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

exec "$@"
