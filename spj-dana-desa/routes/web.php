<?php

use App\Http\Controllers\Web\Admin\KampungController as AdminKampungController;
use App\Http\Controllers\Web\Admin\KegiatanController as AdminKegiatanController;
use App\Http\Controllers\Web\Admin\KodeRekeningController as AdminKodeRekeningController;
use App\Http\Controllers\Web\Admin\UserController as AdminUserController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PeriodeSpjController;
use App\Http\Controllers\Web\TransaksiController;
use Illuminate\Support\Facades\Route;

// Dashboard web (Bab IV.1: Laravel, session-based) — pemeriksaan, persetujuan, master data.
// Input transaksi harian tetap lewat aplikasi Android (KF-01..KF-04); dashboard ini tidak
// menyediakan form input transaksi baru, hanya peninjauan & alur persetujuan berjenjang.
Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('login/2fa', [AuthController::class, 'show2fa'])->name('login.2fa');
    Route::post('login/2fa', [AuthController::class, 'verify2fa']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/kampung/{kampung}', [DashboardController::class, 'kampung'])->name('dashboard.kampung');

    Route::get('transaksi/{transaksi}', [TransaksiController::class, 'show'])->name('transaksi.show');

    Route::prefix('spj')->name('spj.')->group(function () {
        Route::get('/', [PeriodeSpjController::class, 'index'])->name('index');
        Route::get('{periodeSpj}', [PeriodeSpjController::class, 'show'])->name('show');
        Route::post('{periodeSpj}/ajukan', [PeriodeSpjController::class, 'ajukan'])->name('ajukan');
        Route::post('{periodeSpj}/setujui', [PeriodeSpjController::class, 'setujui'])->name('setujui');
        Route::post('{periodeSpj}/tolak', [PeriodeSpjController::class, 'tolak'])->name('tolak');
        Route::post('{periodeSpj}/generate-pdf', [PeriodeSpjController::class, 'generatePdf'])->name('generate-pdf');
        Route::get('{periodeSpj}/unduh', [PeriodeSpjController::class, 'unduhPdf'])->name('unduh');
        Route::get('{periodeSpj}/export-siskeudes', [PeriodeSpjController::class, 'exportSiskeudes'])->name('export-siskeudes');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('kampung', [AdminKampungController::class, 'index'])->name('kampung.index');
        Route::post('kampung', [AdminKampungController::class, 'store'])->name('kampung.store');
        Route::put('kampung/{kampung}', [AdminKampungController::class, 'update'])->name('kampung.update');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::put('users/{user}/wilayah-binaan', [AdminUserController::class, 'setWilayahBinaan'])->name('users.wilayah-binaan');

        Route::get('kode-rekening', [AdminKodeRekeningController::class, 'index'])->name('kode-rekening.index');
        Route::post('kode-rekening', [AdminKodeRekeningController::class, 'store'])->name('kode-rekening.store');

        Route::get('kegiatan', [AdminKegiatanController::class, 'index'])->name('kegiatan.index');
        Route::post('kegiatan', [AdminKegiatanController::class, 'store'])->name('kegiatan.store');
        Route::get('kegiatan/{kegiatan}', [AdminKegiatanController::class, 'show'])->name('kegiatan.show');
        Route::put('kegiatan/{kegiatan}/pagu', [AdminKegiatanController::class, 'setPagu'])->name('kegiatan.pagu');
    });
});
