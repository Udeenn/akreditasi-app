<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Batas waktu idle (menit) sebelum otomatis logout.
     * Ambil dari config atau default 30 menit.
     */
    protected int $timeoutMinutes;

    public function __construct()
    {
        $this->timeoutMinutes = (int) config('session.idle_timeout', 30);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya berlaku untuk user yang sudah login
        if (!Auth::check()) {
            return $next($request);
        }

        $lastActivity = $request->session()->get('last_activity_time');

        if ($lastActivity !== null) {
            $idleSeconds = time() - $lastActivity;
            $timeoutSeconds = $this->timeoutMinutes * 60;

            if ($idleSeconds > $timeoutSeconds) {
                // Update last_activity sebelum logout agar tidak loop
                $request->session()->forget('last_activity_time');

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Jika AJAX / JSON → kembalikan 401 dengan JSON
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.',
                        'session_expired' => true,
                    ], 401);
                }

                return redirect()->route('cas.login')
                    ->with('warning', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.');
            }
        }

        // Perbarui timestamp aktivitas terakhir di setiap request
        $request->session()->put('last_activity_time', time());

        return $next($request);
    }
}
