<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Akreditasi API Documentation",
    description: "Dokumentasi Resmi REST API Aplikasi Akreditasi Perpustakaan UMS",
    contact: new OA\Contact(email: "perpustakaan@ums.ac.id")
)]
#[OA\Server(
    url: "/",
    description: "Primary Application API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    name: "X-API-KEY",
    in: "header",
    // description: "Gunakan API Key: akreditasi_secret_api_key_123"
)]
abstract class Controller
{
    //
}
