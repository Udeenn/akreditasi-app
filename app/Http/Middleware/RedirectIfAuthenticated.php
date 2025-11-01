<?php

namespace App\Http\Middleware; // Pastikan namespace benar

use App\Providers\RouteServiceProvider; // Menggunakan Service Provider default Laravel
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Menggunakan facade Auth
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated // Pastikan nama class benar
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            // Jika user sudah login (Auth::guard($guard)->check()),
            // diarahkan ke RouteServiceProvider::HOME (biasanya '/dashboard')
            if (Auth::guard($guard)->check()) {
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
