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

# 3. Cache Laravel config, routes, views untuk performance
# Gunakan || true agar php-fpm tetap start meski artisan gagal (misal DB timeout)
echo "[3/4] Caching config, routes, and views..."
php artisan config:cache 2>/dev/null || echo "  WARN: config:cache failed, skipping"
php artisan route:cache  2>/dev/null || echo "  WARN: route:cache failed, skipping"
php artisan view:cache   2>/dev/null || echo "  WARN: view:cache failed, skipping"
php artisan event:cache  2>/dev/null || echo "  WARN: event:cache failed, skipping"

echo "[4/4] Starting PHP-FPM..."
echo "=========================================="
echo "  Setup selesai! Menjalankan PHP-FPM..."
echo "=========================================="

exec php-fpm
