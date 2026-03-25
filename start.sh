#!/bin/bash
set -e

echo "======================================"
echo " Laravel startup"
echo " PORT=${PORT:-8000}"
echo " APP_ENV=${APP_ENV}"
echo " DB_HOST=${DB_HOST}"
echo " DB_DATABASE=${DB_DATABASE}"
echo "======================================"

# Clear any build-time cached config
echo "==> Clearing build-time cache..."
php artisan config:clear  || true
php artisan cache:clear   || true
php artisan view:clear    || true
php artisan route:clear   || true

# Re-cache with runtime environment variables
echo "==> Caching config with runtime env vars..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Wait for MySQL to be reachable (max 60s)
echo "==> Waiting for database connection..."
MAX=30
COUNT=0
until php -r "
    \$pdo = new PDO(
        'mysql:host=${DB_HOST};port=${DB_PORT:-3306};dbname=${DB_DATABASE}',
        '${DB_USERNAME}',
        '${DB_PASSWORD}'
    );
    echo 'connected';
" 2>/dev/null; do
    COUNT=$((COUNT+1))
    if [ $COUNT -ge $MAX ]; then
        echo "ERROR: Could not connect to database after ${MAX} attempts. Exiting."
        exit 1
    fi
    echo "    Attempt $COUNT/$MAX - DB not ready, waiting 2s..."
    sleep 2
done
echo "==> Database connected!"

# Run migrations
echo "==> Running migrations..."
php artisan migrate --force

echo "==> Starting server on 0.0.0.0:${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
