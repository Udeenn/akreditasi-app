<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\KohaService;

class CasController extends Controller
{
    /**
     * Redirect to CAS login page
     */
    public function login(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $casLoginUrl = $this->buildCasUrl('/login', ['service' => $this->getServiceUrl()]);

        Log::info('CAS Login redirect', ['url' => $casLoginUrl, 'service' => $this->getServiceUrl()]);

        return redirect($casLoginUrl);
    }

    /**
     * Handle CAS callback with ticket validation
     */
    public function callback(Request $request)
    {
        $ticket = $request->get('ticket');

        if (!$ticket) {
            return redirect()->route('home')->with('error', 'Login gagal: tidak ada tiket CAS.');
        }

        Log::info('CAS callback received', ['ticket' => substr($ticket, 0, 20) . '...']);

        // Validate ticket with CAS server and get user data
        $casData = $this->validateTicket($ticket);

        if (!$casData || empty($casData['username'])) {
            return redirect()->route('home')->with('error', 'Login gagal: validasi tiket CAS gagal. Silakan coba lagi.');
        }

        Log::info('CAS user authenticated', ['username' => $casData['username']]);

        // Find or create user using the combined SSO and Koha data
        $user = $this->findOrCreateUser($casData);

        if (!$user) {
            return redirect()->route('home')->with('error', 'Akun Anda tidak ditemukan di sistem perpustakaan Koha. Hubungi pustakawan.');
        }

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }

    /**
     * Logout and redirect to CAS logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $logoutUrl = $this->buildCasUrl('/logout', ['service' => config('app.url')]);

        return redirect($logoutUrl);
    }

    /**
     * Dev-only: login as a test user (only in local environment)
     */
    public function devLogin(Request $request)
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $request->validate([
            'username' => 'required|string',
            'role' => 'required|in:patron,librarian',
        ]);

        $user = User::firstOrCreate(
            ['cas_username' => $request->username],
            [
                'name' => 'Dev ' . ucfirst($request->role) . ' (' . $request->username . ')',
                'email' => $request->username . '@dev.ums.ac.id',
                'role' => $request->role,
                'categorycode' => $request->role === 'librarian' ? 'LIBRARIAN' : 'S',
                'cardnumber' => 'DEV-' . strtoupper($request->username),
                'koha_patron_id' => rand(1000, 9999),
                'password' => bcrypt('dev-password'),
            ]
        );

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'))->with('login_success', true);
    }

    // ============================
    // Private helpers
    // ============================

    protected function getServiceUrl(): string
    {
        $url = config('cas.service_base_url', config('app.url'));
        
        // Cek apakah url dari .env sudah mengandung /cas/callback
        if (str_ends_with($url, '/cas/callback')) {
            return $url;
        }
        
        return rtrim($url, '/') . '/cas/callback';
    }

    protected function buildCasUrl(string $path, array $params = []): string
    {
        $host = config('cas.host');
        $port = config('cas.port');
        $context = config('cas.context');

        $scheme = ($port == 443) ? 'https' : 'http';
        $portStr = (($scheme === 'https' && $port == 443) || ($scheme === 'http' && $port == 80)) ? '' : ":{$port}";

        $url = "{$scheme}://{$host}{$portStr}{$context}{$path}";

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    protected function validateTicket(string $ticket): ?array
    {
        $validateUrl = $this->buildCasUrl('/serviceValidate', [
            'service' => $this->getServiceUrl(),
            'ticket' => $ticket,
        ]);

        Log::info('CAS validating ticket', ['url' => $validateUrl]);

        try {
            $response = Http::withOptions([
                'verify' => !config('cas.disable_ssl_validation'),
                'timeout' => 10,
            ])->get($validateUrl);

            Log::info('CAS validation response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $body = $response->body();
                
                $userData = [];
                
                if (preg_match('/<cas:user>(.*?)<\/cas:user>/s', $body, $matches)) {
                    $userData['username'] = trim($matches[1]);
                } else {
                    return null;
                }
                
                // Coba ambil nama dan email dari atribut CAS (format standar JASIG/Apereo)
                if (preg_match('/<cas:nama>(.*?)<\/cas:nama>/s', $body, $matches) || preg_match('/<cas:name>(.*?)<\/cas:name>/s', $body, $matches) || preg_match('/<cas:fullName>(.*?)<\/cas:fullName>/s', $body, $matches)) {
                    $userData['name'] = trim($matches[1]);
                }
                
                if (preg_match('/<cas:email>(.*?)<\/cas:email>/s', $body, $matches)) {
                    $userData['email'] = trim($matches[1]);
                }

                return $userData;
            }
        } catch (\Exception $e) {
            Log::error('CAS validation exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function findOrCreateUser(array $casData): ?User
    {
        $username = $casData['username'];
        $ssoName = $casData['name'] ?? null;
        $ssoEmail = $casData['email'] ?? null;

        // Fetch latest patron data from Koha to sync categorycode
        $patron = null;
        try {
            $kohaService = app(KohaService::class);
            // Kodes di KohaService sekarang memakai cardnumber dulu baru userid
            $patron = $kohaService->getPatronByUsername($username);
        } catch (\Exception $e) {
            Log::error("Error fetching patron from Koha", ['username' => $username, 'error' => $e->getMessage()]);
        }

        // Determine categorycode and role from Koha exactly as requested
        $categorycode = $patron['category_id'] ?? $patron['categorycode'] ?? 'S';
        
        // Memakai aturan yang kamu berikan:
        $role = (strtoupper($categorycode) === 'LIBRARIAN') ? 'librarian' : 'patron';

        $user = User::where('cas_username', $username)->first();

        // Data prioritas: Koha -> SSO -> Default
        $kohaName = trim(($patron['firstname'] ?? '') . ' ' . ($patron['surname'] ?? ''));
        $finalName = (!empty($kohaName) ? $kohaName : ($ssoName ?: $username));
        $finalEmail = (!empty($patron['email']) ? $patron['email'] : ($ssoEmail ?: null));

        if ($user) {
            // Update sinkronisasi sesuai instruksimu
            $user->update([
                'role'          => $role,
                'categorycode'  => $categorycode,
                'koha_patron_id' => $patron['patron_id'] ?? $user->koha_patron_id,
                'cardnumber'    => $patron['cardnumber'] ?? $user->cardnumber,
                'name'          => $finalName,
                'email'         => $finalEmail,
            ]);

            Log::info("User synced from Koha and SSO", ['username' => $username, 'role' => $role, 'categorycode' => $categorycode]);
            return $user;
        }

        // New user — create from SSO and Koha data exactly as requested
        if ($patron) {
            return User::create([
                'cas_username'   => $username,
                'koha_patron_id' => $patron['patron_id'] ?? null,
                'name'           => $finalName,
                'email'          => $finalEmail,
                'categorycode'   => $categorycode,
                'role'           => $role,
                'cardnumber'     => $patron['cardnumber'] ?? null,
                'password'       => bcrypt(str()->random(32)),
            ]);
        }

        // Jika tidak ketemu di Koha tapi berhasil SSO
        Log::warning("CAS user {$username} not found in Koha, creating basic user");
        return User::create([
            'cas_username' => $username,
            'name'         => $finalName,
            'email'        => $finalEmail,
            'role'         => 'patron',
            'categorycode' => 'S',
            'password'     => bcrypt(str()->random(32)),
        ]);
    }
}
