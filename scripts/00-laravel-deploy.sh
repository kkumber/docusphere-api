#!/usr/bin/env bash

echo "Installing dependencies..."
composer install --no-dev --working-dir=/var/www/html

echo "Running migrations..."
php artisan migrate --force

if (User::count() == 0); then
    echo "Seeding database..."
    php artisan db:seed --force
fi

echo "Caching config..."
php artisan config:clear
php artisan config:cache

echo "Caching routes..."
php artisan route:cache