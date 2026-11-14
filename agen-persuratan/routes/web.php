<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisposisiController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\PencarianController;
use App\Http\Controllers\SuratKeluarController;
use App\Http\Controllers\SuratMasukController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'form'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.proses');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/cari', PencarianController::class)->name('cari');
    Route::get('/dokumen/{dokumen}', [DokumenController::class, 'unduh'])->name('dokumen.unduh');

    // Surat masuk
    Route::middleware('peran:admin,operator')->group(function () {
        Route::get('/surat-masuk/baru', [SuratMasukController::class, 'create'])->name('surat-masuk.create');
        Route::post('/surat-masuk', [SuratMasukController::class, 'store'])->name('surat-masuk.store');
    });
    Route::get('/surat-masuk', [SuratMasukController::class, 'index'])->name('surat-masuk.index');
    Route::get('/surat-masuk/{suratMasuk}', [SuratMasukController::class, 'show'])->name('surat-masuk.show');

    // Disposisi
    Route::post('/surat-masuk/{suratMasuk}/disposisi', [DisposisiController::class, 'store'])
        ->middleware('peran:admin,pimpinan')->name('disposisi.store');
    Route::patch('/disposisi/{disposisi}', [DisposisiController::class, 'update'])->name('disposisi.update');

    // Surat keluar (staf tidak mengakses register keluar)
    Route::middleware('peran:admin,operator,pimpinan')->group(function () {
        Route::get('/surat-keluar', [SuratKeluarController::class, 'index'])->name('surat-keluar.index');
        Route::middleware('peran:admin,operator')->group(function () {
            Route::get('/surat-keluar/baru', [SuratKeluarController::class, 'create'])->name('surat-keluar.create');
            Route::post('/surat-keluar', [SuratKeluarController::class, 'store'])->name('surat-keluar.store');
        });
        Route::get('/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'show'])->name('surat-keluar.show');
    });
});
