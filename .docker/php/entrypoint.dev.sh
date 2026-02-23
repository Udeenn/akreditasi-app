#!/bin/bash
# ===========================================
# Entrypoint script untuk local development
# ===========================================
# Script ini otomatis dijalankan saat container start.
# Tidak perlu menjalankan perintah-perintah ini secara manual.

set -e

echo "=========================================="
echo "  Akreditasi App - Local Development"
echo "=========================================="

# 1. Install/update Composer dependencies
echo "[1/5] Installing Composer dependencies..."
composer install --no-interaction --optimize-autoloader 2>/dev/null

# 2. Generate APP_KEY jika belum ada
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "[2/5] Generating application key..."
    php artisan key:generate --no-interaction
else
    echo "[2/5] Application key already set, skipping..."
fi

# 3. Clear & cache config
echo "[3/5] Clearing caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# 4. Set permissions
echo "[4/5] Setting storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# 5. Create storage link jika belum ada
if [ ! -L "public/storage" ]; then
    echo "[5/5] Creating storage link..."
    php artisan storage:link 2>/dev/null || true
else
    echo "[5/5] Storage link already exists, skipping..."
fi

echo "=========================================="
echo "  Setup selesai! Menjalankan PHP-FPM..."
echo "=========================================="

# Jalankan PHP-FPM sebagai proses utama
exec php-fpm
