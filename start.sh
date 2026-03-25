#!/bin/bash

echo "======================================"
echo " APP_ENV  = ${APP_ENV}"
echo " APP_KEY  = ${APP_KEY:0:10}..."
echo " DB_HOST  = ${DB_HOST}"
echo " DB_NAME  = ${DB_DATABASE}"
echo " PORT     = ${PORT:-8000}"
echo "======================================"

# Abort early if APP_KEY is missing
if [ -z "$APP_KEY" ]; then
    echo "FATAL: APP_KEY is not set. Set it in Railway Variables."
    exit 1
fi

# Abort early if DB_HOST is missing
if [ -z "$DB_HOST" ]; then
    echo "FATAL: DB_HOST is not set. Add MySQL service and link variables."
    exit 1
fi

# Clear any stale build-time caches
php artisan config:clear  2>&1 || true
php artisan cache:clear   2>&1 || true
php artisan view:clear    2>&1 || true
php artisan route:clear   2>&1 || true

# Re-cache with real runtime env vars
echo "==> Caching config..."
php artisan config:cache  2>&1
php artisan route:cache   2>&1
php artisan view:cache    2>&1

# Wait for MySQL (max 60 seconds)
echo "==> Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}..."
for i in $(seq 1 30); do
    if php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306};dbname=${DB_DATABASE}','${DB_USERNAME}','${DB_PASSWORD}');" 2>/dev/null; then
        echo "==> MySQL is ready!"
        break
    fi
    echo "    ($i/30) Not ready yet, sleeping 2s..."
    sleep 2
    if [ $i -eq 30 ]; then
        echo "FATAL: MySQL never became ready."
        exit 1
    fi
done

# Migrate
echo "==> Running migrations..."
php artisan migrate --force 2>&1

# Start server
echo "==> Serving on 0.0.0.0:${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
