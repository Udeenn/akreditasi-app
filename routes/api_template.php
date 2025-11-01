<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IjazahController;
use App\Http\Controllers\Api\MouController;
use App\Http\Controllers\Api\PelatihanController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SertifikasiController;
use App\Http\Controllers\Api\SkpController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\StatistikController;
use App\Http\Controllers\Api\StatistikKoleksiController;
use App\Http\Controllers\Api\TranskripController;
use App\Http\Controllers\Api\PeminjamanController;
use App\Http\Controllers\Api\VisitHistoryController;

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/credit', [DashboardController::class, 'credit']);


