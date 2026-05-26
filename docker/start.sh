#!/bin/sh

set -e

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"