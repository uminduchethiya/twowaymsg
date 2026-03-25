#!/bin/bash
set -e

echo "==> Clearing cached config (will rebuild with runtime env vars)"
php artisan config:clear
php artisan cache:clear

echo "==> Caching config with live environment variables"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Waiting for database..."
until php artisan db:show --no-interaction > /dev/null 2>&1; do
    echo "    DB not ready yet, retrying in 2s..."
    sleep 2
done

echo "==> Running migrations"
php artisan migrate --force

echo "==> Starting Laravel server on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
