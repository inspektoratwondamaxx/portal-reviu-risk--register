<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        abort_unless($request->user()?->punyaPeran(...$peran), 403, 'Anda tidak memiliki hak akses untuk fitur ini.');

        return $next($request);
    }
}
