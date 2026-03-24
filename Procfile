web: php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
worker: php artisan queue:listen --tries=3 --timeout=60
reverb: php artisan reverb:start --host=0.0.0.0 --port=${REVERB_PORT:-8080} --debug
