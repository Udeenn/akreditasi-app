# Panduan Menjalankan Aplikasi di Lokal (Docker)

Panduan ini menjelaskan cara menjalankan aplikasi Akreditasi Support di laptop lokal menggunakan Docker.
Konfigurasi lokal **100% terpisah** dari production.

---

## Arsitektur Lokal

```
┌─────────────────────────────────────────────────────────┐
│                    Docker Network                       │
│                                                         │
│  ┌──────────────┐   ┌──────────────┐   ┌─────────────┐ │
│  │    Nginx      │   │   PHP-FPM    │   │    Redis    │ │
│  │  (HTTPS:443)  │──▶│  (port 9000) │──▶│ (port 6379) │ │
│  │ mam.ums.ac.id │   │  Laravel App │   │ Cache/Queue │ │
│  └──────────────┘   └──────┬───────┘   └─────────────┘ │
│                            │                            │
└────────────────────────────┼────────────────────────────┘
                             │
                    ┌────────▼────────┐
                    │  Remote MySQL   │
                    │  10.12.0.7      │
                    │  172.16.10.43   │
                    └─────────────────┘
```

## Perbandingan Lokal vs Production

| | **Lokal** | **Production** |
|---|---|---|
| Domain | `https://mam.ums.ac.id` | `https://data-lib.ums.ac.id` |
| Compose file | `docker-compose.local.yml` | `docker-compose.production.yml` |
| ENV file | `.env.local` → `.env` | `.env.production` → `.env` |
| Nginx config | `.docker/nginx/mam-local.conf` | `.docker/nginx/datapustaka.conf` |
| Dockerfile | `.docker/php/Dockerfile.dev` | `.docker/php/Dockerfile.prod` |
| SSL | Self-signed cert | SSL dari server |
| Cache | Redis | Redis |
| Session | Redis | Redis |
| Queue | Redis | Redis |

---

## Prasyarat

1. **Docker Desktop** terinstall dan berjalan
2. **Entry di hosts file** — pastikan `mam.ums.ac.id` sudah mengarah ke `127.0.0.1`:
   ```
   # File: C:\Windows\System32\drivers\etc\hosts
   127.0.0.1 mam.ums.ac.id
   ```
3. **Akses jaringan** ke database server (`10.12.0.7` dan `172.16.10.43`)
4. **Self-signed SSL certificate** — sudah ada di `.docker/nginx/`. Jika perlu generate ulang:
   ```bash
   # Jalankan dari root project
   bash generate_cert.sh
   ```

---

## Cara Menjalankan

### 1. Start Aplikasi (Pertama Kali)

```powershell
# Build image dan start semua container
docker-compose -f docker-compose.local.yml up -d --build
```

> **Catatan:** Pertama kali akan memakan waktu ~2-3 menit untuk build image PHP.
> Entrypoint script otomatis menjalankan:
> - `composer install`
> - `php artisan cache:clear`
> - `php artisan config:clear`
> - `php artisan route:clear`
> - `php artisan view:clear`
> - `php artisan storage:link`
> - Set permissions pada `storage/` dan `bootstrap/cache/`

### 2. Buka di Browser

Buka: **https://mam.ums.ac.id**

> ⚠️ Browser akan menampilkan peringatan SSL karena menggunakan self-signed certificate.
> Klik **Advanced** → **Proceed to mam.ums.ac.id** untuk melanjutkan.

### 3. Login

Login menggunakan CAS UMS — akan redirect ke `auth.ums.ac.id`.

---

## Perintah-Perintah Penting

### Menghentikan Aplikasi
```powershell
docker-compose -f docker-compose.local.yml down
```

### Start Ulang (Tanpa Rebuild)
```powershell
docker-compose -f docker-compose.local.yml up -d
```

### Rebuild (Setelah Ubah Dockerfile/Config)
```powershell
docker-compose -f docker-compose.local.yml down
docker-compose -f docker-compose.local.yml up -d --build
```

### Melihat Logs
```powershell
# Semua container
docker-compose -f docker-compose.local.yml logs -f

# PHP-FPM saja
docker logs akreditasi_app_local -f

# Nginx saja
docker logs akreditasi_nginx_local -f

# Redis saja
docker logs akreditasi_redis_local -f
```

### Masuk ke Container (Shell)
```powershell
# Masuk ke container PHP
docker exec -it akreditasi_app_local bash

# Jalankan artisan command
docker exec akreditasi_app_local php artisan tinker
docker exec akreditasi_app_local php artisan cache:clear
docker exec akreditasi_app_local php artisan route:list
```

### Cek Status Container
```powershell
docker ps -a --filter "name=akreditasi"
```

---

## Struktur File Lokal

```
akreditasi-app/
├── .docker/
│   ├── nginx/
│   │   ├── mam-local.conf          ← Nginx config lokal (HTTPS)
│   │   ├── datapustaka.conf         ← Nginx config production
│   │   ├── selfsigned.crt           ← SSL certificate
│   │   └── selfsigned.key           ← SSL private key
│   └── php/
│       ├── Dockerfile.dev           ← Image PHP untuk lokal (PHP 8.4 + Redis)
│       ├── Dockerfile.prod          ← Image PHP untuk production
│       └── entrypoint.dev.sh        ← Script auto-setup saat container start
├── .env                             ← Config aktif (copy dari .env.local)
├── .env.local                       ← Config khusus lokal ← EDIT INI
├── .env.production                  ← Config khusus production
├── docker-compose.local.yml         ← Docker Compose lokal
├── docker-compose.production.yml    ← Docker Compose production
└── ...
```

---

## Mengubah Konfigurasi Lokal

1. Edit file `.env.local`
2. Copy ke `.env`:
   ```powershell
   Copy-Item -Path ".env.local" -Destination ".env" -Force
   ```
3. Restart container:
   ```powershell
   docker-compose -f docker-compose.local.yml down
   docker-compose -f docker-compose.local.yml up -d
   ```

---

## Troubleshooting

### Port 80/443 Sudah Dipakai
```powershell
# Cek proses yang pakai port
netstat -ano | findstr ":80 "
netstat -ano | findstr ":443 "

# Matikan proses tersebut, atau ubah port di docker-compose.local.yml
```

### 502 Bad Gateway
- Container PHP mungkin belum selesai startup (entrypoint masih jalan `composer install`)
- Tunggu 30-60 detik lalu coba lagi
- Cek logs: `docker logs akreditasi_app_local`

### Permission Error pada Storage
```powershell
docker exec akreditasi_app_local bash -c "chmod -R 775 storage bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache"
```

### Database Connection Refused
- Pastikan laptop terhubung ke jaringan yang bisa akses `10.12.0.7` dan `172.16.10.43`
- Cek kredensial di `.env.local`

### SSL Certificate Error di Browser
- Normal untuk self-signed cert — klik **Advanced** → **Proceed**
- Jika mau generate ulang cert: `bash generate_cert.sh`

### phpCAS Deprecation Warnings
- Warning `Deprecated: phpCAS::client()...` di logs adalah **normal** dan tidak mempengaruhi fungsi aplikasi
- Ini karena library phpCAS belum sepenuhnya kompatibel dengan PHP 8.4
