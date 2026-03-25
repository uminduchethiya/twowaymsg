#!/bin/bash
set -e

echo "======================================"
echo " APP_ENV  = ${APP_ENV}"
echo " APP_KEY  = ${APP_KEY:0:20}..."
echo " DB_HOST  = ${DB_HOST}"
echo " DB_NAME  = ${DB_DATABASE}"
echo " PORT     = ${PORT:-8080}"
echo "======================================"

# Trim whitespace from APP_KEY just in case
APP_KEY=$(echo "${APP_KEY}" | tr -d '[:space:]')

if [ -z "${APP_KEY}" ]; then
    echo "FATAL: APP_KEY is empty - set it in Railway Variables"
    exit 1
fi

echo "==> APP_KEY length: ${#APP_KEY} chars - OK"

cd /var/www/html

# Clear stale build-time cache
php artisan config:clear  || true
php artisan cache:clear   || true
php artisan view:clear    || true
php artisan route:clear   || true

# Re-cache with real runtime env vars
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Wait for MySQL
echo "==> Waiting for MySQL..."
for i in $(seq 1 30); do
    if php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306};dbname=${DB_DATABASE}','${DB_USERNAME}','${DB_PASSWORD}');" 2>/dev/null; then
        echo "==> MySQL ready!"
        break
    fi
    echo "    ($i/30) waiting..."
    sleep 2
    if [ $i -eq 30 ]; then
        echo "FATAL: MySQL never became available"
        exit 1
    fi
done

# Run migrations
php artisan migrate --force

# Railway provides $PORT — update Apache to listen on it
LISTEN_PORT=${PORT:-80}
echo "==> Configuring Apache to listen on port ${LISTEN_PORT}"

# Update ports.conf
sed -i "s/Listen 80$/Listen ${LISTEN_PORT}/" /etc/apache2/ports.conf

# Update VirtualHost
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${LISTEN_PORT}>/" /etc/apache2/sites-available/000-default.conf

# Verify the config
echo "==> Apache ports.conf:"
cat /etc/apache2/ports.conf

echo "==> Starting Apache on port ${LISTEN_PORT}"
exec apache2-foreground
