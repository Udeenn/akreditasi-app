<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CAS Server Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk server CAS UMS
    |
    */

    'host' => env('CAS_HOST', 'auth.ums.ac.id'),
    'port' => env('CAS_PORT', 443),
    'context' => env('CAS_CONTEXT', '/cas'),
    'version' => env('CAS_VERSION', '2.0'),
    
    /*
    |--------------------------------------------------------------------------
    | Service Configuration
    |--------------------------------------------------------------------------
    |
    | URL aplikasi yang akan digunakan sebagai service identifier
    |
    */
    
    'service_base_url' => env('CAS_SERVICE_URL', env('APP_URL')),
    'logout_url' => env('CAS_LOGOUT_URL'),
    
    /*
    |--------------------------------------------------------------------------
    | SSL Validation
    |--------------------------------------------------------------------------
    |
    | Untuk development, bisa dinonaktifkan. Di production, sebaiknya aktifkan
    | dan set path ke CA certificate.
    |
    */
    
    'disable_ssl_validation' => env('CAS_DISABLE_SSL_VALIDATION', false),
    'ca_cert_path' => env('CAS_CA_CERT_PATH'),
];

