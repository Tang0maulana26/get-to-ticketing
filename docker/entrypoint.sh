#!/bin/sh
set -e

export PORT="${PORT:-10000}"

# Render Nginx configuration with dynamic PORT
envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

# Ensure storage directory structure exists
mkdir -p /var/www/html/storage/app/public \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Symlink public storage and warm up caches
php artisan storage:link --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optional automatic database migration on boot
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

if [ "${RUN_SEEDER}" = "true" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force
fi

echo "Starting PHP-FPM and Nginx on port ${PORT}..."
php-fpm -D
exec nginx -g "daemon off;"

