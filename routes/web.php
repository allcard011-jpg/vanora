<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

// Sementara: halaman bawaan. Diganti beranda Vanora di Fase 6.
Route::view('/', 'welcome');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    });
});
