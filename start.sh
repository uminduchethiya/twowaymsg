#!/bin/bash

echo "=== START ==="
echo "PORT=${PORT}"
echo "APP_KEY=${APP_KEY:0:15}..."
echo "DB_HOST=${DB_HOST}"

# Must have APP_KEY
if [ -z "$APP_KEY" ]; then
    echo "FATAL: APP_KEY missing"
    exit 1
fi

php artisan config:clear || true
php artisan config:cache

echo "=== Starting server on port ${PORT:-8000} ==="
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
