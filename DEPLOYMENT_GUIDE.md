# Panduan Update & Deployment - Data Pustaka UMS

Panduan langkah-langkah yang harus dilakukan setiap kali ada perubahan kode dan ingin di-deploy ke server production.

---

## Langkah Update Setelah Ada Perubahan

### 1. Push Perubahan ke Git (di Komputer Lokal)

```bash
git add .
git commit -m "deskripsi perubahan"
git push origin main
```

### 2. Pull Perubahan di Server Production

```bash
# SSH ke server, lalu masuk ke folder project
cd /path/to/akreditasi-app
git pull origin main
```

### 3. Rebuild & Restart Docker

Pilih salah satu sesuai jenis perubahan:

#### a. Perubahan Kode PHP Saja (controller, view, model, route, dll)

```bash
docker compose restart app
```

#### b. Perubahan Composer (tambah/hapus package)

```bash
docker compose exec app composer install --no-interaction --optimize-autoloader --no-dev
docker compose restart app
```

#### c. Perubahan Dockerfile / Nginx Config / Docker Compose

```bash
docker compose down
docker compose up -d --build
```

### 4. Clear Cache Laravel

> Disarankan **selalu** dilakukan setiap kali update.

```bash
docker compose exec app php artisan optimize:clear
```

### 5. Jalankan Migration (Jika Ada Perubahan Database)

```bash
docker compose exec app php artisan migrate --force
```

---

## Ringkasan Cepat

| Jenis Perubahan | Perintah di Server |
|---|---|
| Kode PHP biasa | `git pull` → `docker compose restart app` → `optimize:clear` |
| Tambah package Composer | `git pull` → `composer install` → `restart app` |
| Perubahan `.env` | Edit `.env` di server → `docker compose restart app` |
| Perubahan Docker / Nginx | `git pull` → `docker compose down` → `docker compose up -d --build` |
| Perubahan database (migration) | `git pull` → `php artisan migrate --force` |

---

## Catatan Penting

> **⚠️ Production mode (`docker-compose.production.yml`):**
> Jika menggunakan file production, source code di-embed ke dalam Docker image (tidak di-mount volume).
> Artinya **setiap perubahan kode harus rebuild** dengan `docker compose up -d --build`, bukan hanya restart.

> **⚠️ Jangan lupa clear cache** setelah setiap update agar perubahan langsung terlihat:
> ```bash
> docker compose exec app php artisan optimize:clear
> ```

---

## Contoh Alur Update Lengkap

```bash
# 1. Di komputer lokal
git add .
git commit -m "fix: perbaikan fitur filter jurnal"
git push origin main

# 2. Di server production
cd /path/to/akreditasi-app
git pull origin main

# 3. Rebuild (jika pakai production mode) atau restart
docker compose down
docker compose up -d --build

# 4. Clear cache
docker compose exec app php artisan optimize:clear

# 5. Jalankan migration (jika ada)
docker compose exec app php artisan migrate --force
```

---

## Troubleshooting

### Container gagal start setelah update

```bash
docker compose down
docker compose up -d --build
docker compose logs -f app
```

### Perubahan tidak terlihat di browser

```bash
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan config:clear
```

### Permission error pada storage

```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Melihat log error

```bash
docker compose logs -f app        # Log PHP real-time
docker compose logs -f web        # Log Nginx real-time
docker compose logs --tail=50     # 50 baris terakhir semua service
```
