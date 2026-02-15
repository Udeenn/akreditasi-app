# Panduan Docker - Data Pustaka UMS (Akreditasi Support)

## Arsitektur

```
┌──────────────────────────────────────────────────────┐
│              Docker Network: akreditasi-network       │
├──────────────────────────────────────────────────────┤
│                                                       │
│  ┌──────────┐    ┌──────────┐    ┌────────┐          │
│  │   web    │    │   app    │    │  redis │          │
│  │  nginx   │───▶│ php-fpm  │    │        │          │
│  │ :80/:443 │    │  :9000   │    │  :6379 │          │
│  └──────────┘    └──────────┘    └────────┘          │
│       │                                               │
│       ▼                                               │
│  Browser akses via                                    │
│  https://mam.ums.ac.id (dev)                         │
│  https://data-lib.ums.ac.id (prod)                   │
│                                                       │
│  Database: server eksternal (10.2.20.7)              │
│                                                       │
└──────────────────────────────────────────────────────┘
```

> **Catatan**: Database menggunakan server eksternal, bukan Docker container.

---

## Prasyarat

1. **Docker Desktop** terinstall dan running
2. **OpenSSL** (biasanya sudah ada di Git Bash)
3. **Hosts file** sudah dikonfigurasi

### Konfigurasi Hosts File

Tambahkan entry berikut di `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1 mam.ums.ac.id
```

> Untuk production, ganti dengan: `<IP_SERVER> data-lib.ums.ac.id`

---

## Setup Pertama Kali

### 1. Generate SSL Certificate

```bash
# Untuk Development (mam.ums.ac.id)
bash generate_cert.sh
```

Atau jalankan manual:
```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout .docker/nginx/selfsigned.key \
  -out .docker/nginx/selfsigned.crt \
  -subj "//C=ID\ST=JawaTengah\L=Surakarta\O=UMS\OU=IT\CN=mam.ums.ac.id"
```

> **Windows Note**: Gunakan `//C=ID\ST=...` (double slash di awal) jika pakai Git Bash.

### 2. Konfigurasi Environment

Copy dan sesuaikan `.env`:
```bash
cp .env.example .env
```

Pastikan variabel berikut sudah diset:
```env
APP_URL=https://mam.ums.ac.id

# Database Eksternal
DB_HOST=10.2.20.7
DB_PORT=3306

# Session
SESSION_DOMAIN=mam.ums.ac.id
SESSION_SECURE_COOKIE=true

# CAS SSO
CAS_HOST=auth.ums.ac.id
CAS_PORT=443
CAS_CONTEXT=/cas
CAS_VERSION=2.0
CAS_DISABLE_SSL_VALIDATION=true
```

### 3. Build & Jalankan Docker

```bash
docker compose up -d --build
```

### 4. Install Dependencies & Setup Laravel

```bash
# Buat direktori storage yang diperlukan (jika belum ada)
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

# Install Composer dependencies
docker compose exec app composer install --no-interaction --optimize-autoloader

# Clear cache
docker compose exec app php artisan optimize:clear
```

### 5. Verifikasi

Buka browser: **https://mam.ums.ac.id**

> Browser akan menampilkan warning SSL karena menggunakan self-signed certificate.
> Klik **Advanced** → **Proceed** untuk melanjutkan.

---

## Perintah Sehari-hari

### Start / Stop

```bash
# Start semua service
docker compose up -d

# Stop semua service
docker compose down

# Restart service tertentu
docker compose restart app
docker compose restart web
```

### Melihat Log

```bash
docker compose logs -f app        # Log PHP real-time
docker compose logs -f web        # Log Nginx real-time
docker compose logs --tail=50     # 50 baris terakhir semua service
```

