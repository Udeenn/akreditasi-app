
FROM php:8.1-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install dependencies yang umum dibutuhkan Laravel
RUN apk update && apk add --no-cache \
    build-base shadow wget \
    php81-common php81-cli php81-fpm \
    php81-pdo php81-pdo_mysql php81-mysqlnd \
    php81-tokenizer php81-xml php81-xmlwriter \
    php81-session php81-mbstring php81-gd \
    php81-curl php81-zip php81-bcmath \
    php81-redis \
    nginx curl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Hapus cache
RUN rm -rf /var/cache/apk/*

# Copy file proyek ke dalam container
COPY . .

# Install dependency via Composer
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 9000 untuk PHP-FPM
EXPOSE 9000

# Jalankan PHP-FPM
CMD ["php-fpm"]
