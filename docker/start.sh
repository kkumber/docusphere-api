#!/bin/sh
set -e

cd /var/www/html

php artisan config:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache

echo "Starting Queue Worker..."
php artisan queue:work --tries=3 --sleep=3 --timeout=90 --max-time=3600 &

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
nginx -g "daemon off;"