# ============================================================
# Dockerfile - PRODUCTION
# Akreditasi App - Laravel 11 / PHP 8.4
# ============================================================
# Multi-stage build untuk image production yang lebih kecil dan aman.
# Stage 1 (builder): install semua dependency & compile assets.
# Stage 2 (final):   hanya ambil artefak yang dibutuhkan runtime.
# ============================================================

# ---- STAGE 1: BUILDER ----
FROM php:8.4-fpm AS builder
WORKDIR /var/www/html

# Install system dependencies dan PHP extensions
RUN apt-get update && apt-get install -y \
    git zip unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && pecl install redis && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql zip gd bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer dari official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files dulu agar layer cache optimal
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

# Copy seluruh kode aplikasi
COPY . .

# Buat direktori storage yang dibutuhkan Laravel
RUN mkdir -p storage/framework/cache storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Dump-autoload optimasi terakhir (tanpa dev deps)
RUN rm -rf bootstrap/cache/*.php && composer dump-autoload --optimize

# Bersihkan composer cache
RUN rm -rf /root/.composer


# ---- STAGE 2: PRODUCTION IMAGE ----
FROM php:8.4-fpm-alpine
WORKDIR /var/www/html

# Install runtime libraries + PHP extensions
RUN apk add --no-cache \
        libpng libjpeg freetype libzip \
    && apk add --no-cache --virtual .build-deps \
        libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip bcmath \
    && apk del .build-deps

# Salin hasil build dari stage builder
COPY --from=builder /var/www/html .

# Copy entrypoint production
COPY .docker/php/entrypoint.prod.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Buat folder runtime & set permission
RUN mkdir -p storage/framework/cache storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache vendor \
    && chown -R www-data:www-data storage bootstrap/cache vendor \
    && chmod -R 775 storage bootstrap/cache vendor

# Gunakan port 9001 agar tidak konflik dengan Portainer (port 9000)
RUN echo 'listen = 9001' >> /usr/local/etc/php-fpm.d/zz-docker.conf

USER www-data

EXPOSE 9001
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