### Artisan Commands

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan route:list
docker compose exec app php artisan queue:work
```

### Composer

```bash
docker compose exec app composer install
docker compose exec app composer update
docker compose exec app composer dump-autoload
```

---

## Struktur File Docker

```
akreditasi-app/
├── docker-compose.yml              # Orchestrator semua service
├── generate_cert.sh                # Script generate SSL cert
├── .docker/
│   ├── nginx/
│   │   ├── datapustaka.conf        # Nginx config (dev: mam.ums.ac.id)
│   │   ├── local.conf              # Nginx config (prod: data-lib.ums.ac.id)
│   │   ├── selfsigned.crt          # SSL certificate (git-ignored)
│   │   └── selfsigned.key          # SSL private key (git-ignored)
│   └── php/
│       ├── Dockerfile.dev          # PHP-FPM development
│       └── Dockerfile.prod         # PHP-FPM production (multi-stage)
├── .env                            # Environment development
└── .env.production                 # Environment production
```

---

## Environment: Development vs Production

| Setting | Development (`.env`) | Production (`.env.production`) |
|---------|---------------------|-------------------------------|
| `APP_URL` | `https://mam.ums.ac.id` | `https://data-lib.ums.ac.id` |
| `APP_DEBUG` | `true` | `false` |
| `SESSION_DOMAIN` | `mam.ums.ac.id` | `data-lib.ums.ac.id` |
| `CACHE_STORE` | `file` | `redis` |
| `QUEUE_CONNECTION` | `sync` | `redis` |
| `SESSION_DRIVER` | `file` | `redis` |
| `CAS_DISABLE_SSL_VALIDATION` | `true` | `false` |

### Beralih ke Production

1. Ganti nginx config di `docker-compose.yml`:
   ```yaml
   - ./.docker/nginx/local.conf:/etc/nginx/conf.d/default.conf
   ```
2. Generate SSL cert baru untuk `data-lib.ums.ac.id`:
   ```bash
   openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
     -keyout .docker/nginx/selfsigned.key \
     -out .docker/nginx/selfsigned.crt \
     -subj "/C=ID/ST=JawaTengah/L=Surakarta/O=UMS/OU=IT/CN=data-lib.ums.ac.id"
   ```
3. Copy `.env.production` ke `.env`, sesuaikan `APP_KEY`.
4. Rebuild: `docker compose up -d --build`

---

## CAS SSO Authentication

Aplikasi menggunakan **CAS SSO UMS** (`auth.ums.ac.id`) untuk autentikasi.

| Route | Fungsi |
|-------|--------|
| `/cas/login` | Redirect ke CAS login UMS |
| `/cas/callback` | Handle callback setelah login CAS |
| `/cas/logout` | Logout dari CAS dan aplikasi |
| `/login` | Staff login (non-CAS, backward compatibility) |

> **Penting**: Domain yang didaftarkan di CAS server harus sesuai dengan `APP_URL`.
> - Development: `mam.ums.ac.id` (sudah didaftarkan)
> - Production: `data-lib.ums.ac.id`

---

## Akses Layanan

| Service | URL/Port | Keterangan |
|---------|----------|------------|
| **Web App** | https://mam.ums.ac.id | Aplikasi utama (dev) |
| **Redis** | localhost:6379 | Cache & Queue |
| **MySQL** | 10.2.20.7:3306 | Database eksternal |

---

## Troubleshooting

### Port 80/443 sudah dipakai (Laravel Herd / service lain)

```bash
# Cek proses yang pakai port 80
netstat -ano | findstr ":80 "

# Jika Laravel Herd, stop dulu Herd dari system tray
# Lalu restart Docker
docker compose down
docker compose up -d
```

### Container tidak mau start

```bash
docker compose down
docker compose up -d --build
```

### Vendor directory kosong / error autoload

```bash
# Karena volume mount override vendor dari build
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
docker compose exec app composer install
```

### Permission denied pada storage

```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### SSL certificate expired / error

```bash
# Regenerate certificate
bash generate_cert.sh
docker compose restart web
```

### Clear semua cache

```bash
docker compose exec app php artisan optimize:clear
```

### phpCAS Deprecation Warning

Warning `phpCAS::client(): Implicitly marking parameter as nullable` bisa diabaikan — ini dari library `apereo/phpcas` dan tidak mempengaruhi fungsi.
