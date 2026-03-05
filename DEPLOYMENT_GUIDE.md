# Panduan Lengkap Docker - Data Pustaka UMS
Panduan ini mencakup instalasi dari awal (segar) hingga cara melakukan pembaruan (update) harian di server production maupun di laptop lokal.

---

## DAFTAR ISI
1. [Prasyarat Server / Laptop](#1-prasyarat-server--laptop)
2. [Instalasi Awal (Lokal / Development)](#2-instalasi-awal-lokal--development)
3. [Instalasi Awal (Server Production)](#3-instalasi-awal-server-production)
4. [Panduan Update Rutin](#4-panduan-update-rutin)
5. [Troubleshooting Umum](#5-troubleshooting-umum)

---

## 1. Prasyarat Server / Laptop
Sebelum mulai, pastikan spesifikasi dasar ini terpenuhi:
- **Docker & Docker Compose** sudah terinstall.
- **Git** terinstall untuk menarik kode sumber.
- **OpenSSL** (Opsional, untuk generate sertifikat SSL lokal).
- Akses ke **Database MySQL Eksternal** (Koha server: 10.2.20.7).

---

## 2. Instalasi Awal (Lokal / Development)
Langkah ini digunakan saat Anda baru saja melakukan *clone* ke laptop baru atau developer baru yang ingin menjalankan aplikasi di komputernya.

### Langkah 1: Clone Repository
```bash
git clone https://github.com/akun-anda/akreditasi-app.git
cd akreditasi-app
```

### Langkah 2: Konfigurasi Environment
```bash
cp .env.example .env
```
Buka `.env` dan pastikan konfigurasi CAS dan Database sudah sesuai dengan standar pengembangan (development).
Contoh:
```env
APP_URL=https://mam.ums.ac.id
DB_HOST=10.2.20.7
```

*(Catatan: Jangan lupa menambahkan `127.0.0.1 mam.ums.ac.id` ke file `hosts` di OS Windows/Mac Anda).*

### Langkah 3: Generate SSL Lokal (Wajib untuk CAS)
Karena CAS UMS mewajibkan HTTPS, Anda harus membuat sertifikat sementara.
```bash
# Buka Git Bash / Terminal, jalankan:
bash generate_cert.sh
```

### Langkah 4: Jalankan Container & Install Dependencies
```bash
# 1. Build & jalankan docker lokal (menggunakan docker-compose.yml default)
docker compose up -d --build

# 2. Install dependensi PHP (Composer) ke dalam container
docker compose exec app composer install

# 3. Generate App Key Laravel
docker compose exec app php artisan key:generate

# 4. Beri perizinan (Permission) ke folder storage
docker compose exec app chmod -R 777 storage bootstrap/cache
```

Sekarang aplikasi bisa diakses di **https://mam.ums.ac.id** 🚀

---

## 3. Instalasi Awal (Server Production)
Langkah ini digunakan saat Anda melakukan *deployment* pertama kali ke server asli (VPS / Server Data Center).

### Langkah 1: Clone Repository
```bash
cd /var/www/html
git clone https://github.com/akun-anda/akreditasi-app.git
cd akreditasi-app
```

### Langkah 2: Konfigurasi Environment Server
```bash
cp .env.production .env
```
Buka `.env`, sesuaikan isinya dengan kredensial data production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://data-lib.ums.ac.id

# Pastikan koneksi DB koha_satellite & user sudah benar
DB_HOST=10.2.20.7
DB_USERNAME=pilot_satellite
DB_PASSWORD=password_super_rahasia
```

### Langkah 3: Build & Install
Di production, kita secara eksplisit menyuruh Docker untuk menggunakan `docker-compose.production.yml` agar source-code di-*embed* langsung ke image untuk alasan keamanan dan kecepatan.

```bash
# 1. Jalankan target Production
docker compose -f docker-compose.production.yml up -d --build

# 2. Install Composer khusus production (--no-dev)
docker compose -f docker-compose.production.yml exec app composer install --optimize-autoloader --no-dev

# 3. Generate App Key Laravel
docker compose -f docker-compose.production.yml exec app php artisan key:generate

# 4. Fix Permission
docker compose -f docker-compose.production.yml exec -u root app chmod -R 775 storage bootstrap/cache
docker compose -f docker-compose.production.yml exec -u root app chown -R www-data:www-data storage bootstrap/cache

# 5. Clear Configuration dan Cache
docker compose -f docker-compose.production.yml exec app php artisan optimize:clear
```

Aplikasi Production berjalan di **https://data-lib.ums.ac.id** 🌐

---

## 4. Panduan Update Rutin
Bagian ini digunakan ketika ada perubahan kode (misal: Anda baru saja *push* perubahan desain dari laptop Anda ke Github).

### Update di Production Server
Karena kita menggunakan `docker-compose.production.yml`, perubahan file PHP **tidak akan langsung terbaca** (karena filenya ada di dalam "kapsul" Docker). Selalu ikuti ritme eksekusi **Pull -> Rebuild -> Clear Cache**.

```bash
# 1. Masuk ke folder aplikasi
cd /var/www/html/akreditasi-app

# 2. Tarik kode terbaru
git pull origin main

# 3. Matikan Container lama
docker compose -f docker-compose.production.yml down

# 4. Build Ulang dan Hidupkan (KODE BARU MASUK KE IMAGE DISINI)
docker compose -f docker-compose.production.yml up -d --build

# 5. Bersihkan Cache (Wajib agar Web tidak nyangkut)
docker compose -f docker-compose.production.yml exec app php artisan optimize:clear

# 6. (Opsional) Jika ada perubahan Database / Migration:
docker compose -f docker-compose.production.yml exec app php artisan migrate --force
```

*(Catatan: Jika Anda membuat alias untuk `docker compose -f docker-compose.production.yml` menjadi `docker compose` saja, sesuaikan dengan kebiasaan di server Anda).*

---

## 5. Troubleshooting Umum

**Q: Saya sudah update teks/HTML tapi tidak berubah di layar?**
> A: Pastikan Anda menjalankan `php artisan optimize:clear` dan/atau `php artisan view:clear`
```bash
docker compose -f docker-compose.production.yml exec app php artisan view:clear
```

**Q: Error 500 setelah instalasi awal atau Update Composer?**
> A: Folder vendor belum ada. Jalankan instruksi *Composer Install*.
```bash
docker compose -f docker-compose.production.yml exec app composer install --no-dev
```

**Q: File CSS dan JS tidak ter-load (Error 404)?**
> A: Volume public nginx di production bersumber dari lokal host. Pastikan Anda tidak lupa mem-build asset Vite / Mix (kalau ada) di laptop dan melakukan *push* ke Git, atau jalankan command build di server jika Node.js tersedia.

**Q: Mendapat Error `Class 'phpCAS' not found`?**
> A: Berarti paket auth CAS belum terinstal. Lakukan instalasi lewat instruktur khusus:
```bash
docker compose -f docker-compose.production.yml exec app composer require apereo/phpcas
```
