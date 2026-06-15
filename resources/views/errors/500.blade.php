<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Terjadi Kesalahan Server | Library Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('css/500.css') }}">
</head>
<body>

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

    <div class="error-card">

        <div class="error-code">500</div>

        <div class="cog-wrap">
            <div class="cog-outer"></div>
            <div class="cog-inner"></div>
            <div class="cog-icon">
                <i class="fas fa-gears" style="color: #a78bfa;"></i>
            </div>
        </div>

        <div class="divider"></div>

        <h1 class="error-title">Terjadi Kesalahan Server</h1>
        <p class="error-desc">
            Server sedang mengalami gangguan sementara. Tim kami sedang memperbaikinya.
            Silakan coba lagi dalam beberapa saat.
        </p>

        {{-- Terminal-style log box --}}
        <div class="terminal">
            <div class="terminal-header">
                <div class="terminal-dot" style="background:#f87171;"></div>
                <div class="terminal-dot" style="background:#facc15;"></div>
                <div class="terminal-dot" style="background:#34d399;"></div>
                <span style="color:rgba(255,255,255,0.3); font-size:0.75rem; margin-left:0.5rem;">system.log</span>
            </div>
            <div class="terminal-line"><span class="cmd">$ </span>status --check application</div>
            <div class="terminal-line"><span class="err">[ERROR]</span> Internal server exception caught</div>
            <div class="terminal-line"><span class="cmd">$ </span>auto-recovery --mode=safe</div>
            <div class="terminal-line"><span class="ok">[INFO] </span>Alert dispatched to tech team...</div>
            <div class="terminal-line" style="color:rgba(255,255,255,0.25);">Timestamp: {{ now()->format('Y-m-d H:i:s') }}</div>
        </div>

        <div class="d-flex flex-wrap gap-3 justify-content-center">
            <button onclick="window.location.reload()" class="btn-back">
                <i class="fas fa-rotate-right"></i> Coba Lagi
            </button>
            <a href="{{ url('/') }}" class="btn-ghost">
                <i class="fas fa-house"></i> Beranda
            </a>
        </div>

        <div class="brand-footer">
            &copy; {{ date('Y') }} <span>UPT Perpustakaan dan Layanan Digital UMS</span> · Library Data Akreditasi
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
