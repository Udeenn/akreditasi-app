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

        // Validate ticket with CAS server
        $username = $this->validateTicket($ticket);

        if (!$username) {
            return redirect()->route('home')->with('error', 'Login gagal: validasi tiket CAS gagal. Silakan coba lagi.');
        }

        Log::info('CAS user authenticated', ['username' => $username]);

        // Find or create user
        $user = $this->findOrCreateUser($username);

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
        return config('cas.service_url', config('cas.service_base_url', config('app.url')) . '/cas/callback');
    }

    protected function buildCasUrl(string $path, array $params = []): string
    {
        $host    = config('cas.host');
        $port    = config('cas.port');
        $context = config('cas.context');

        $scheme  = ($port == 443) ? 'https' : 'http';
        $portStr = (($scheme === 'https' && $port == 443) || ($scheme === 'http' && $port == 80)) ? '' : ":{$port}";

        $url = "{$scheme}://{$host}{$portStr}{$context}{$path}";

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    protected function validateTicket(string $ticket): ?string
    {
        $validateUrl = $this->buildCasUrl('/serviceValidate', [
            'service' => $this->getServiceUrl(),
            'ticket'  => $ticket,
        ]);

        Log::info('CAS validating ticket', ['url' => $validateUrl]);

        try {
            $response = Http::withOptions([
                'verify'  => !config('cas.disable_ssl_validation'),
                'timeout' => 10,
            ])->get($validateUrl);

            Log::info('CAS validation response', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if ($response->successful()) {
                $body = $response->body();
                if (preg_match('/<cas:user>(.*?)<\/cas:user>/s', $body, $matches)) {
                    return trim($matches[1]);
                }
            }
        } catch (\Exception $e) {
            Log::error('CAS validation exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function findOrCreateUser(string $username): ?User
    {
        // Always fetch latest patron data from Koha to sync categorycode/role
        $patron = null;
        try {
            $kohaService = app(KohaService::class);
            $patron = $kohaService->getPatronByUsername($username);
        } catch (\Exception $e) {
            Log::error("Error fetching patron from Koha", ['username' => $username, 'error' => $e->getMessage()]);
        }

        // Ambil categorycode dari response Koha
        // Koha REST API mengembalikan field 'category_id', bukan 'categorycode'
        $categorycode = $patron['category_id'] ?? $patron['categorycode'] ?? 'S';

        // Gunakan resolveRole() dari KohaService agar konsisten dengan config KOHA_LIBRARIAN_CATEGORIES
        $role = $kohaService->resolveRole($categorycode);

        $user = User::where('cas_username', $username)->first();

        if ($user) {
            // Always sync role & categorycode from Koha on every login
            $user->update([
                'role'           => $role,
                'categorycode'   => $categorycode,
                'koha_patron_id' => $patron['patron_id'] ?? $user->koha_patron_id,
                'cardnumber'     => $patron['cardnumber'] ?? $user->cardnumber,
                'name'           => $patron ? trim(($patron['firstname'] ?? '') . ' ' . ($patron['surname'] ?? '')) : $user->name,
            ]);

            Log::info("User synced from Koha", ['username' => $username, 'role' => $role, 'categorycode' => $categorycode]);
            return $user;
        }

        // New user — create from Koha data or basic fallback
        if ($patron) {
            return User::create([
                'cas_username'   => $username,
                'koha_patron_id' => $patron['patron_id'] ?? null,
                'name'           => trim(($patron['firstname'] ?? '') . ' ' . ($patron['surname'] ?? '')),
                'email'          => $patron['email'] ?? null,
                'categorycode'   => $categorycode,
                'role'           => $role,
                'cardnumber'     => $patron['cardnumber'] ?? null,
                'password'       => bcrypt(str()->random(32)),
            ]);
        }

        // Koha not reachable — create basic user
        Log::warning("CAS user {$username} not found in Koha, creating basic user");
        return User::create([
            'cas_username' => $username,
            'name'         => $username,
            'role'         => 'patron',
            'categorycode' => 'S',
            'password'     => bcrypt(str()->random(32)),
        ]);
    }
}
