<?php

use Illuminate\Support\Facades\Route;

/*
 * Jalur integrasi SIPD Level 2 - REST API (Bab 21 kajian), dipersiapkan untuk kebutuhan mendatang.
 * Belum diaktifkan penuh — versi berjalan memakai Level 1 (export Excel format SIPD).
 */
Route::middleware('auth:sanctum')->get('/user', function (\Illuminate\Http\Request $request) {
    return $request->user();
});
