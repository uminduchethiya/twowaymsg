#!/bin/bash
set -e

echo "======================================"
echo " APP_ENV  = ${APP_ENV}"
echo " APP_KEY  = ${APP_KEY:0:20}..."
echo " DB_HOST  = ${DB_HOST}"
echo " DB_NAME  = ${DB_DATABASE}"
echo " PORT     = ${PORT:-8080}"
echo "======================================"

# Trim whitespace from APP_KEY
APP_KEY=$(echo "${APP_KEY}" | tr -d '[:space:]')

if [ -z "${APP_KEY}" ]; then
    echo "FATAL: APP_KEY is empty - set it in Railway Variables"
    exit 1
fi

echo "==> APP_KEY length: ${#APP_KEY} chars - OK"

cd /var/www/html

# Clear and rebuild cache with runtime env vars
php artisan config:clear  || true
php artisan cache:clear   || true
php artisan view:clear    || true
php artisan route:clear   || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Show DB credentials
echo "==> DB_USERNAME = ${DB_USERNAME}"
echo "==> DB_PORT     = ${DB_PORT}"
echo "==> DB_PASSWORD length = ${#DB_PASSWORD}"

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

# Configure nginx port
LISTEN_PORT=${PORT:-8080}
echo "==> Configuring nginx on port ${LISTEN_PORT}"
sed -i "s/\${PORT}/${LISTEN_PORT}/g" /etc/nginx/sites-available/default

# Start php-fpm in background
echo "==> Starting php-fpm..."
php-fpm -D

# Start nginx in foreground
echo "==> Starting nginx on port ${LISTEN_PORT}"
exec nginx -g 'daemon off;'
