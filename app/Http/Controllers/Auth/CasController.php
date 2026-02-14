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
        // dd($attributes); // DEBUG CAS - Removed
        
        // Cari atau buat user, dan update data terbaru dari CAS
        // Cari atau buat user, dan update data terbaru dari CAS
        $user = User::firstOrNew(['username' => $casUser]);
        
        $user->email = $attributes['mail'] ?? $casUser . '@ums.ac.id';
        $user->name = $attributes['full_name'] ?? $attributes['displayName'] ?? $attributes['cn'] ?? $attributes['description'] ?? $casUser;
        
        if (!$user->exists) {
            $user->password = bcrypt(str()->random(32));
        }
        
        $user->save();
        
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

