FROM php:8.2-fpm

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nginx \
    net-tools \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath \ 
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy application
COPY . .

RUN php artisan package:discover --ansi

RUN echo "upload_max_filesize=10M" > /usr/local/etc/php/conf.d/upload.ini && \
    echo "post_max_size=10M" >> /usr/local/etc/php/conf.d/upload.ini && \
    echo "memory_limit=128M" >> /usr/local/etc/php/conf.d/upload.ini && \
    echo "max_execution_time=600" >> /usr/local/etc/php/conf.d/upload.ini

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Nginx config
COPY ./conf/nginx/nginx-site.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Startup script
COPY ./docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]