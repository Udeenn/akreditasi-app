<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Koha REST API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk mengakses Koha ILS REST API.
    | Base URL harus diakhiri dengan /api/v1
    |
    */

    // Base URL Koha REST API (contoh: http://koha.ums.ac.id:8080/api/v1)
    'base_url' => env('KOHA_API_URL', 'http://172.16.10.43/api/v1'),

    // OAuth2 Client Credentials (diutamakan)
    'client_id'     => env('KOHA_CLIENT_ID', ''),
    'client_secret' => env('KOHA_CLIENT_SECRET', ''),

    // Basic Auth (fallback jika OAuth2 tidak dikonfigurasi)
    'api_user'     => env('KOHA_API_USERNAME', ''),
    'api_password' => env('KOHA_API_PASSWORD', ''),

    // Kode kategori yang dianggap librarian (pisahkan dengan koma jika lebih dari satu)
    'librarian_categorycodes' => env('KOHA_LIBRARIAN_CATEGORIES', 'LIBRARIAN'),
];
