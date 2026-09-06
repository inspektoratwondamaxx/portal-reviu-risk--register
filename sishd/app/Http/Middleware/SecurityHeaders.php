<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar untuk seluruh respons HTML.
 *
 * Yang paling penting di sini adalah X-Frame-Options: tanpa itu halaman aplikasi bisa dimuat di
 * dalam <iframe> tersembunyi milik situs lain, lalu tombol yang tampak biasa di situs tersebut
 * sebenarnya menekan tombol "Setujui" pada usulan harga yang sedang dibuka pejabat berwenang
 * (clickjacking) — persetujuan terjadi tanpa disadari pemiliknya.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');

        // HSTS hanya bermakna (dan hanya aman dipasang) ketika permintaannya sudah lewat HTTPS.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
