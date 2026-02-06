# Panduan Teknis Docker - Data Pustaka UMS

## Arsitektur Docker

```
┌─────────────────────────────────────────────────────────────┐
│                    Docker Network: laravel                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌────────┐ │
│  │   web    │    │   app    │    │    db    │    │  redis │ │
│  │  nginx   │───▶│ php-fpm  │───▶│  mysql   │    │        │ │
│  │  :80     │    │  :9000   │    │  :3306   │    │  :6379 │ │
│  └──────────┘    └──────────┘    └──────────┘    └────────┘ │
│       │                               │                      │
│       ▼                               ▼                      │
│  ┌──────────┐                   ┌──────────┐                │
│  │ adminer  │                   │ db_data  │                │
│  │  :8080   │                   │ (volume) │                │
│  └──────────┘                   └──────────┘                │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Struktur File Docker

```
akreditasi-app/
├── docker-compose.yml          # Orchestrator semua service
├── Dockerfile                  # Build image untuk production
├── .docker/
│   ├── nginx/
│   │   └── datapustaka.conf    # Konfigurasi Nginx
│   └── php/
│       ├── Dockerfile.dev      # PHP-FPM untuk development
│       └── Dockerfile.prod     # PHP-FPM untuk production
├── .env                        # Environment development (lokal)
└── .env.production             # Environment production (server)
```

---

## Perintah Docker Dasar

### 1. Build & Start Semua Service
```bash
docker-compose up -d --build
```

### 2. Stop Semua Service
```bash
docker-compose down
```

### 3. Restart Service Tertentu
```bash
docker-compose restart app        # Restart PHP-FPM
docker-compose restart web        # Restart Nginx
docker-compose restart redis      # Restart Redis
```

### 4. Lihat Log Container
```bash
docker-compose logs -f app        # Log PHP real-time
docker-compose logs -f web        # Log Nginx real-time
docker-compose logs --tail=100    # 100 baris terakhir semua service
```

### 5. Masuk ke Container (Shell)
```bash
docker-compose exec app bash      # Masuk ke container PHP
docker-compose exec db mysql -u root -p  # Masuk MySQL CLI
docker-compose exec redis redis-cli      # Masuk Redis CLI
```

---

## Perintah Laravel dalam Docker

### Artisan Commands
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan queue:work
```

### Composer
```bash
docker-compose exec app composer install
docker-compose exec app composer update
docker-compose exec app composer dump-autoload
```

---

## Akses Layanan

| Service | URL | Keterangan |
|---------|-----|------------|
| **Web App** | http://data-lib.ums.ac.id | Aplikasi utama |
| **Adminer** | http://localhost:8080 | Database Manager |
| **MySQL** | localhost:33066 | Koneksi eksternal |
| **Redis** | localhost:6379 | Cache & Queue |

---

## Environment: Development vs Production

| Setting | Development (.env) | Production (.env.production) |
|---------|-------------------|------------------------------|
| `APP_DEBUG` | `true` | `false` |
| `CACHE_STORE` | `database` | `redis` |
| `QUEUE_CONNECTION` | `database` | `redis` |
| `SESSION_DRIVER` | `file` | `redis` |
| `REDIS_HOST` | `127.0.0.1` | `redis` |

---

## Troubleshooting

### Container tidak mau start
```bash
docker-compose down -v            # Hapus volume lama
docker-compose up -d --build      # Build ulang
```

### Permission denied pada storage
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Clear semua cache
```bash
docker-compose exec app php artisan optimize:clear
docker-compose exec redis redis-cli FLUSHALL
```
