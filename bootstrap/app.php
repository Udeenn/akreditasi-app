<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ── Alias middleware ──────────────────────────────────────────
        $middleware->alias([
            'admin'           => \App\Http\Middleware\CheckAdmin::class,
            'session.timeout' => \App\Http\Middleware\SessionTimeout::class,
            'log.activity'    => \App\Http\Middleware\LogActivity::class,
        ]);

        // ── Proxy trust ───────────────────────────────────────────────
        $middleware->trustProxies(at: '*');

        // ── CSRF Exemption ────────────────────────────────────────────
        $middleware->validateCsrfTokens(except: [
            'logout',
            'cas/logout'
        ]);

        // ── Web group tambahan ────────────────────────────────────────
        // LogActivity & SessionTimeout HARUS didaftarkan di sini karena
        // Laravel 12 mengabaikan app/Http/Kernel.php secara otomatis.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SessionTimeout::class,
            \App\Http\Middleware\LogActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 401);
            }

            return redirect()->guest(route('cas.login'));
        });
    })->create();
