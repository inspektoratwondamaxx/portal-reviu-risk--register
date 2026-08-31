<?php

use App\Http\Controllers\Api\V1\HargaController;
use Illuminate\Support\Facades\Route;

/*
 * Integrasi SIPD Level 2 - REST API (Bab 21 kajian). Baca-saja & publik: menyajikan data yang
 * sama dengan yang bisa dicari lewat website publik tanpa login (SSH/SBU/HSPK/ASB aktif), agar
 * bisa dikonsumsi sistem lain (mis. SIPD daerah). Dibatasi laju (throttle) untuk mencegah abuse.
 */
Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1')->group(function () {
    Route::get('/ringkasan', [HargaController::class, 'ringkasan'])->name('ringkasan');

    Route::get('/ssh', [HargaController::class, 'ssh'])->name('ssh.index');
    Route::get('/ssh/{ssh:kode_barang}', [HargaController::class, 'sshShow'])->name('ssh.show');

    Route::get('/sbu', [HargaController::class, 'sbu'])->name('sbu.index');
    Route::get('/sbu/{sbu:kode}', [HargaController::class, 'sbuShow'])->name('sbu.show');

    Route::get('/hspk', [HargaController::class, 'hspk'])->name('hspk.index');
    Route::get('/hspk/{hspk:kode}', [HargaController::class, 'hspkShow'])->name('hspk.show');

    Route::get('/asb', [HargaController::class, 'asb'])->name('asb.index');
    Route::get('/asb/{asb:kode}', [HargaController::class, 'asbShow'])->name('asb.show');
});

Route::middleware('auth:sanctum')->get('/user', function (\Illuminate\Http\Request $request) {
    return $request->user();
});
