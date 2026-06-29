<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Akreditasi API Documentation",
    description: "L5 Swagger OpenApi description for Akreditasi App",
    contact: new OA\Contact(email: "admin@example.com")
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: "Demo API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    name: "X-API-KEY",
    in: "header",
    description: "Masukkan API Key Anda di sini"
)]
abstract class Controller
{
    //
}
