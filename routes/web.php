<?php

use App\Http\Controllers\Auth\AuthController;
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

Route::get('/', [DashboardController::class, 'totalStatistik'])->name('dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/credit', function () {
    return view('credit');
})->name('credit.index');

Route::get('/test', function () {
    dd(Auth::check()); // true jika login, false jika tidak
});

Route::get('/debug-session', function () {
    dd([
        'is_logged_in' => Auth::check(),
        'user_id' => Auth::id(),
        'session_data' => session()->all()
    ]);
});


// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::resources([
//         'ijazah' => IjazahController::class,
//         'staff' => StaffController::class,
//         'transkrip' => TranskripController::class,
//         'sertifikasi' => SertifikasiController::class,
//         'skp' => SkpController::class,
//         'pelatihan' => PelatihanController::class,
//         'mou' => MouController::class,
//     ]);
// });

Route::get('/kunjungan/prodiChart', [VisitHistory::class, 'kunjunganProdiChart'])->name('kunjungan.prodiChart');
Route::get('/kunjungan/prodiTable', [VisitHistory::class, 'kunjunganProdiTable'])->name('kunjungan.prodiTable');
Route::get('/kunjungan/tanggal', [VisitHistory::class, 'kunjunganTanggalTable'])->name('kunjungan.tanggalTable');
Route::get('/kunjungan/fakultas', [VisitHistory::class, 'kunjunganFakultasTable'])->name('kunjungan.fakultasTable');
// Route untuk Export CSV
Route::get('/kunjungan/fakultas/export', [VisitHistory::class, 'exportCsvFakultas'])
    ->name('kunjungan.fakultasExport');

Route::get('/koleksi/prosiding', [StatistikKoleksi::class, 'prosiding'])->name('koleksi.prosiding');
Route::get('/koleksi/jurnal', [StatistikKoleksi::class, 'jurnal'])->name('koleksi.jurnal');
Route::get('/koleksi/ejurnal', [StatistikKoleksi::class, 'ejurnal'])->name('koleksi.ejurnal');
Route::get('/koleksi/ebook', [StatistikKoleksi::class, 'ebook'])->name('koleksi.ebook');
Route::get('/koleksi/textbook', [StatistikKoleksi::class, 'textbook'])->name('koleksi.textbook');
Route::get('/koleksi/periodikal', [StatistikKoleksi::class, 'periodikal'])->name('koleksi.periodikal');
Route::get('/koleksi/referensi', [StatistikKoleksi::class, 'referensi'])->name('koleksi.referensi');
Route::get('/koleksi/prodi', [StatistikKoleksi::class, 'koleksiPerprodi'])->name('koleksi.prodi');
Route::get('/koleksi/eresource', [StatistikKoleksi::class, 'eresource'])->name('koleksi.eresource');


Route::get('/kunjungan/cek-kehadiran', [VisitHistory::class, 'cekKehadiran'])->name('kunjungan.cekKehadiran');

Route::get('/laporan/kunjungan-gabungan', [VisitHistory::class, 'laporanKunjunganGabungan'])->name('kunjungan.kunjungan_gabungan');

Route::get('/peminjaman/peminjaman-rentang-tanggal', [PeminjamanController::class, 'pertanggal'])->name('peminjaman.peminjaman_rentang_tanggal');

Route::get('/peminjaman/export-detail', [PeminjamanController::class, 'exportDetailCsv'])->name('peminjaman.export_detail');

Route::get('/peminjaman/peminjaman-prodi-chart', [PeminjamanController::class, 'peminjamanProdiChart'])->name('peminjaman.peminjaman_prodi_chart');
Route::get('/peminjaman/export-detail-prodi', [PeminjamanController::class, 'exportDetailProdiCsv'])->name('peminjaman.export_detail_prodi');

Route::get('/peminjaman/cek-histori', [PeminjamanController::class, 'checkHistory'])->name('peminjaman.check_history');

