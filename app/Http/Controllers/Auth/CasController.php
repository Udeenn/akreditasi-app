<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use phpCAS;

class CasController extends Controller
{
    /**
     * Inisialisasi phpCAS client
     */

    /**
     * Handle CAS login - redirect ke CAS server
     */
    public function login()
    {
        // Jika sudah login di Laravel, langsung ke dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }


        
        // Force authentication - akan redirect ke CAS jika belum login
        phpCAS::forceAuthentication();
        
        // Jika sudah terautentikasi di CAS, proses login
        return $this->handleAuthentication();
    }

    /**
     * Handle CAS callback setelah login berhasil
     */
    public function callback()
    {

        
        // Cek apakah sudah terautentikasi
        if (phpCAS::checkAuthentication()) {
            return $this->handleAuthentication();
        }


        return redirect('/')->with('error', 'Login gagal. Silakan coba lagi.');
    }

    /**
     * Proses autentikasi setelah CAS berhasil
     */
    private function handleAuthentication()
    {
        $casUser = phpCAS::getUser();
        $attributes = phpCAS::getAttributes();
        
        // Cari atau buat user berdasarkan username CAS
        $user = User::firstOrCreate(
            ['username' => $casUser],
            [
                'email' => $attributes['mail'] ?? $casUser . '@ums.ac.id',
                'password' => bcrypt(str()->random(32)),
                'name' => $attributes['displayName'] ?? $attributes['cn'] ?? $casUser,
            ]
        );
        
        // Login ke Laravel
        Auth::login($user, true);
        
        // Redirect ke halaman yang diminta sebelumnya, atau dashboard
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle logout - logout dari Laravel dan CAS
     */
    public function logout(Request $request)
    {
        // Logout dari Laravel
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        

        
        // Logout dari CAS dan redirect ke halaman utama
        phpCAS::logout(['service' => config('app.url')]);
    }
}

