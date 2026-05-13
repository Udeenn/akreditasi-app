<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak | Library Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/x-icon">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-primary: #0a0a1a;
            --accent:     #ef4444;
            --accent-2:   #f97316;
            --accent-3:   #facc15;
            --text:       #ffffff;
            --text-muted: rgba(255,255,255,0.5);
            --glow:       rgba(239,68,68,0.35);
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
        .bg-orbs { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
        .orb {
            position: absolute; border-radius: 50%;
            filter: blur(80px); opacity: 0.22;
            animation: drift 18s ease-in-out infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: var(--accent);   top: -150px; left: -100px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: var(--accent-2); bottom: -100px; right: -80px; animation-delay: -6s; }
        .orb-3 { width: 280px; height: 280px; background: var(--accent-3); top: 50%; left: 55%; animation-delay: -12s; }

        @keyframes drift {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(40px, 30px) scale(1.05); }
            66%      { transform: translate(-30px, 20px) scale(0.95); }
        }

        .grid-overlay {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(239,68,68,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(239,68,68,0.05) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* ── Shield with lock animation ── */
        .shield-wrap {
            position: relative;
            width: 120px; height: 120px;
            margin: 0 auto 1.5rem;
        }
        .shield-ring {
            position: absolute; inset: 0;
            border-radius: 50%;
            border: 2px solid rgba(239,68,68,0.3);
            animation: ringPulse 2.5s ease-out infinite;
        }
        .shield-ring:nth-child(2) { animation-delay: 0.8s; }
        .shield-ring:nth-child(3) { animation-delay: 1.6s; }
        @keyframes ringPulse {
            0%   { transform: scale(0.8); opacity: 0.8; }
            100% { transform: scale(1.8); opacity: 0; }
        }
        .shield-icon {
            position: relative; z-index: 5;
            width: 120px; height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(239,68,68,0.2), rgba(249,115,22,0.2));
            border: 1px solid rgba(239,68,68,0.35);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.8rem;
            backdrop-filter: blur(12px);
            animation: float 4s ease-in-out infinite;
        }
        @keyframes float {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-10px); }
        }

        /* ── Error code ── */
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
            margin-bottom: 1.5rem;
        }
        @keyframes pulseNum {
            0%,100% { filter: drop-shadow(0 0 30px var(--glow)); }
            50%      { filter: drop-shadow(0 0 60px rgba(239,68,68,0.5)); }
        }

        .error-card {
            position: relative; z-index: 10;
            text-align: center;
            padding: 3rem 2rem;
            max-width: 560px; width: 100%;
        }

        .divider {
            width: 60px; height: 3px;
            background: linear-gradient(90deg, var(--accent), transparent);
            border-radius: 2px;
            margin: 0 auto 1.5rem;
        }

        .error-title { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.75rem; }
        .error-desc {
            color: var(--text-muted); font-size: 1rem; line-height: 1.7;
            margin-bottom: 2.5rem; max-width: 420px; margin-left: auto; margin-right: auto;
        }

        /* ── Permission badge ── */
        .perm-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 50px;
            padding: 0.4rem 1rem;
            font-size: 0.82rem;
            color: #fca5a5;
            margin-bottom: 2rem;
        }

        /* ── Buttons ── */
        .btn-back {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.85rem 2rem; border-radius: 50px;
            font-weight: 600; font-size: 0.95rem; text-decoration: none;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #fff; border: none;
            box-shadow: 0 8px 32px rgba(239,68,68,0.4);
            transition: all 0.3s ease; position: relative; overflow: hidden;
        }
        .btn-back::before {
            content: '';
            position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .btn-back:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(239,68,68,0.55); color: #fff; }
        .btn-back:hover::before { left: 100%; }

        .btn-ghost {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.85rem 1.75rem; border-radius: 50px;
            font-weight: 600; font-size: 0.95rem; text-decoration: none;
            background: transparent; color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.15);
            transition: all 0.3s ease;
        }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); background: rgba(239,68,68,0.08); }

        /* ── Particles ── */
        .particle {
            position: fixed; width: 4px; height: 4px;
            background: var(--accent); border-radius: 50%; opacity: 0;
            animation: rise 12s infinite; pointer-events: none; z-index: 1;
        }
        @keyframes rise {
            0%   { opacity: 0; transform: translateY(100vh) scale(0); }
            10%  { opacity: 0.5; }
            90%  { opacity: 0.5; }
            100% { opacity: 0; transform: translateY(-20vh) scale(1); }
        }
        .particle:nth-child(1) { left: 10%; animation-delay: 0s;    animation-duration: 14s; }
        .particle:nth-child(2) { left: 30%; animation-delay: -4s;   animation-duration: 11s; background: var(--accent-2); }
        .particle:nth-child(3) { left: 55%; animation-delay: -7s;   animation-duration: 13s; }
        .particle:nth-child(4) { left: 75%; animation-delay: -2s;   animation-duration: 10s; background: var(--accent-3); }
        .particle:nth-child(5) { left: 88%; animation-delay: -9s;   animation-duration: 15s; }

        .brand-footer {
            margin-top: 3rem; font-size: 0.8rem;
            color: rgba(255,255,255,0.2); letter-spacing: 0.5px;
        }
        .brand-footer span { color: var(--accent); }
    </style>
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

        <div class="error-code">403</div>

        {{-- Shield with pulse rings --}}
        <div class="shield-wrap">
            <div class="shield-ring"></div>
            <div class="shield-ring"></div>
            <div class="shield-ring"></div>
            <div class="shield-icon">
                <i class="fas fa-shield-halved" style="color: #f87171;"></i>
            </div>
        </div>

        <div class="divider"></div>

        <div class="perm-badge">
            <i class="fas fa-lock fa-xs"></i>
            Akses Terbatas — Hanya Pustakawan
        </div>

        <h1 class="error-title">Akses Ditolak</h1>
        <p class="error-desc">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman ini. Hubungi pustakawan jika Anda yakin ini adalah kesalahan.' }}
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
