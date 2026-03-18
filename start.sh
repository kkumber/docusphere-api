#!/bin/sh

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:work --memory=128 --sleep=3 --tries=3 &

php artisan serve --host=0.0.0.0 --port=$PORT
