#!/bin/sh
# ===========================================
# Entrypoint script untuk PRODUCTION
# ===========================================
set -e

echo "=========================================="
echo "  Akreditasi App - Production"
echo "=========================================="

# 1. Set permissions
echo "[1/5] Setting storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# 2. Create storage link jika belum ada
if [ ! -L "public/storage" ]; then
    echo "[2/5] Creating storage link..."
    php artisan storage:link 2>/dev/null || true
else
    echo "[2/5] Storage link already exists, skipping..."
fi

# 3. Run migrations (opsional, comment jika tidak mau auto-migrate)
# echo "[3/5] Running migrations..."
# php artisan migrate --force 2>/dev/null || true

# 4. Cache Laravel config, routes, views untuk performance
echo "[4/5] Caching config, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[5/5] Clearing old caches..."
php artisan event:cache

echo "=========================================="
echo "  Setup selesai! Menjalankan PHP-FPM..."
echo "=========================================="

exec php-fpm
