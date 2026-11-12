# ==============================================================================
# Stage 1: Frontend Asset Compilation (Node.js 22)
# ==============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

# Install frontend dependencies
COPY package.json package-lock.json ./
RUN npm ci

# Copy frontend source and Vite configuration
COPY resources/ ./resources/
COPY vite.config.js ./

# Build production assets
RUN npm run build


# ==============================================================================
# Stage 2: Production Runtime (PHP 8.4-FPM + Nginx)
# ==============================================================================
FROM php:8.4-fpm-bookworm

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive \
    APP_ENV=production \
    APP_DEBUG=false

# Install Nginx and required system packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    curl \
    git \
    unzip \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extension installer
COPY --from=mlocati/php-extension-installer:latest \
    /usr/bin/install-php-extensions /usr/local/bin/

# Install required PHP extensions
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    intl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Configure PHP upload and execution limits
RUN { \
    echo 'upload_max_filesize=10M'; \
    echo 'post_max_size=12M'; \
    echo 'max_file_uploads=20'; \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=60'; \
} > /usr/local/etc/php/conf.d/production.ini

# Configure OPcache
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.memory_consumption=192'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Copy application source
COPY . /var/www/html

# Copy compiled Vite assets
COPY --from=frontend /app/public/build /var/www/html/public/build

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf

# Install production Composer dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# Prepare Laravel writable directories and permissions
RUN mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
    && chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache

# Railway internal HTTP port
EXPOSE 8080

# Laravel healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/up || exit 1

# Start Laravel optimization, PHP-FPM, and Nginx
CMD ["sh", "-c", "set -e; mkdir -p /var/www/html/storage/app/private; chown www-data:www-data /var/www/html/storage/app/private; php artisan optimize; if [ ! -L /var/www/html/public/storage ]; then php artisan storage:link; fi; php-fpm -D; exec nginx -g 'daemon off;'"]