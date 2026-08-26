<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Middleware role-gate: route('...')->middleware('role:inspektorat,admin'). */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses untuk sumber daya ini.');
        }

        return $next($request);
    }
}
