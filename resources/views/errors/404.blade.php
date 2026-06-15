<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan | Library Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('css/404.css') }}">
</head>
<body>

    <!-- Background -->
    <div class="bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="grid-overlay"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>

    <!-- Content -->
    <div class="error-card">
        <div class="error-code">404</div>

        <div class="error-icon-wrap">
            <i class="fas fa-map-signs" style="color: var(--accent);"></i>
        </div>

        <div class="divider"></div>

        <h1 class="error-title">Halaman Tidak Ditemukan</h1>
        <p class="error-desc">
            Sepertinya halaman yang Anda cari sudah dipindahkan, dihapus, atau memang tidak pernah ada.
            Coba kembali ke beranda.
        </p>

        <div class="d-flex flex-wrap gap-3 justify-content-center">
            <a href="{{ url('/') }}" class="btn-back">
                <i class="fas fa-house"></i> Kembali ke Beranda
            </a>
            @auth
            <a href="{{ route('dashboard') }}" class="btn-ghost">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            @endauth
        </div>

        <div class="brand-footer">
            &copy; {{ date('Y') }} <span>UPT Perpustakaan dan Layanan Digital UMS</span> · Library Data Akreditasi
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
