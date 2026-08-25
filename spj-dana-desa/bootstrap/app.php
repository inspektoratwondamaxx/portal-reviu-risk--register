<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetTenantSessionContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        $middleware->appendToGroup('web', SetTenantSessionContext::class);
        $middleware->appendToGroup('api', SetTenantSessionContext::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());

        // AuthenticationException::render()/Handler::unauthenticated() memakai expectsJson()
        // secara langsung (mengabaikan shouldRenderJsonWhen), sehingga request api/* tanpa
        // header Accept: application/json akan coba redirect ke route "login" yang tidak ada
        // pada aplikasi API-only ini. Format response gagal wajib tetap JSON sesuai Bab VI.1.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Autentikasi diperlukan.'], 401);
            }
        });
    })->create();
