@echo off
setlocal

:: ==========================================
:: SCRIPT AUTO-BACKUP DATABASE KOHA_SATELLITE
:: ==========================================

:: Konfigurasi Database (Sesuaikan dengan .env Anda)
set DB_HOST=10.12.0.7
set DB_USER=pilot_satellite
set DB_PASS=B1sm1r0bb1k4123
set DB_NAME=koha_satellite

:: Konfigurasi Folder Backup
set BACKUP_DIR=D:\Code\akreditasi-app\storage\backups

:: Buat folder backup jika belum ada
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

:: Format Nama File (YYYY-MM-DD_HH-MM-SS)
for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c-%%a-%%b)
for /f "tokens=1-2 delims=/:" %%a in ('time /t') do (set mytime=%%a%%b)
set mytime=%mytime: =0%

set FILE_NAME=%BACKUP_DIR%\%DB_NAME%_backup_%mydate%_%mytime%.sql

:: Eksekusi mysqldump (Pastikan mysqldump.exe ada di system PATH atau XAMPP)
echo Memulai backup database %DB_NAME% dari %DB_HOST%...
mysqldump -h %DB_HOST% -u %DB_USER% -p%DB_PASS% %DB_NAME% > "%FILE_NAME%"

if %ERRORLEVEL% equ 0 (
    echo Backup berhasil disimpan di: %FILE_NAME%
) else (
    echo [ERROR] Gagal melakukan backup! Pastikan XAMPP/MySQL terinstall dan mysqldump bisa diakses.
)

:: Hapus backup yang lebih tua dari 30 hari (opsional)
forfiles /p "%BACKUP_DIR%" /s /m *.sql /d -30 /c "cmd /c del @path" 2>nul

echo Selesai.
endlocal
