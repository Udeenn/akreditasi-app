<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitHistory;

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');


