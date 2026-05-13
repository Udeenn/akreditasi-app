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
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-primary: #0a0a1a;
            --accent:     #4A69FF;
            --accent-2:   #5B7AFF;
            --accent-3:   #6D8BFF;
            --text:       #ffffff;
            --text-muted: rgba(255,255,255,0.5);
            --glow:       rgba(74,105,255,0.35);
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: var(--bg-primary);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* ── Animated Background ── */
        .bg-orbs {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.25;
            animation: drift 18s ease-in-out infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: var(--accent);   top: -150px; left: -100px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: var(--accent-2); bottom: -100px; right: -80px; animation-delay: -6s; }
        .orb-3 { width: 300px; height: 300px; background: var(--accent-3); top: 40%; left: 50%; animation-delay: -12s; }

        @keyframes drift {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(40px, 30px) scale(1.05); }
            66%      { transform: translate(-30px, 20px) scale(0.95); }
        }

        /* ── Grid lines ── */
        .grid-overlay {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(74,105,255,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(74,105,255,0.06) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* ── Main card ── */
        .error-card {
            position: relative; z-index: 10;
            text-align: center;
            padding: 3rem 2rem;
            max-width: 560px;
            width: 100%;
        }

        /* ── Big number ── */
        .error-code {
            font-size: clamp(7rem, 18vw, 12rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -6px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2), var(--accent-3));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 40px var(--glow));
            animation: pulseNum 3s ease-in-out infinite;
            margin-bottom: 0.5rem;
        }

        @keyframes pulseNum {
            0%,100% { filter: drop-shadow(0 0 30px var(--glow)); }
            50%      { filter: drop-shadow(0 0 60px rgba(74,105,255,0.6)); }
        }

        /* ── Icon inside number area ── */
        .error-icon-wrap {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(74,105,255,0.2), rgba(109,139,255,0.2));
            border: 1px solid rgba(74,105,255,0.3);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            animation: float 4s ease-in-out infinite;
            backdrop-filter: blur(10px);
        }
        @keyframes float {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-12px); }
        }

        .error-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .error-desc {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 2.5rem;
            max-width: 420px;
            margin-left: auto; margin-right: auto;
        }

        /* ── Buttons ── */
        .btn-back {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.85rem 2rem;
            border-radius: 50px;
            font-weight: 600; font-size: 0.95rem;
            text-decoration: none;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #fff;
            border: none;
            box-shadow: 0 8px 32px rgba(74,105,255,0.4);
            transition: all 0.3s ease;
            position: relative; overflow: hidden;
        }
        .btn-back::before {
            content: '';
            position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .btn-back:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(74,105,255,0.55); color: #fff; }
        .btn-back:hover::before { left: 100%; }

        .btn-ghost {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.85rem 1.75rem;
            border-radius: 50px;
            font-weight: 600; font-size: 0.95rem;
            text-decoration: none;
            background: transparent;
            color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.15);
            transition: all 0.3s ease;
            backdrop-filter: blur(6px);
        }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); background: rgba(74,105,255,0.08); }

        /* ── Divider ── */
        .divider {
            width: 60px; height: 3px;
            background: linear-gradient(90deg, var(--accent), transparent);
            border-radius: 2px;
            margin: 0 auto 1.5rem;
        }

        /* ── Footer brand ── */
        .brand-footer {
            margin-top: 3rem;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.25);
            letter-spacing: 0.5px;
        }
        .brand-footer span { color: var(--accent); }

        /* ── Floating particles ── */
        .particle {
            position: fixed; width: 4px; height: 4px;
            background: var(--accent); border-radius: 50%; opacity: 0;
            animation: rise 12s infinite;
            pointer-events: none; z-index: 1;
        }
        @keyframes rise {
            0%   { opacity: 0; transform: translateY(100vh) scale(0); }
            10%  { opacity: 0.5; }
            90%  { opacity: 0.5; }
            100% { opacity: 0; transform: translateY(-20vh) scale(1); }
        }
        .particle:nth-child(1) { left: 10%; animation-delay: 0s;    animation-duration: 14s; }
        .particle:nth-child(2) { left: 25%; animation-delay: -3s;   animation-duration: 11s; background: var(--accent-2); }
        .particle:nth-child(3) { left: 45%; animation-delay: -6s;   animation-duration: 13s; }
        .particle:nth-child(4) { left: 65%; animation-delay: -9s;   animation-duration: 10s; background: var(--accent-3); }
        .particle:nth-child(5) { left: 80%; animation-delay: -2s;   animation-duration: 15s; background: var(--accent-2); }
        .particle:nth-child(6) { left: 55%; animation-delay: -7s;   animation-duration: 12s; }
    </style>
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
