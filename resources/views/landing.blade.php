<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Statistik - UPT Perpustakaan UMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Palet Warna Utama (Sesuai dengan dashboard app.blade.php) */
            --primary-color: #4A69FF;
            --primary-hover: #3b54cc;
            --primary-light: #eef2ff;
            --main-bg: #f5f7fa;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --accent-color: #f4b136;
        }

        [data-theme="dark"] {
            --primary-light: rgba(74, 105, 255, 0.15);
            --main-bg: #0f172a;
            --card-bg: #1e293b;
            --text-dark: #e2e8f0;
            --text-light: #94a3b8;
            --border-color: #334155;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--main-bg);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Navbar Sederhana */
        .navbar-custom {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.15rem;
        }

        .brand-logo img {
            height: 45px;
            object-fit: contain;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .brand-text span:last-child {
            font-size: 0.85rem;
            color: var(--text-light);
            font-weight: 500;
        }

        /* Main Wrapper - Split Design */
        .main-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            position: relative;
            padding: 3rem 0;
        }

        /* Hero Section (Left) */
        .hero-section {
            padding-right: 2rem;
            position: relative;
            z-index: 10;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: var(--primary-light);
            color: var(--primary-color);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .badge-status i {
            color: var(--accent-color);
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            line-height: 1.2;
            color: var(--text-dark);
            margin-bottom: 1.25rem;
            letter-spacing: -0.02em;
        }

        .hero-title span {
            color: var(--primary-color);
        }

        .hero-desc {
            font-size: 1.1rem;
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 2.5rem;
            max-width: 550px;
        }

        /* Feature List (Clean Text Style) */
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0 0 2rem 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .feature-list i {
            color: var(--primary-color);
            background-color: var(--primary-light);
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        /* Auth Card (Right) */
        .auth-container {
            position: relative;
        }

        .auth-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1.25rem;
            padding: 2.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            position: relative;
            z-index: 10;
            transition: all 0.3s ease;
        }

        .auth-card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-icon {
            width: 64px;
            height: 64px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin: 0 auto 1.25rem;
            box-shadow: 0 4px 10px rgba(74, 105, 255, 0.2);
        }

        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            font-size: 0.95rem;
            color: var(--text-light);
        }

        .btn-sso {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 20px;
            background-color: var(--primary-color);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-sso:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(74, 105, 255, 0.2);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.85rem;
            color: var(--text-light);
        }

        /* Theme Switch (FAB Bottom Right) */
        .theme-fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1051;
        }

        .theme-fab #theme-toggle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            background-color: var(--card-bg);
            color: var(--text-light);
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .theme-fab #theme-toggle:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
            color: var(--primary-color);
        }

        #theme-toggle .fa-sun {
            display: none;
        }

        [data-theme="dark"] #theme-toggle .fa-sun {
            display: inline-block;
        }

        [data-theme="dark"] #theme-toggle .fa-moon {
            display: none;
        }



        /* Responsive */
        @media (max-width: 991.98px) {
            .hero-section {
                padding-right: 0;
                margin-bottom: 3rem;
                text-align: center;
            }
            .hero-desc {
                margin: 0 auto 2.5rem;
            }
            .feature-list {
                text-align: left;
                max-width: 400px;
                margin: 0 auto 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-title {
                font-size: 2.25rem;
            }
            .feature-list {
                grid-template-columns: 1fr;
            }
            .auth-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- Content -->
    <main class="main-wrapper">
        <div class="container">
            <!-- Alert Handling -->
            @if (session('error') || session('success') || session('warning'))
            <div class="row justify-content-center mb-4">
                <div class="col-md-8 text-center position-relative" style="z-index: 20;">
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show d-inline-block text-start w-100" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif
                    
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show d-inline-block text-start w-100" role="alert">
                        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show d-inline-block text-start w-100" role="alert">
                        <i class="fas fa-clock me-2"></i> {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="row align-items-center">
                <!-- Left: Hero Text -->
                <div class="col-lg-7 hero-section">
                    <div class="badge-status">
                        UPT Perpustakaan dan Layanan Digital UMS
                    </div>
                    
                    <h1 class="hero-title">
                        Data Statistik Untuk<br>
                        <span>Keperluan Prodi</span>
                    </h1>
                    
                    <p class="hero-desc">
                        Platform resmi penyedia layanan data statistik perpustakaan guna mempermudah proses penyusunan borang akreditasi Program Studi di lingkungan Universitas Muhammadiyah Surakarta.
                    </p>

                    <ul class="feature-list">
                        <li>
                            <i class="fas fa-book-open"></i>
                            <span>Rekapitulasi Koleksi</span>
                        </li>
                        <li>
                            <i class="fas fa-users"></i>
                            <span>Data Kunjungan Harian</span>
                        </li>
                        <li>
                            <i class="fas fa-hand-holding-heart"></i>
                            <span>Statistik Sirkulasi</span>
                        </li>
                        <li>
                            <i class="fas fa-file-csv"></i>
                            <span>Export Laporan Cepat</span>
                        </li>
                    </ul>
                </div>

                <!-- Right: Login Box -->
                <div class="col-lg-5 auth-container">
                    <div class="auth-card">
                        <div class="auth-card-header">
                            <div class="auth-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h2 class="auth-title">Akses Dasbor</h2>
                            <p class="auth-subtitle">Gunakan akun Single Sign-On (SSO) CAS UMS Anda untuk melanjutkan.</p>
                        </div>

                        <a href="{{ route('cas.login') }}" class="btn-sso">
                            <i class="fas fa-sign-in-alt"></i>
                            Masuk dengan SSO UMS
                        </a>

                        <div class="auth-footer">
                            UPT Perpustakaan dan Layanan Digital UMS        
                        <br>    
                            Universitas Muhammadiyah Surakarta &copy; {{ date('Y') }} 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- FAB Theme Toggle -->
    <div class="theme-fab">
        <button class="btn" id="theme-toggle" type="button" title="Ganti Tema" onclick="toggleTheme()">
            <i class="fas fa-moon"></i>
            <i class="fas fa-sun"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Management (Selaras dengan Dashboard app.blade.php)
        const getTheme = () => {
            return localStorage.getItem('theme') || 'light';
        };
        
        const updateIcon = (theme) => {
            const icon = document.getElementById('theme-icon');
            const logo = document.getElementById('landing-logo');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
            if (logo) {
                logo.src = theme === 'dark' ? '{{ asset("img/logo4.png") }}' : '{{ asset("img/logo4a.png") }}';
            }
        };

        const setTheme = (theme) => {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateIcon(theme);
        };

        const toggleTheme = () => {
            const newTheme = getTheme() === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        };

        // Initialization
        document.addEventListener('DOMContentLoaded', () => {
            const currentTheme = getTheme();
            setTheme(currentTheme);
            
            // Auto dismiss alerts
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>
