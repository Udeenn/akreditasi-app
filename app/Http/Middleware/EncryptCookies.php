<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Tambahkan nama cookie di sini jika ada yang tidak perlu dienkripsi
        // Contoh: 'cookie_tanpa_enkripsi',
    ];
}
