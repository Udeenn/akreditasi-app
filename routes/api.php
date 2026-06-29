<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\KunjunganApiController;
use App\Http\Controllers\Api\KoleksiApiController;
use App\Http\Controllers\Api\PeminjamanApiController;
use App\Http\Controllers\Api\RewardApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('api.key')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // Kunjungan
    Route::prefix('kunjungan')->group(function () {
        Route::get('/harian', [KunjunganApiController::class, 'harian']);
        Route::get('/fakultas', [KunjunganApiController::class, 'fakultas']);
        Route::get('/prodi', [KunjunganApiController::class, 'prodi']);
    });

    // Koleksi
    Route::prefix('koleksi')->group(function () {
        Route::get('/statistik', [KoleksiApiController::class, 'statistik']);
        Route::get('/fakultas', [KoleksiApiController::class, 'fakultas']);
    });

    // Peminjaman
    Route::prefix('peminjaman')->group(function () {
        Route::get('/keseluruhan', [PeminjamanApiController::class, 'keseluruhan']);
        Route::get('/berlangsung', [PeminjamanApiController::class, 'berlangsung']);
    });

    // Reward
    Route::prefix('reward')->group(function () {
        Route::get('/pemustaka', [RewardApiController::class, 'pemustaka']);
        Route::get('/peminjam', [RewardApiController::class, 'peminjam']);
    });
});

