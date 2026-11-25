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
    in: "header"
)]
abstract class Controller
{
    /**
     * Izinkan hanya data URI gambar raster base64 (mencegah SSRF/file read via Dompdf).
     */
    protected function sanitizeChartImage(?string $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }
        $raw = trim($raw);
        return preg_match('/^data:image\/(png|jpe?g|webp);base64,[A-Za-z0-9+\/=]+$/', $raw) ? $raw : null;
    }
}
