#!/bin/sh
set -e

cd /var/www/html

php artisan config:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache

php-fpm -D

nginx -g "daemon off;"