Route::get('/peminjaman/berlangsung', [PeminjamanController::class, 'peminjamanBerlangsung'])->name('peminjaman.berlangsung');

Route::get('/peminjaman/export-berlangsung-full-data', [PeminjamanController::class, 'getBerlangsungExportData'])->name('peminjaman.get_berlangsung_export_data');

Route::get('/peminjaman/detail', [PeminjamanController::class, 'getDetailPeminjaman'])->name('peminjaman.get_detail');

Route::get('/kunjungan/export-kehadiran-full-data', [VisitHistory::class, 'getKehadiranExportData'])->name('kunjungan.get_export_data');
Route::get('/kunjungan/export-harian-full-data', [VisitHistory::class, 'getKunjunganHarianExportData'])->name('kunjungan.get_harian_export_data');

Route::get('/kunjungan/export-prodi-full-data', [VisitHistory::class, 'getProdiExportData'])->name('kunjungan.get_prodi_export_data');

Route::get('/peminjaman/export-borrowing-full-data', [PeminjamanController::class, 'getBorrowingHistoryExportData'])->name('peminjaman.get_borrowing_export_data');
Route::get('/peminjaman/export-return-full-data', [PeminjamanController::class, 'getReturnHistoryExportData'])->name('peminjaman.get_return_export_data');

Route::get('/peminjaman/peminjam-detail', [PeminjamanController::class, 'getPeminjamDetail'])->name('peminjaman.peminjamDetail');

Route::get('/koleksi/detail', [StatistikKoleksi::class, 'getDetailKoleksi'])->name('koleksi.detail');

Route::get('/kunjungan/prodi-table/detail-pengunjung', [VisitHistory::class, 'getDetailPengunjung'])
    ->name('kunjungan.get_detail_pengunjung');

Route::get('/kunjungan/get_detail_pengunjung_harian', [VisitHistory::class, 'getDetailPengunjungHarian'])->name('kunjungan.get_detail_pengunjung_harian');

Route::get('/kunjungan/export-pdf', [VisitHistory::class, 'exportPdf'])->name('kunjungan.export-pdf');

Route::get('/kunjungan/get-detail-pengunjung-harian-export', [VisitHistory::class, 'getDetailPengunjungHarianExport'])->name('kunjungan.get_detail_pengunjung_harian_export');

Route::get('/statistik/keterpakaian-koleksi', [PenggunaanController::class, 'keterpakaianKoleksi'])->name('penggunaan.keterpakaian_koleksi');

Route::get('/statistik/keterpakaian-koleksi/detail', [PenggunaanController::class, 'getKeterpakaianDetail'])->name('statistik.keterpakaian_koleksi.detail');

Route::get('/kunjungan/get-lokasi-detail', [VisitHistory::class, 'getLokasiDetail'])->name('kunjungan.get_lokasi_detail');

Route::get('/reward/pemustaka-teraktif', [RewardController::class, 'pemustakaTeraktif'])->name('reward.pemustaka_teraktif')->middleware('auth');
Route::get('/reward/pemustaka-teraktif/export-csv', [RewardController::class, 'exportCsvPemustakaTeraktif'])->name('reward.export_csv_pemustaka_teraktif')->middleware('auth');
Route::get('/reward/peminjam-teraktif', [RewardController::class, 'peminjamTeraktif'])->name('reward.peminjam_teraktif')->middleware('auth');
Route::get('/reward/peminjam-teraktif/export-csv', [RewardController::class, 'exportCsvPeminjamTeraktif'])->name('reward.export_csv_peminjam_teraktif')->middleware('auth');

Route::get('/cek-histori-buku', [PenggunaanController::class, 'cekBuku'])->name('penggunaan.cek_histori');
Route::get('/statistik/sering-dibaca', [PenggunaanController::class, 'seringDibaca'])->name('penggunaan.sering_dibaca')->middleware('auth');

Route::get('/koleksi/rekap-fakultas', [StatistikKoleksi::class, 'rekapPerFakultas'])->name('koleksi.rekap_fakultas');

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    dd('clear');
});
