#!/bin/sh
# ===========================================
# Entrypoint script untuk PRODUCTION
# ===========================================
set -e

echo "=========================================="
echo "  Akreditasi App - Production"
echo "=========================================="

# 1. Set permissions
echo "[1/4] Setting storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# 2. Create storage link jika belum ada
if [ ! -L "public/storage" ]; then
    echo "[2/4] Creating storage link..."
    php artisan storage:link 2>/dev/null || true
else
    echo "[2/4] Storage link already exists, skipping..."
fi

# 3. Cache routes, views untuk performance
# JANGAN cache config! Karena .env di-mount saat runtime, bukan saat build.
# config:cache akan mengunci nilai KOSONG secara permanen.
echo "[3/4] Caching routes and views..."
php artisan config:clear 2>/dev/null || true
php artisan route:cache  2>/dev/null || echo "  WARN: route:cache failed, skipping"
php artisan view:cache   2>/dev/null || echo "  WARN: view:cache failed, skipping"
php artisan event:cache  2>/dev/null || echo "  WARN: event:cache failed, skipping"

echo "[4/4] Starting PHP-FPM..."
echo "=========================================="
echo "  Setup selesai! Menjalankan PHP-FPM..."
echo "=========================================="

exec php-fpm
