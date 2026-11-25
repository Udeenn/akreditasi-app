<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Route name prefix / pattern yang TIDAK dicatat.
     * Hindari noise dari AJAX data-table, export CSV, dll.
     */
    protected array $skipRoutePatterns = [
        'cas.logout',
        'cas.login',
        'cas.callback',
        'clear-cache',
        // Route yang mengembalikan JSON / data mentah (bukan page view)
        '*.data',
        '*.detail',
        '*.prodiData',
        '*.export*',
        '*.get_*',
        'kunjungan.get_*',
        'peminjaman.berlangsung_data',
        'penggunaan.keterpakaian_koleksi.detail',
    ];

    /**
     * URL fragment yang diskip (untuk request AJAX non-route-named)
     */
    protected array $skipUrlFragments = [
        '/export',
        '/data',
        '/detail',
        '/chart',
        'full-data',
        'export-csv',
        'export-pdf',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Hanya catat request dari user yang sudah login
        if (!Auth::check()) {
            return $response;
        }

        // Skip AJAX / JSON requests
        if ($request->ajax() || $request->wantsJson()) {
            return $response;
        }

        // Skip non-GET kecuali POST
        if (!in_array($request->method(), ['GET', 'POST'])) {
            return $response;
        }

        // Skip berdasarkan route name
        $routeName = $request->route()?->getName() ?? '';
        if ($this->shouldSkipRoute($routeName)) {
            return $response;
        }

        // Skip berdasarkan URL fragment
        if ($this->shouldSkipUrl($request->path())) {
            return $response;
        }

        // Skip halaman activity log itu sendiri (hindari loop)
        if (str_starts_with($routeName, 'admin.activity')) {
            return $response;
        }

        $user = Auth::user();

        try {
            ActivityLog::create([
                'user_id'     => $user->id,
                'username'    => $user->cas_username,
                'user_name'   => $user->name,
                'user_role'   => $user->role,
                'ip_address'  => $request->ip(),
                'method'      => $request->method(),
                'url'         => $request->fullUrl(),
                'route_name'  => $routeName ?: null,
                'user_agent'  => substr($request->userAgent() ?? '', 0, 500),
                'status_code' => $response->getStatusCode(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[ActivityLog] FAILED: ' . $e->getMessage());
        }

        return $response;
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function shouldSkipRoute(string $routeName): bool
    {
        if (empty($routeName)) return false;

        foreach ($this->skipRoutePatterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // Convert glob-style pattern to regex
                $regex = '/^' . str_replace(['.', '*'], ['\.', '.*'], $pattern) . '$/';
                if (preg_match($regex, $routeName)) return true;
            } elseif ($routeName === $pattern) {
                return true;
            }
        }
        return false;
    }

    private function shouldSkipUrl(string $path): bool
    {
        foreach ($this->skipUrlFragments as $fragment) {
            if (str_contains($path, $fragment)) return true;
        }
        return false;
    }
}
