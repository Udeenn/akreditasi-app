<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\CasController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IjazahController;
use App\Http\Controllers\MouController;
use App\Http\Controllers\PelatihanController;
use App\Http\Controllers\SertifikasiController;
use App\Http\Controllers\SkpController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StatistikController;
use App\Http\Controllers\StatistikKoleksi;
use App\Http\Controllers\TranskripController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PenggunaanController;
use App\Http\Controllers\RewardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitHistory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

// =============================================
// PUBLIC ROUTES (Tidak perlu login)
// =============================================

// Landing Page
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('landing');
})->name('home');

// CAS Authentication Routes
Route::prefix('cas')->name('cas.')->group(function () {
    Route::get('/login', [CasController::class, 'login'])->name('login');
    Route::get('/callback', [CasController::class, 'callback'])->name('callback');
    Route::post('/logout', [CasController::class, 'logout'])->name('logout');
});

// Staff Login (Non-CAS) - untuk backward compatibility
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// =============================================
// PROTECTED ROUTES (Harus login CAS/Auth)
// =============================================

Route::middleware('auth')->group(function () {
    
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'totalStatistik'])->name('dashboard');
    
    // Credit Page
    Route::get('/credit', function () {
        return view('credit');
    })->name('credit.index');

    // =============================================
    // Statistik Kunjungan
    // =============================================
    Route::prefix('kunjungan')->name('kunjungan.')->group(function () {
        Route::get('/prodi-chart', [VisitHistory::class, 'kunjunganProdiChart'])->name('prodiChartData');
        Route::get('/prodi-data', [VisitHistory::class, 'getProdiDataJSON'])->name('prodiData');
        Route::get('/prodi', [VisitHistory::class, 'kunjunganProdiTable'])->name('prodi');
        Route::get('/prodi/data', [VisitHistory::class, 'getProdiData'])->name('prodi.data');
        Route::get('/harian', [VisitHistory::class, 'kunjunganTanggalTable'])->name('tanggalTable');
        Route::get('/fakultas', [VisitHistory::class, 'kunjunganFakultasTable'])->name('fakultasTable');
        Route::get('/fakultas/export', [VisitHistory::class, 'exportCsvFakultas'])->name('fakultasExport');
        Route::get('/cek-kehadiran', [VisitHistory::class, 'cekKehadiran'])->name('cekKehadiran');
        Route::get('/export-kehadiran-full-data', [VisitHistory::class, 'getKehadiranExportData'])->name('get_export_data');
        Route::get('/export-harian-full-data', [VisitHistory::class, 'getKunjunganHarianExportData'])->name('get_harian_export_data');
        Route::get('/export-prodi-full-data', [VisitHistory::class, 'getProdiExportData'])->name('get_prodi_export_data');
        Route::get('/get-detail-pengunjung', [VisitHistory::class, 'getDetailPengunjung'])->name('get_detail_pengunjung');
        Route::get('/get-detail-pengunjung-harian', [VisitHistory::class, 'getDetailPengunjungHarian'])->name('get_detail_pengunjung_harian');
        Route::get('/export-pdf', [VisitHistory::class, 'exportPdf'])->name('export_pdf');
        Route::get('/get-detail-pengunjung-harian-export', [VisitHistory::class, 'getDetailPengunjungHarianExport'])->name('get_detail_pengunjung_harian_export');
        Route::get('/get-lokasi-detail', [VisitHistory::class, 'getLokasiDetail'])->name('get_lokasi_detail');
    });

    // =============================================
    // Statistik Koleksi
    // =============================================
    Route::prefix('koleksi')->name('koleksi.')->group(function () {
        Route::get('/prosiding', [StatistikKoleksi::class, 'prosiding'])->name('prosiding');
        Route::get('/jurnal', [StatistikKoleksi::class, 'jurnal'])->name('jurnal');
        Route::get('/ejurnal', [StatistikKoleksi::class, 'ejurnal'])->name('ejurnal');
        Route::get('/ebook', [StatistikKoleksi::class, 'ebook'])->name('ebook');
        Route::get('/textbook', [StatistikKoleksi::class, 'textbook'])->name('textbook');
        Route::get('/periodikal', [StatistikKoleksi::class, 'periodikal'])->name('periodikal');
        Route::get('/referensi', [StatistikKoleksi::class, 'referensi'])->name('referensi');
        Route::get('/prodi', [StatistikKoleksi::class, 'koleksiPerprodi'])->name('prodi');
        Route::get('/eresource', [StatistikKoleksi::class, 'eresource'])->name('eresource');
        Route::get('/detail', [StatistikKoleksi::class, 'getDetailKoleksi'])->name('detail');
        Route::get('/rekap-fakultas', [StatistikKoleksi::class, 'rekapPerFakultas'])->name('rekap_fakultas');
    });

    // =============================================
    // Laporan
    // =============================================
    Route::get('/kunjungan/keseluruhan', [VisitHistory::class, 'laporanKunjunganGabungan'])->name('kunjungan.keseluruhan');

    // =============================================
    // Peminjaman
    // =============================================
    Route::prefix('peminjaman')->name('peminjaman.')->group(function () {
        Route::get('/peminjaman-rentang-tanggal', [PeminjamanController::class, 'pertanggal'])->name('peminjaman_rentang_tanggal');
        Route::get('/export-detail', [PeminjamanController::class, 'exportDetailCsv'])->name('export_detail');
        Route::get('/peminjaman-prodi-chart', [PeminjamanController::class, 'peminjamanProdiChart'])->name('peminjaman_prodi_chart');
        Route::get('/export-detail-prodi', [PeminjamanController::class, 'exportDetailProdiCsv'])->name('export_detail_prodi');
        Route::get('/cek-histori', [PeminjamanController::class, 'checkHistory'])->name('check_history');
        Route::get('/berlangsung', [PeminjamanController::class, 'peminjamanBerlangsung'])->name('berlangsung');
        Route::get('/export-berlangsung-full-data', [PeminjamanController::class, 'getBerlangsungExportData'])->name('get_berlangsung_export_data');
        Route::get('/detail', [PeminjamanController::class, 'getDetailPeminjaman'])->name('get_detail');
        Route::get('/export-borrowing-full-data', [PeminjamanController::class, 'getBorrowingHistoryExportData'])->name('get_borrowing_export_data');
        Route::get('/export-return-full-data', [PeminjamanController::class, 'getReturnHistoryExportData'])->name('get_return_export_data');
        Route::get('/peminjam-detail', [PeminjamanController::class, 'getPeminjamDetail'])->name('peminjamDetail');
    });

    // =============================================
    // Statistik Penggunaan
    // =============================================
    Route::get('/statistik/keterpakaian-koleksi', [PenggunaanController::class, 'keterpakaianKoleksi'])->name('penggunaan.keterpakaian_koleksi');
    Route::get('/statistik/keterpakaian-koleksi/detail', [PenggunaanController::class, 'getKeterpakaianDetail'])->name('statistik.keterpakaian_koleksi.detail');
    Route::get('/cek-histori-buku', [PenggunaanController::class, 'cekBuku'])->name('penggunaan.cek_histori');
    Route::get('/statistik/sering-dibaca', [PenggunaanController::class, 'seringDibaca'])->name('penggunaan.sering_dibaca');

    // =============================================
    // Reward
    // =============================================
    Route::prefix('reward')->name('reward.')->group(function () {
        Route::get('/pemustaka-teraktif', [RewardController::class, 'pemustakaTeraktif'])->name('pemustaka_teraktif');
        Route::get('/pemustaka-teraktif/export-csv', [RewardController::class, 'exportCsvPemustakaTeraktif'])->name('export_csv_pemustaka_teraktif');
        Route::get('/peminjam-teraktif', [RewardController::class, 'peminjamTeraktif'])->name('peminjam_teraktif');
        Route::get('/peminjam-teraktif/export-csv', [RewardController::class, 'exportCsvPeminjamTeraktif'])->name('export_csv_peminjam_teraktif');
    });

    // =============================================
    // Utilities (Admin only)
    // =============================================
    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        return redirect()->back()->with('success', 'Cache cleared!');
    })->name('clear-cache');
});

