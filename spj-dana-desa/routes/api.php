<?php

use App\Http\Controllers\Api\Admin\BidangKegiatanController;
use App\Http\Controllers\Api\Admin\KampungController;
use App\Http\Controllers\Api\Admin\KegiatanController;
use App\Http\Controllers\Api\Admin\KodeRekeningController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PeriodeSpjController;
use App\Http\Controllers\Api\SpjDokumenController;
use App\Http\Controllers\Api\TransaksiController;
use Illuminate\Support\Facades\Route;

// Bab VI kajian teknis — seluruh endpoint diprefiks /api/v1/.
Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('login-2fa', [AuthController::class, 'login2fa'])->middleware('auth:sanctum');
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::prefix('transaksi')->group(function () {
            Route::get('/', [TransaksiController::class, 'index']);
            Route::post('/', [TransaksiController::class, 'store']);
            Route::post('sync-batch', [TransaksiController::class, 'syncBatch']);
            Route::get('{transaksi}', [TransaksiController::class, 'show']);
            Route::put('{transaksi}', [TransaksiController::class, 'update']);
            Route::post('{transaksi}/bukti', [TransaksiController::class, 'uploadBukti']);
            Route::post('{transaksi}/verifikasi-ocr', [TransaksiController::class, 'verifikasiOcr']);
            Route::post('{transaksi}/ajukan', [TransaksiController::class, 'ajukan']);
        });

        Route::prefix('ai')->group(function () {
            Route::get('ocr-status/{bukti}', [AiController::class, 'ocrStatus']);
            Route::post('narasi', [AiController::class, 'narasi']);
            Route::post('cek-kewajaran/{transaksi}', [AiController::class, 'cekKewajaran']);
            Route::post('chat', [AiController::class, 'chat']);
        });

        Route::prefix('periode-spj')->group(function () {
            Route::get('/', [PeriodeSpjController::class, 'index']);
            Route::get('{periodeSpj}', [PeriodeSpjController::class, 'show']);
            Route::post('{periodeSpj}/ajukan', [PeriodeSpjController::class, 'ajukan']);
            Route::post('{periodeSpj}/setujui', [PeriodeSpjController::class, 'setujui']);
            Route::post('{periodeSpj}/tolak', [PeriodeSpjController::class, 'tolak']);
            Route::post('{periodeSpj}/generate-pdf', [PeriodeSpjController::class, 'generatePdf']);
            Route::get('{periodeSpj}/export-siskeudes', [PeriodeSpjController::class, 'exportSiskeudes']);
        });

        Route::get('spj-dokumen/{spjDokumen}', [SpjDokumenController::class, 'show']);

        Route::prefix('dashboard')->middleware('role:pendamping,inspektorat,admin')->group(function () {
            Route::get('ringkasan', [DashboardController::class, 'ringkasan']);
            Route::get('kampung/{kampung}', [DashboardController::class, 'kampung']);
            Route::get('transaksi-flagged', [DashboardController::class, 'transaksiFlagged']);
        });

        // KF-17 — modul admin: master data kampung, kode rekening, bidang kegiatan, kegiatan,
        // pagu anggaran, dan akun pengguna.
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::apiResource('kampung', KampungController::class);

            Route::get('kode-rekening', [KodeRekeningController::class, 'index']);
            Route::post('kode-rekening', [KodeRekeningController::class, 'store']);
            Route::put('kode-rekening/{kodeRekening}', [KodeRekeningController::class, 'update']);

            Route::get('bidang-kegiatan', [BidangKegiatanController::class, 'index']);
            Route::post('bidang-kegiatan', [BidangKegiatanController::class, 'store']);

            Route::get('kegiatan', [KegiatanController::class, 'index']);
            Route::post('kegiatan', [KegiatanController::class, 'store']);
            Route::get('kegiatan/{kegiatan}', [KegiatanController::class, 'show']);
            Route::put('kegiatan/{kegiatan}', [KegiatanController::class, 'update']);
            Route::put('kegiatan/{kegiatan}/pagu', [KegiatanController::class, 'setPagu']);

            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store']);
            Route::put('users/{user}', [UserController::class, 'update']);
            Route::put('users/{user}/wilayah-binaan', [UserController::class, 'setWilayahBinaan']);
        });
    });
});
