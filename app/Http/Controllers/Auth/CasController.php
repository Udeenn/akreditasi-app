<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\LoginLog;
use phpCAS;

class CasController extends Controller
{
    /**
     * Inisialisasi phpCAS client
     */
    private function initializeCas(): void
    {
        // Cegah inisialisasi berulang
        if (!phpCAS::isInitialized()) {
            phpCAS::setVerbose(config('app.debug'));
            
            // Konversi version string ke konstanta CAS
            $version = config('cas.version', '2.0');
            $casVersion = ($version === '3.0') ? CAS_VERSION_3_0 : CAS_VERSION_2_0;
            
            phpCAS::client(
                $casVersion,
                config('cas.host'),
                (int) config('cas.port', 443),
                config('cas.context', '/cas'),
                config('cas.service_base_url', config('app.url'))
            );
            
            // Untuk development/testing, nonaktifkan validasi SSL
            // Di production, sebaiknya gunakan setCasServerCACert
            if (config('app.env') === 'local' || config('cas.disable_ssl_validation', false)) {
                phpCAS::setNoCasServerValidation();
            } else {
                // phpCAS::setCasServerCACert('/path/to/ca-bundle.crt');
                phpCAS::setNoCasServerValidation(); // Temporary untuk testing
            }
        }
    }

    /**
     * Handle CAS login - redirect ke CAS server
     */
    public function login()
    {
        // Jika sudah login di Laravel, langsung ke dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $this->initializeCas();
        
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
        $this->initializeCas();
        
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
        
        // Log successful login
        LoginLog::logSuccess($user->id, $casUser, 'cas');
        
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
        
        $this->initializeCas();
        
        // Logout dari CAS dan redirect ke halaman utama
        phpCAS::logout(['service' => config('app.url')]);
    }
}

