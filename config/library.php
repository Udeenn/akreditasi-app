<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Library Configuration Settings
    |--------------------------------------------------------------------------
    |
    | Menyimpan berbagai aturan dan konstanta manual terkait perpustakaan.
    |
    */

    'ddc' => [
        // Kode DDC (Dewey Decimal Classification) untuk Koleksi Fiksi
        'fiksi' => ['812', '813', '823', '899']
    ],

    /*
    |--------------------------------------------------------------------------
    | Koha REST API
    |--------------------------------------------------------------------------
    */
    'koha_api_url'      => env('KOHA_API_URL', 'http://172.16.10.43/api/v1'),
    'koha_api_username' => env('KOHA_API_USERNAME', ''),
    'koha_api_password' => env('KOHA_API_PASSWORD', ''),
    'disable_ssl_validation' => env('KOHA_DISABLE_SSL', false),

    // Kode kategori Koha yang dianggap sebagai librarian (pisahkan dengan koma)
    'librarian_categorycodes' => env('KOHA_LIBRARIAN_CATEGORIES', 'LIBRARIAN'),
];
