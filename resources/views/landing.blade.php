<!DOCTYPE html>
<html lang="id" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Pendukung Akreditasi Prodi - UMS Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0a0a1a;
            --bg-secondary: #0f0f2a;
            --bg-card: rgba(255, 255, 255, 0.03);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.4);
            --accent-1: #4A69FF;
            --accent-2: #5B7AFF;
            --accent-3: #6D8BFF;
            --glow-color: rgba(74, 105, 255, 0.4);
        }

        [data-theme="light"] {
            --bg-primary: #f0f4ff;
            --bg-secondary: #e0e7ff;
            --bg-card: rgba(255, 255, 255, 0.8);
            --text-primary: #1e293b;
            --text-secondary: #4A69FF;
            --text-muted: #5B7AFF;
            --glow-color: rgba(74, 105, 255, 0.2);
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
            position: relative;
        }

        /* Animated Background */
        .bg-animated {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .bg-animated::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 80%, var(--accent-1) 0%, transparent 25%),
                radial-gradient(circle at 80% 20%, var(--accent-2) 0%, transparent 25%),
                radial-gradient(circle at 40% 40%, var(--accent-3) 0%, transparent 20%);
            animation: bgFloat 20s ease-in-out infinite;
            opacity: 0.15;
        }

        @keyframes bgFloat {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(2%, 2%) rotate(5deg); }
            66% { transform: translate(-2%, 1%) rotate(-5deg); }
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--accent-1);
            border-radius: 50%;
            opacity: 0.6;
            animation: float 15s infinite;
        }

        .particle:nth-child(2) { left: 20%; animation-delay: -2s; background: var(--accent-2); }
        .particle:nth-child(3) { left: 40%; animation-delay: -4s; background: var(--accent-3); }
        .particle:nth-child(4) { left: 60%; animation-delay: -6s; }
        .particle:nth-child(5) { left: 80%; animation-delay: -8s; background: var(--accent-2); }
        .particle:nth-child(6) { left: 10%; animation-delay: -10s; }
        .particle:nth-child(7) { left: 30%; animation-delay: -12s; background: var(--accent-3); }
        .particle:nth-child(8) { left: 50%; animation-delay: -14s; }
        .particle:nth-child(9) { left: 70%; animation-delay: -3s; background: var(--accent-2); }
        .particle:nth-child(10) { left: 90%; animation-delay: -7s; }

        @keyframes float {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
            100% { transform: translateY(-10vh) scale(1); opacity: 0; }
        }

        /* Main Container */
        .landing-wrapper {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 100;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .theme-toggle:hover {
            transform: rotate(180deg) scale(1.1);
            box-shadow: 0 0 30px var(--glow-color);
        }

        .theme-toggle i {
            font-size: 1.3rem;
            color: var(--text-primary);
        }

        /* Hero Section */
        .hero-content {
            padding: 2rem;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2));
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
            font-size: 0.85rem;
            color: var(--accent-1);
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease;
        }

        [data-theme="light"] .hero-badge {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15));
        }

        .hero-badge i {
            font-size: 0.9rem;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease 0.1s both;
        }

        .hero-title .highlight {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2), var(--accent-3));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 1.15rem;
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: 2.5rem;
            max-width: 520px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        /* Stats Cards */
        .stats-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease 0.3s both;
        }

        .stat-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            flex: 1;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-1);
            box-shadow: 0 10px 40px var(--glow-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Login Card */
        .login-section {
            padding: 2rem;
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .login-card {
            background: var(--bg-card);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 2rem;
            padding: 3rem;
            max-width: 420px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2), var(--accent-3));
        }

        [data-theme="light"] .login-card {
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
        }

        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            border-radius: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px var(--glow-color);
        }

        .login-icon i {
            font-size: 2rem;
            color: white;
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .btn-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 1.1rem 2rem;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            border: none;
            border-radius: 1rem;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px var(--glow-color);
            color: white;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login i {
            font-size: 1.2rem;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--bg-card);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            border-color: var(--accent-1);
            transform: translateX(5px);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2));
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-icon i {
            color: var(--accent-1);
            font-size: 1rem;
        }

        .feature-text {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        /* Footer */
        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .footer-text a {
            color: var(--accent-1);
            text-decoration: none;
        }

        /* Alert Container */
        .alert-container {
            position: fixed;
            top: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            max-width: 400px;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 991px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-content {
                text-align: center;
            }
            
            .hero-description {
                margin: 0 auto 2rem;
            }
            
            .stats-row {
                justify-content: center;
            }

            .features-grid {
                max-width: 400px;
                margin: 2rem auto 0;
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .stats-row {
                flex-direction: column;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }

            .theme-toggle {
                top: 1rem;
                right: 1rem;
                width: 44px;
                height: 44px;
            }
        }
    </style>
</head>

<body>
    <!-- Animated Background -->
    <div class="bg-animated"></div>
    
    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <!-- Alert Messages -->
    @if (session('error'))
        <div class="alert-container">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="alert-container">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <div class="container landing-wrapper">
        <div class="row align-items-center w-100">
            <!-- Left: Hero Section -->
            <div class="col-lg-7 hero-content">
                <div class="hero-badge">
                    <i class="fas fa-sparkles"></i>
                    Perpustakaan UMS
                </div>
                
                <h1 class="hero-title">
                    Aplikasi Pendukung<br>
                    <span class="highlight">Akreditasi Prodi</span>
                </h1>
                
                <p class="hero-description">
                    Platform terintegrasi untuk mengakses data statistik perpustakaan yang mendukung 
                    proses akreditasi program studi di Universitas Muhammadiyah Surakarta.
                </p>

                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Prodi</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">100K+</div>
                        <div class="stat-label">Koleksi</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">Real-time</div>
                        <div class="stat-label">Data</div>
                    </div>
                </div>

                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="feature-text">Statistik Kunjungan</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <span class="feature-text">Data Koleksi</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="feature-text">Laporan Pemustaka</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-file-export"></i>
                        </div>
                        <span class="feature-text">Export Data</span>
                    </div>
                </div>
            </div>

            <!-- Right: Login Section -->
            <div class="col-lg-5 login-section">
                <div class="login-card">
                    <div class="login-icon">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    
                    <h2 class="login-title">Selamat Datang</h2>
                    <p class="login-subtitle">
                        Masuk menggunakan akun SSO UMS Anda
                    </p>

                    <a href="{{ route('cas.login') }}" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Login dengan SSO UMS
                    </a>

                    <p class="footer-text">
                        &copy; {{ date('Y') }} <a href="#">Perpustakaan UMS</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Management
        function getTheme() {
            return localStorage.getItem('theme') || 'dark';
        }

        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateIcon(theme);
        }

        function updateIcon(theme) {
            const icon = document.getElementById('theme-icon');
            icon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        }

        function toggleTheme() {
            const newTheme = getTheme() === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            setTheme(getTheme());
        });

        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>

</html>
