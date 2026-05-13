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
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-primary: #0a0a1a;
            --accent:     #7c3aed;
            --accent-2:   #a855f7;
            --accent-3:   #ec4899;
            --text:       #ffffff;
            --text-muted: rgba(255,255,255,0.5);
            --glow:       rgba(124,58,237,0.4);
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

        /* ── Background ── */
        .bg-orbs { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
        .orb {
            position: absolute; border-radius: 50%;
            filter: blur(90px); opacity: 0.22;
            animation: drift 20s ease-in-out infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: var(--accent);   top: -150px; right: -100px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: var(--accent-2); bottom: -100px; left: -80px; animation-delay: -8s; }
        .orb-3 { width: 300px; height: 300px; background: var(--accent-3); top: 40%; right: 30%; animation-delay: -14s; }

        @keyframes drift {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(-40px, 30px) scale(1.05); }
            66%      { transform: translate(30px, -20px) scale(0.95); }
        }

        .grid-overlay {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(124,58,237,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(124,58,237,0.06) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* ── Glitch effect on error code ── */
        .error-code {
            font-size: clamp(7rem, 18vw, 12rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -6px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2), var(--accent-3));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            animation: glitch 4s ease-in-out infinite;
            margin-bottom: 1.5rem;
        }
        @keyframes glitch {
            0%, 90%, 100% { filter: drop-shadow(0 0 30px var(--glow)); transform: none; }
            91%  { transform: translate(-3px, 0) skew(-1deg); filter: drop-shadow(-4px 0 #ec4899) drop-shadow(4px 0 var(--accent)); }
            92%  { transform: translate(3px, 0) skew(1deg);  filter: drop-shadow(4px 0 #ec4899) drop-shadow(-4px 0 var(--accent)); }
            93%  { transform: none; filter: drop-shadow(0 0 30px var(--glow)); }
            95%  { transform: translate(-2px, 1px); }
            96%  { transform: translate(2px, -1px); }
            97%  { transform: none; }
        }

        /* ── Spinning cog ── */
        .cog-wrap {
            position: relative;
            width: 110px; height: 110px;
            margin: 0 auto 1.5rem;
            display: flex; align-items: center; justify-content: center;
        }
        .cog-outer {
            position: absolute; inset: 0;
            border-radius: 50%;
            border: 2px dashed rgba(124,58,237,0.35);
            animation: spin 14s linear infinite;
        }
        .cog-inner {
            position: absolute;
            inset: 16px;
            border-radius: 50%;
            border: 2px solid rgba(168,85,247,0.3);
            animation: spin 8s linear infinite reverse;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .cog-icon {
            position: relative; z-index: 5;
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(124,58,237,0.2), rgba(236,72,153,0.15));
            border: 1px solid rgba(124,58,237,0.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            backdrop-filter: blur(12px);
            animation: float 4s ease-in-out infinite;
        }
        @keyframes float {
            0%,100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-8px) rotate(10deg); }
        }

        /* ── Terminal-style error box ── */
        .terminal {
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin: 0 auto 2.5rem;
            max-width: 420px;
            font-family: 'Courier New', monospace;
            font-size: 0.82rem;
            text-align: left;
            backdrop-filter: blur(10px);
        }
        .terminal-header {
            display: flex; align-items: center; gap: 0.4rem;
            margin-bottom: 0.75rem; padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .terminal-dot {
            width: 10px; height: 10px; border-radius: 50%;
        }
        .terminal-line {
            color: rgba(255,255,255,0.4);
            margin: 0.2rem 0;
        }
        .terminal-line .cmd { color: #a855f7; }
        .terminal-line .err { color: #f87171; }
        .terminal-line .ok  { color: #34d399; }

        /* ── Cards ── */
        .error-card {
            position: relative; z-index: 10;
            text-align: center;
            padding: 3rem 2rem;
            max-width: 580px; width: 100%;
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
            margin-bottom: 1.5rem; max-width: 440px; margin-left: auto; margin-right: auto;
        }

        /* ── Buttons ── */
        .btn-back {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.85rem 2rem; border-radius: 50px;
            font-weight: 600; font-size: 0.95rem; text-decoration: none;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #fff; border: none;
            box-shadow: 0 8px 32px rgba(124,58,237,0.45);
            transition: all 0.3s ease; position: relative; overflow: hidden;
        }
        .btn-back::before {
            content: '';
            position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .btn-back:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(124,58,237,0.6); color: #fff; }
        .btn-back:hover::before { left: 100%; }

        .btn-ghost {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.85rem 1.75rem; border-radius: 50px;
            font-weight: 600; font-size: 0.95rem; text-decoration: none;
            background: transparent; color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.15);
            transition: all 0.3s ease;
        }
        .btn-ghost:hover { border-color: var(--accent-2); color: var(--accent-2); background: rgba(168,85,247,0.08); }

        /* ── Particles ── */
        .particle {
            position: fixed; width: 4px; height: 4px;
            border-radius: 50%; opacity: 0;
            animation: rise 12s infinite; pointer-events: none; z-index: 1;
        }
        @keyframes rise {
            0%   { opacity: 0; transform: translateY(100vh) scale(0); }
            10%  { opacity: 0.5; }
            90%  { opacity: 0.5; }
            100% { opacity: 0; transform: translateY(-20vh) scale(1); }
        }
        .particle:nth-child(1) { left: 8%;  animation-delay: 0s;    animation-duration: 14s; background: var(--accent); }
        .particle:nth-child(2) { left: 28%; animation-delay: -3s;   animation-duration: 11s; background: var(--accent-2); }
        .particle:nth-child(3) { left: 50%; animation-delay: -6s;   animation-duration: 13s; background: var(--accent-3); }
        .particle:nth-child(4) { left: 72%; animation-delay: -9s;   animation-duration: 10s; background: var(--accent); }
        .particle:nth-child(5) { left: 90%; animation-delay: -2s;   animation-duration: 15s; background: var(--accent-2); }

        .brand-footer {
            margin-top: 3rem; font-size: 0.8rem;
            color: rgba(255,255,255,0.2); letter-spacing: 0.5px;
        }
        .brand-footer span { color: var(--accent-2); }
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
