<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KohaService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('koha.base_url'), '/');
        $this->clientId = config('koha.client_id');
        $this->clientSecret = config('koha.client_secret');
    }

    /**
     * Get OAuth2 access token (cached for its lifetime)
     */
    protected function getAccessToken(): ?string
    {
        return Cache::remember('koha_oauth_token', 3500, function () {
            // Koha OAuth2 token endpoint — base URL without /api/v1
            $tokenUrl = preg_replace('#/api/v1$#', '', $this->baseUrl) . '/api/v1/oauth/token';

            Log::info('Koha: Requesting OAuth2 token', ['url' => $tokenUrl]);

            $response = Http::withOptions(['verify' => false])
                ->asForm()
                ->post($tokenUrl, [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Koha: OAuth2 token obtained', ['expires_in' => $data['expires_in'] ?? 'unknown']);
                return $data['access_token'] ?? null;
            }

            Log::error('Koha: OAuth2 token request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        });
    }

    /**
     * Create an authenticated HTTP client
     */
    public function client()
    {
        $http = Http::baseUrl($this->baseUrl)
            ->withOptions(['verify' => false, 'timeout' => 15])
            ->acceptJson();

        // Try OAuth2 first
        $token = $this->getAccessToken();
        if ($token) {
            return $http->withToken($token);
        }

        // Fallback to Basic Auth if configured
        $user = config('koha.api_user');
        $pass = config('koha.api_password');
        if ($user && $pass) {
            Log::info('Koha: Using Basic Auth fallback');
            return $http->withBasicAuth($user, $pass);
        }

        Log::warning('Koha: No authentication configured');
        return $http;
    }

    /**
     * Get patron by CAS username (userid in Koha)
     */
    public function getPatronByUsername(string $username): ?array
    {
        $cacheKey = "koha_patron_{$username}";
        
        // Cache patron data for 60 minutes
        return Cache::remember($cacheKey, 3600, function () use ($username) {
            try {
                // First try searching by userid
                $response = $this->client()->get('/patrons', [
                    'userid' => $username,
                ]);

                if ($response->successful()) {
                    $patrons = $response->json();
                    if (!empty($patrons)) {
                        return $patrons[0];
                    }
                }

                // Fallback: Try searching by cardnumber
                $response2 = $this->client()->get('/patrons', [
                    'cardnumber' => $username,
                ]);

                if ($response2->successful()) {
                    $patrons = $response2->json();
                    if (!empty($patrons)) {
                        return $patrons[0];
                    }
                }

                Log::warning('Koha: getPatronByUsername failed (not found by userid or cardnumber)', [
                    'username' => $username,
                ]);
            } catch (\Exception $e) {
                Log::error('Koha: getPatronByUsername exception', ['error' => $e->getMessage()]);
            }

            return null;
        });
    }

    /**
     * Search bibliographic records
     */
    public function searchBiblios(string $query, int $page = 1, int $perPage = 20): array
    {
        $cacheKey = "koha_search_" . md5("{$query}_{$page}_{$perPage}");
        
        // Cache search results for 15 minutes
        return Cache::remember($cacheKey, 900, function () use ($query, $page, $perPage) {
            try {
                // Split query into words for order-independent search
                // e.g. "tere liye" should match "LIYE, Tere" (author format in Koha)
                $words = array_filter(explode(' ', trim($query)));

                if (count($words) > 1) {
                    // Build per-word conditions for each field
                    $titleConditions = [];
                    $authorConditions = [];
                    foreach ($words as $word) {
                        $titleConditions[]  = ['title'  => ['like' => "%{$word}%"]];
                        $authorConditions[] = ['author' => ['like' => "%{$word}%"]];
                    }

                    // Match ALL words in title OR ALL words in author
                    $searchFilter = json_encode([
                        '-or' => [
                            ['-and' => $titleConditions],
                            ['-and' => $authorConditions],
                        ]
                    ]);
                } else {
                    // Single word: simple search in title or author
                    $searchFilter = json_encode([
                        '-or' => [
                            ['title'  => ['like' => "%{$query}%"]],
                            ['author' => ['like' => "%{$query}%"]],
                        ]
                    ]);
                }

                $response = $this->client()->get('/biblios', [
                    'q'         => $searchFilter,
                    '_page'     => $page,
                    '_per_page' => $perPage,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Koha: searchBiblios failed', [
                    'query'  => $query,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            } catch (\Exception $e) {
                Log::error('Koha: searchBiblios exception', ['error' => $e->getMessage()]);
            }

            return [];
        });
    }

    /**
     * Get latest bibliographic records sorted by acquisition date (newest first).
     * Filters out records that do not have any physical items.
     */
    public function getLatestBiblios(int $targetCount = 20): array
    {
        $cacheKey = "koha_latest_with_items_{$targetCount}";

        // Cache for 10 minutes to balance performance and freshness
        return Cache::remember($cacheKey, 600, function () use ($targetCount) {
            try {
                $validBiblios = [];
                $page = 1;
                $perPage = 50; // Fetch an initial batch larger than target to account for itemless biblios

                // Keep fetching pages until we have the desired number of valid books
                while (count($validBiblios) < $targetCount) {
                    $response = $this->client()->get('/biblios', [
                        '_order_by' => '-biblio_id',
                        '_per_page' => $perPage,
                        '_page'     => $page,
                    ]);

                    if (!$response->successful()) {
                        Log::warning('Koha: getLatestBiblios failed in batch', [
                            'status' => $response->status(),
                            'body'   => $response->body(),
                        ]);
                        break; // Stop trying if API fails
                    }

                    $batch = $response->json();
                    
                    if (empty($batch)) {
                        break; // No more books to fetch at all
                    }

                    // For each biblio in the batch, check if it has items
                    foreach ($batch as $biblio) {
                        try {
                            $itemsResponse = $this->client()->get("/biblios/{$biblio['biblio_id']}/items");
                            
                            if ($itemsResponse->successful()) {
                                $items = $itemsResponse->json();
                                if (is_array($items) && count($items) > 0) {
                                    $hasAvailableItem = false;
                                    foreach ($items as $i) {
                                        $withdrawn = (int) ($i['withdrawn'] ?? 0);
                                        $lost = (int) ($i['lost_status'] ?? 0);
                                        $nfl = ((int) ($i['not_for_loan_status'] ?? 0) > 0) || ((int) ($i['effective_not_for_loan_status'] ?? 0) > 0) || ((int) ($i['notforloan'] ?? 0) > 0);
                                        $homeLib = $i['home_library_id'] ?? '';
                                        
                                        if ($withdrawn === 0 && $lost === 0 && !$nfl && $homeLib === 'PUSAT') {
                                            $hasAvailableItem = true;
                                            break;
                                        }
                                    }

                                    if ($hasAvailableItem) {
                                        $validBiblios[] = $biblio;
                                        
                                        // Stop if we reached the requested count mid-batch
                                        if (count($validBiblios) >= $targetCount) {
                                            break;
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning("Koha: failed checking items for biblio {$biblio['biblio_id']}", ['err' => $e->getMessage()]);
                            continue;
                        }
                    }
                    
                    $page++;
                    
                    // Failsafe: Don't check more than 5 pages (max 250 records) to avoid API rate limits/timeouts
                    if ($page > 5) {
                        break;
                    }
                }

                return $validBiblios;
            } catch (\Exception $e) {
                Log::error('Koha: getLatestBiblios exception', ['error' => $e->getMessage()]);
            }

            return [];
        });
    }

    /**
     * Get items for a specific biblio
     */
    public function getBiblioItems(int $biblioId): array
    {
        try {
            $response = $this->client()->get("/biblios/{$biblioId}/items");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Koha: getBiblioItems exception', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get a single biblio record
     */
    public function getBiblio(int $biblioId): ?array
    {
        $cacheKey = "koha_biblio_{$biblioId}";
        
        // Cache individual book data for 60 minutes
        return Cache::remember($cacheKey, 3600, function () use ($biblioId) {
            try {
                $response = $this->client()->get("/biblios/{$biblioId}");

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                Log::error('Koha: getBiblio exception', ['error' => $e->getMessage()]);
            }

            return null;
        });
    }

    /**
     * Place a hold (reserve a book)
     */
    public function placeHold(int $patronId, int $biblioId, string $pickupLibraryId): ?array
    {
        try {
            $response = $this->client()->post('/holds', [
                'patron_id'         => $patronId,
                'biblio_id'         => $biblioId,
                'pickup_library_id' => $pickupLibraryId,
                'notes'             => '[Trolly] User melakukan Checkout Pesanan dari Aplikasi',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Koha: placeHold failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Koha: placeHold exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Cancel a hold
     */
    public function cancelHold(int $holdId): bool
    {
        try {
            $response = $this->client()->delete("/holds/{$holdId}");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Koha: cancelHold exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Create a checkout (official borrow).
     */
    public function createCheckout(int $patronId, int $itemId): ?array
    {
        try {
            $payload = [
                'patron_id' => $patronId,
                'item_id'   => $itemId,
                'note'      => '[Trolly] Dipinjam via aplikasi Trolly Koha',
            ];

            $response = $this->client()->post('/checkouts', $payload);

            if ($response->successful()) {
                Log::info('Koha: createCheckout success', ['patron_id' => $patronId, 'item_id' => $itemId]);
                return $response->json();
            }

            // 412 = Confirmation needed (e.g. RESERVED, ISSUED_TO_ANOTHER, etc.)
            if ($response->status() === 412) {
                Log::info('Koha: createCheckout got 412, fetching confirmation token', [
                    'patron_id' => $patronId, 'item_id' => $itemId, 'body' => $response->body()
                ]);

                $availResponse = $this->client()->get('/checkouts/availability', [
                    'patron_id' => $patronId,
                    'item_id'   => $itemId,
                ]);

                if (!$availResponse->successful()) {
                    Log::warning('Koha: checkouts/availability failed', [
                        'status' => $availResponse->status(), 'body' => $availResponse->body()
                    ]);
                    return null;
                }

                $availData = $availResponse->json();

                if (!empty($availData['blockers'])) {
                    Log::warning('Koha: createCheckout blocked', [
                        'patron_id' => $patronId, 'item_id' => $itemId,
                        'blockers' => $availData['blockers']
                    ]);
                    return null;
                }

                $token = $availData['confirmation_token'] ?? null;

                if (!$token) {
                    Log::warning('Koha: No confirmation_token in availability response', [
                        'patron_id' => $patronId, 'item_id' => $itemId
                    ]);
                    return null;
                }

                $retryResponse = $this->client()
                    ->post('/checkouts?confirmation=' . urlencode($token), $payload);

                if ($retryResponse->successful()) {
                    Log::info('Koha: createCheckout success (with confirmation)', [
                        'patron_id' => $patronId, 'item_id' => $itemId,
                        'confirms' => array_keys($availData['confirms'] ?? [])
                    ]);
                    return $retryResponse->json();
                }

                Log::warning('Koha: createCheckout failed even with confirmation', [
                    'status' => $retryResponse->status(), 'body' => $retryResponse->body()
                ]);
                return null;
            }

            Log::warning('Koha: createCheckout failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Koha: createCheckout exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if the service is configured and reachable
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Update patron data in Koha
     */
    public function updatePatron(int $patronId, array $data): bool
    {
        try {
            $response = $this->client()->put("/patrons/{$patronId}", $data);

            if ($response->successful()) {
                Log::info("Koha: Successfully updated patron {$patronId}");
                
                // Clear the patron cache so next fetch gets updated data immediately
                if (auth()->check()) {
                    Cache::forget("koha_patron_" . auth()->user()->cas_username);
                }
                
                return true;
            }

            Log::warning("Koha: Failed to update patron {$patronId}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('Koha: updatePatron exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    /**
     * Create a manual invoice / fine for a patron (e.g., for shipping cost)
     */
    public function createInvoice(int $patronId, float $amount, string $description): ?array
    {
        if ($amount <= 0) {
            return null;
        }

        try {
            $response = $this->client()->post("/patrons/{$patronId}/account/debits", [
                'amount' => (string) $amount,
                'description' => $description,
                'type' => 'MANUAL',
            ]);

            if ($response->successful()) {
                Log::info("Koha: Successfully created invoice for patron {$patronId}", ['amount' => $amount]);
                return $response->json();
            }

            Log::error('Koha: createInvoice failed', [
                'patron_id' => $patronId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('Koha: createInvoice exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Determine role from Koha categorycode.
     */
    public function resolveRole(string $categorycode): string
    {
        $librarianCodes = array_map(
            'strtoupper',
            explode(',', config('koha.librarian_categorycodes', 'LIBRARIAN'))
        );

        return in_array(strtoupper($categorycode), $librarianCodes) ? 'librarian' : 'patron';
    }
}
