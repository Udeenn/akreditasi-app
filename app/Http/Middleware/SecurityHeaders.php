<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders Middleware
 *
 * Menambahkan HTTP security headers untuk hardening keamanan web.
 * Melengkapi header yang sudah ada di Nginx (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection).
 */
class SecurityHeaders
{
    /**
     * CDN yang diizinkan di CSP.
     * Sesuaikan jika menambahkan sumber baru.
     */
    protected array $allowedScriptSources = [
        "'self'",
        "'unsafe-inline'",      // Diperlukan karena banyak inline script di layout
        "'unsafe-eval'",        // Diperlukan oleh beberapa library chart
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',
        'https://code.jquery.com',
        'https://unpkg.com',
        'https://cdn.datatables.net',
        'https://static.cloudflareinsights.com',
    ];

    protected array $allowedStyleSources = [
        "'self'",
        "'unsafe-inline'",      // Diperlukan oleh inline <style> di layout
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',
        'https://fonts.googleapis.com',
        'https://cdn.datatables.net',
    ];

    protected array $allowedFontSources = [
        "'self'",
        'https://fonts.gstatic.com',
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',
        'data:',                // Font Awesome menggunakan data: URI
    ];

    protected array $allowedImgSources = [
        "'self'",
        'data:',                // Inline base64 images
        'blob:',
        'https:',               // Izinkan semua HTTPS image (chart, avatar, dll)
    ];

    protected array $allowedConnectSources = [
        "'self'",
        'https://cdn.jsdelivr.net',
        'https://cdn.datatables.net',
        'https://cloudflareinsights.com',
    ];

    protected array $allowedFrameSources = [
        "'none'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ── Skip untuk binary responses (file download, stream) ──
        $contentType = $response->headers->get('Content-Type', '');
        if ($this->isBinaryResponse($contentType)) {
            return $response;
        }

        // ── 1. HSTS (HTTP Strict Transport Security) ──────────────────
        // Hanya aktif di HTTPS (production). Di local/HTTP diabaikan browser.
        if ($request->isSecure() || config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // ── 2. Content Security Policy ────────────────────────────────
        $csp = $this->buildCsp();
        $response->headers->set('Content-Security-Policy', $csp);

        // ── 3. Referrer Policy ────────────────────────────────────────
        // strict-origin-when-cross-origin: kirim origin saja ke cross-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // ── 4. Permissions Policy ─────────────────────────────────────
        // Matikan fitur browser yang tidak dipakai aplikasi ini
        $response->headers->set('Permissions-Policy', implode(', ', [
            'camera=()',
            'microphone=()',
            'geolocation=()',
            'payment=()',
            'usb=()',
            'accelerometer=()',
            'gyroscope=()',
            'magnetometer=()',
        ]));

        // ── 5. X-Content-Type-Options (backup dari Nginx) ─────────────
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // ── 6. X-Frame-Options (backup dari Nginx) ────────────────────
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // ── 7. X-Permitted-Cross-Domain-Policies ─────────────────────
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // ── 8. Hapus header yang membocorkan info server ──────────────
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function buildCsp(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src " . implode(' ', $this->allowedScriptSources),
            "style-src "  . implode(' ', $this->allowedStyleSources),
            "font-src "   . implode(' ', $this->allowedFontSources),
            "img-src "    . implode(' ', $this->allowedImgSources),
            "connect-src " . implode(' ', $this->allowedConnectSources),
            "frame-src "  . implode(' ', $this->allowedFrameSources),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests",
        ];

        return implode('; ', $directives);
    }

    private function isBinaryResponse(string $contentType): bool
    {
        $binaryTypes = [
            'application/pdf',
            'application/octet-stream',
            'application/vnd.',
            'image/',
            'video/',
            'audio/',
        ];

        foreach ($binaryTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }
}
