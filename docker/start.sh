#!/bin/sh
set -e

cd /var/www/html

php artisan config:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache

php artisan migrate --force

php artisan db:seed --force

php-fpm -D

sleep 5
netstat -tlnp | grep 9000 || echo "PHP-FPM NOT listening on 9000"

nginx -g "daemon off;"