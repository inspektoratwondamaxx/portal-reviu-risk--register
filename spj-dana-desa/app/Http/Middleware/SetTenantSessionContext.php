<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyuntikkan variabel sesi app.current_kampung_id / app.current_role pada koneksi basis data
 * di awal request (Bab VI.7), agar kebijakan Row-Level Security Postgres (Bab V.4) konsisten
 * dengan otorisasi di level aplikasi. Pelengkap App\Models\Concerns\BelongsToKampung.
 *
 * Catatan: memakai SET sesi biasa (bukan SET LOCAL) karena Laravel memegang satu koneksi PDO
 * per request pada model PHP-FPM standar. Bila basis data diakses lewat connection pooler mode
 * transaksi (mis. PgBouncer transaction pooling), pendekatan ini perlu diganti membungkus
 * request dalam transaksi eksplisit agar SET LOCAL berlaku benar.
 */
class SetTenantSessionContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (DB::getDriverName() === 'pgsql') {
            // set_config(..., false) diparameterkan lewat prepared statement — aman dari injeksi,
            // berbeda dengan SET yang hanya menerima literal.
            $user = Auth::user();

            DB::statement("SELECT set_config('app.current_kampung_id', ?, false)", [(string) ($user?->kampung_id ?? '')]);
            DB::statement("SELECT set_config('app.current_role', ?, false)", [(string) ($user?->role ?? '')]);
        }

        return $next($request);
    }
}
