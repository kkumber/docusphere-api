#!/bin/sh
php artisan queue:work --daemon &

# Cron scheduler in background (poor man's cron)
while true; do
    php artisan schedule:run
    sleep 86400
done &

php artisan serve --host=0.0.0.0 --port=$PORT