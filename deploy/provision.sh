#!/usr/bin/env bash
#
# One-time bare-metal provisioning for fintrax (run once on the server as a
# sudo-capable user). Idempotent — safe to re-run. Production no longer uses
# Docker; the deploy workflow ships a CI-built artifact into /opt/fintrax/releases
# and flips the /opt/fintrax/current symlink.
#
# Usage:  sudo DEPLOY_USER=<your-ssh-deploy-user> bash deploy/provision.sh
#
set -euo pipefail

BASE=/opt/fintrax
DEPLOY_USER="${DEPLOY_USER:-deploy}"
PHP=8.5
HERE="$(cd "$(dirname "$0")" && pwd)"

echo "==> Installing PHP ${PHP} + extensions"
# PHP 8.5 isn't in Ubuntu's default repos — use the ondrej/php PPA.
# (pcntl is built into php${PHP}-cli, so it has no separate package.)
apt-get install -y --no-install-recommends software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y --no-install-recommends \
    php${PHP}-fpm php${PHP}-cli php${PHP}-mysql php${PHP}-sqlite3 \
    php${PHP}-bcmath php${PHP}-zip php${PHP}-mbstring php${PHP}-curl \
    php${PHP}-xml php${PHP}-intl rsync

echo "==> Installing PHP-FPM pool + ini"
install -m 644 "$HERE/php-fpm/fintrax.conf"  /etc/php/${PHP}/fpm/pool.d/fintrax.conf
install -m 644 "$HERE/php-fpm/zz-fintrax.ini" /etc/php/${PHP}/fpm/conf.d/zz-fintrax.ini
systemctl restart php${PHP}-fpm

echo "==> Creating directory layout under ${BASE}"
mkdir -p "$BASE/releases" "$BASE/_incoming" \
         "$BASE/shared/storage/framework/cache" \
         "$BASE/shared/storage/framework/sessions" \
         "$BASE/shared/storage/framework/views" \
         "$BASE/shared/storage/app/public" \
         "$BASE/shared/storage/logs"

echo "==> Seeding shared/.env (edit it before first deploy!)"
if [ ! -f "$BASE/shared/.env" ]; then
    cp "$HERE/.env.production.example" "$BASE/shared/.env"
    echo "    !! Set APP_KEY (php artisan key:generate --show) in $BASE/shared/.env"
    echo "    !! For MySQL/Postgres: set DB_* in $BASE/shared/.env (DB_HOST=127.0.0.1)"
fi

# SQLite only: create the persistent db file the release will symlink to.
# MySQL/Postgres deployments leave shared/database absent (DB is external).
if grep -q '^DB_CONNECTION=sqlite' "$BASE/shared/.env"; then
    mkdir -p "$BASE/shared/database"
    [ -f "$BASE/shared/database/database.sqlite" ] || touch "$BASE/shared/database/database.sqlite"
    chmod 664 "$BASE/shared/database/database.sqlite"
fi

# The deploy user owns the tree (rsync/artisan run as them) and is added to the
# www-data group; shared/ is group-writable + setgid so files written by either
# the deploy user (during deploy) or www-data (at runtime) stay group-accessible.
usermod -aG www-data "$DEPLOY_USER" || true
chown -R "$DEPLOY_USER":www-data "$BASE"
find "$BASE/shared" -type d -exec chmod 2775 {} +

echo "==> Installing queue worker systemd unit"
install -m 644 "$HERE/fintrax-queue.service" /etc/systemd/system/fintrax-queue.service
systemctl daemon-reload
systemctl enable fintrax-queue   # started after the first successful deploy

echo "==> Installing scheduler cron for www-data"
CRON='* * * * * cd /opt/fintrax/current && /usr/bin/php8.5 artisan schedule:run >> /dev/null 2>&1'
( crontab -u www-data -l 2>/dev/null | grep -vF 'artisan schedule:run'; echo "$CRON" ) | crontab -u www-data -

echo "==> Installing sudoers rule for deploy user (${DEPLOY_USER})"
cat > /etc/sudoers.d/fintrax-deploy <<EOF
${DEPLOY_USER} ALL=(root) NOPASSWD: \
  /usr/bin/systemctl reload php${PHP}-fpm, \
  /usr/bin/systemctl restart fintrax-queue, \
  /usr/bin/systemctl reload nginx, \
  /usr/sbin/nginx -t, \
  /usr/bin/cp /opt/fintrax/releases/*/deploy/fintrax.hassanpi.com.conf /etc/nginx/sites-available/fintrax.hassanpi.com.conf, \
  /usr/bin/ln -sf /etc/nginx/sites-available/fintrax.hassanpi.com.conf /etc/nginx/sites-enabled/fintrax.hassanpi.com.conf
EOF
chmod 440 /etc/sudoers.d/fintrax-deploy
visudo -cf /etc/sudoers.d/fintrax-deploy

cat <<'EOF'

==> Done. Remaining manual steps:
  1. Edit /opt/fintrax/shared/.env — set APP_KEY, confirm APP_URL/DB_DATABASE.
  2. (Optional) migrate old data: copy the .sqlite out of the fintrax-storage
     docker volume into /opt/fintrax/shared/database/database.sqlite, then
     chown www-data:www-data it. Otherwise the first deploy seeds fresh demo data.
  3. Push to main (or re-run the deploy workflow) to ship the first release.
  4. After the first deploy: ensure TLS exists
     (certbot --nginx -d fintrax.hassanpi.com) and `systemctl status fintrax-queue`.
  5. Tear down the old Docker stack: cd /opt/fintrax && docker compose down
     (remove the old docker-compose.yml / fintrax-* volumes once verified).
EOF
