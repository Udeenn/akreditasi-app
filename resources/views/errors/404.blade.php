<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan | Library Data</title>
    <link rel="icon" href="{{ asset('img/logo4.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous">

    <link href="{{ asset('vendor/bootstrap/5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/unified-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">
</head>
<body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark' ? 'dark-mode' : '' }}" style="background-color: var(--main-bg); min-height: 100vh;">

    <div class="min-vh-100 d-flex align-items-center justify-content-center p-3 p-md-4">
        <div class="card border shadow-sm text-center p-4 p-md-5" style="max-width: 520px; width: 100%; border-radius: 1.25rem; background-color: var(--sidebar-bg); border-color: var(--border-color) !important;">
            
            {{-- Brand Header --}}
            <div class="mb-4">
                <a href="{{ route('dashboard') }}" class="d-inline-flex align-items-center text-decoration-none gap-2">
                    <img src="{{ asset('img/logo4.png') }}" alt="Logo UMS" style="height: 38px;">
                    <div class="text-start">
                        <div class="fw-bold tracking-tight" style="color: var(--text-dark); font-size: 1.15rem; line-height: 1.2;">Library Data</div>
                        <small class="text-muted" style="font-size: var(--font-xs);">Sistem Statistik Perpustakaan UMS</small>
                    </div>
                </a>
            </div>

            {{-- Icon Container --}}
            <div class="d-flex justify-content-center mb-3">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                    <x-icon name="search" size="40" />
                </div>
            </div>

            {{-- Error Badge --}}
            <div>
                <span class="badge rounded-pill mb-2 px-3 py-1 text-uppercase" style="background: rgba(245, 158, 11, 0.15); color: #d97706; font-size: var(--font-xs); font-weight: 700; letter-spacing: 0.05em;">
                    Kode 404 · Not Found
                </span>
            </div>

            {{-- Title & Description --}}
            <h1 class="h3 fw-bold mb-2 mt-2" style="color: var(--text-dark);">Halaman Tidak Ditemukan</h1>
            <p class="text-muted mb-4" style="font-size: var(--font-base); line-height: 1.6;">
                Halaman atau tautan yang Anda cari mungkin telah dipindahkan, diubah namanya, atau sedang tidak tersedia.
            </p>

            {{-- Actions --}}
            <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">
                <a href="{{ route('dashboard') }}" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2" style="border-radius: 0.65rem; font-weight: 600;">
                    <x-icon name="house" />
                    <span>Kembali ke Dashboard</span>
                </a>
                <button type="button" onclick="window.history.back()" class="btn btn-outline-secondary px-4 py-2 d-inline-flex align-items-center gap-2" style="border-radius: 0.65rem; font-weight: 600;">
                    <x-icon name="arrow-left" />
                    <span>Halaman Sebelumnya</span>
                </button>
            </div>

            {{-- Footer Note --}}
            <div class="mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                <small class="text-muted" style="font-size: var(--font-xs);">
                    &copy; {{ date('Y') }} UPT Perpustakaan dan Layanan Digital UMS
                </small>
            </div>

        </div>
    </div>

</body>
</html>
