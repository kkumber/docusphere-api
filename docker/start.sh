#!/bin/sh
set -e

cd /var/www/html

# Cache config/routes
php artisan config:cache
php artisan route:cache

# Run migrations
php artisan migrate --force

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"