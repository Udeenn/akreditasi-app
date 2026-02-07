<!DOCTYPE html>
<html lang="id">

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

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #1a1a2e 100%);
            color: #fff;
            overflow-x: hidden;
        }

        .landing-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
        }

        /* Decorative elements */
        .bg-decoration {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.3;
            pointer-events: none;
        }

        .bg-decoration-1 {
            width: 400px;
            height: 400px;
            background: #4A69FF;
            top: -100px;
            left: -100px;
        }

        .bg-decoration-2 {
            width: 300px;
            height: 300px;
            background: #7c3aed;
            bottom: -50px;
            right: -50px;
        }

        .bg-decoration-3 {
            width: 200px;
            height: 200px;
            background: #06b6d4;
            top: 50%;
            right: 30%;
        }

        .welcome-section {
            padding: 3rem;
        }

        .logo-container {
            margin-bottom: 2rem;
        }

        .logo-container img {
            height: 60px;
            width: auto;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .welcome-title span {
            background: linear-gradient(90deg, #4A69FF, #7c3aed, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .welcome-description {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.8;
            margin-bottom: 2rem;
            max-width: 500px;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
        }

        .features-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            color: rgba(255, 255, 255, 0.8);
        }

        .features-list li i {
            color: #4A69FF;
            font-size: 1rem;
        }

        .login-section {
            padding: 3rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 3rem;
            max-width: 400px;
            margin: 0 auto;
        }

        .login-card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .login-card-subtitle {
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .btn-cas-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #4A69FF 0%, #7c3aed 100%);
            border: none;
            border-radius: 0.75rem;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(74, 105, 255, 0.4);
        }

        .btn-cas-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 105, 255, 0.5);
            color: #fff;
        }

        .btn-cas-login i {
            font-size: 1.2rem;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.85rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .divider span {
            padding: 0 1rem;
        }

        .btn-staff-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-staff-login:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            color: #fff;
        }

        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.85rem;
        }

        .alert-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            max-width: 400px;
        }

        @media (max-width: 991px) {
            .welcome-section {
                text-align: center;
            }

            .welcome-description {
                margin: 0 auto 2rem;
            }

            .features-list {
                display: inline-block;
                text-align: left;
            }

            .welcome-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .welcome-section,
            .login-section {
                padding: 2rem 1.5rem;
            }

            .login-card {
                padding: 2rem 1.5rem;
            }

            .welcome-title {
                font-size: 1.75rem;
            }
        }
    </style>
</head>

<body>
    <!-- Background decorations -->
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>

    <!-- Alert messages -->
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

    <div class="container landing-container">
        <div class="row align-items-center w-100">
            <!-- Left: Welcome Section -->
            <div class="col-lg-6 welcome-section">
                <div class="logo-container">
                    <img src="{{ asset('img/logo4a.png') }}" alt="UMS Library">
                </div>
                <h1 class="welcome-title">
                    Selamat Datang di<br>
                    <span>Aplikasi Pendukung Akreditasi Prodi</span>
                </h1>
                <p class="welcome-description">
                    Platform data dan statistik perpustakaan untuk mendukung proses akreditasi 
                    program studi di Universitas Muhammadiyah Surakarta.
                </p>
                <ul class="features-list">
                    <li>
                        <i class="fas fa-chart-line"></i>
                        Statistik kunjungan dan peminjaman
                    </li>
                    <li>
                        <i class="fas fa-book"></i>
                        Data koleksi per program studi
                    </li>
                    <li>
                        <i class="fas fa-users"></i>
                        Laporan aktivitas pemustaka
                    </li>
                    <li>
                        <i class="fas fa-file-export"></i>
                        Export data untuk akreditasi
                    </li>
                </ul>
            </div>

            <!-- Right: Login Section -->
            <div class="col-lg-6 login-section">
                <div class="login-card">
                    <h2 class="login-card-title">Masuk</h2>
                    <p class="login-card-subtitle">
                        Gunakan akun SSO UMS untuk mengakses aplikasi
                    </p>

                    <a href="{{ route('cas.login') }}" class="btn-cas-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Login dengan SSO UMS
                    </a>

                    <div class="divider">
                        <span>atau</span>
                    </div>

                    <a href="{{ route('login') }}" class="btn-staff-login">
                        <i class="fas fa-user-shield"></i>
                        Login Staff Perpustakaan
                    </a>

                    <p class="footer-text">
                        &copy; {{ date('Y') }} Perpustakaan UMS
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>

</html>
