<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Tambahkan URI di sini jika ada yang tidak perlu dicek CSRF token-nya
        // Contoh: 'api/*', // API biasanya tidak butuh CSRF jika pakai token auth
        // Contoh: 'stripe/*', // Webhook dari pihak ketiga
    ];
}
