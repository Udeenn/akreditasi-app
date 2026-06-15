<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Library Data</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/img/logo4.png?v=1.2" type="image/png">
    <link rel="shortcut icon" href="/img/logo4.png?v=1.2" type="image/png">
    {{-- CSS LINKS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/unified-components.css') }}">
    {{-- NProgress (top loading bar) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.css">
<link rel="stylesheet" href="{{ asset('css/app-loader.css') }}">

    @stack('styles')

<link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">
</head>

<body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark' ? 'dark-mode' : '' }}">

    {{-- ====================================================== --}}
    {{-- GLOBAL PAGE LOADER OVERLAY --}}
    {{-- Dipicu oleh link/form dengan atribut data-heavy="true" --}}
    {{-- ====================================================== --}}
    <div id="page-loader" role="status" aria-label="Memuat halaman...">
        <div class="loader-ring"></div>
        <span class="loader-text">Memuat data&hellip;</span>
    </div>

    <div class="main-wrapper">
        {{-- Sidebar --}}
        <aside id="sidebar" class="sidebar">
            @include('partials.sidebar')
        </aside>

        <div class="flex-grow-1 d-flex flex-column">
            <header class="app-header">
                <div class="header-left d-flex align-items-center gap-3">
                    <button class="btn p-0 sidebar-toggle-btn" id="toggleSidebarBtn">
                        <i class="fas fa-bars"></i>
                    </button>

                    {{-- Greeting dan Waktu --}}
                    <div class="d-flex flex-column justify-content-center">
                        <span class="text-xs text-muted fw-bold">Selamat Datang,</span>
                        <h5 class="m-0 fw-bold" id="greeting-header">
                             @auth
                                {{ Auth::user()->name ?: Auth::user()->username }}
                            @else
                                <span id="greeting-text">Pengunjung</span>
                            @endauth
                        </h5>
                    </div>
                </div>
                <div class="header-right">
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end me-2 d-none d-lg-block">
                            <div id="current-date" class="small text-muted"></div>
                            <div id="current-time" class="fw-bold"></div>
                        </div>

                        <div class="d-flex align-items-center">
                            @guest
                                <a href="{{ route('cas.login') }}" class="btn btn-sm btn-outline-secondary text-body-emphasis">
                                    <i class="fas fa-sign-in-alt me-1"></i>
                                    Login
                                </a>
                            @else
                                <form action="{{ route('cas.logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-sign-out-alt me-1"></i>
                                        Logout</button>
                                </form>
                            @endguest
                        </div>
                    </div>
                </div>
            </header>

            {{-- Main Content Area --}}
            <div class="content-area">
                <main class="flex-grow-1">
                    {{-- @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif --}}
                    <!-- Modal Notifikasi -->
                    <div class="modal fade" id="notificationModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg">
                                <div class="modal-body text-center py-4">
                                    <div class="mb-3">
                                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="modal-title text-success fw-bold" id="notificationModalTitle">Berhasil!
                                    </h5>
                                    <p class="text-muted mt-2" id="notificationModalMessage"></p>
                                    <button type="button" class="btn btn-success mt-3" data-bs-dismiss="modal">
                                        <i class="fas fa-check me-1"></i> Oke
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- @include('partials.breadcrumb') -->
                    @yield('content')
                </main>

                @include('partials.footer')
            </div>
        </div>
    </div>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    {{-- ===== MOBILE BOTTOM NAV BAR ===== --}}
    @php
        $currentRoute = Route::currentRouteName();
        $isKoleksi = request()->routeIs('koleksi.*');
        $isAnalitik = request()->routeIs(['kunjungan.*', 'peminjaman.*', 'penggunaan.*', 'reward.*']);
    @endphp
    <nav class="mobile-bottom-nav" id="mobileBottomNav">
        <a href="{{ route('dashboard') }}" class="bnav-item {{ $currentRoute === 'dashboard' ? 'active' : '' }}">
            <i class="fas fa-home"></i>
            <span>Beranda</span>
        </a>
        <button type="button" class="bnav-item {{ $isKoleksi ? 'active' : '' }}" data-panel="koleksi">
            <i class="fas fa-book"></i>
            <span>Koleksi</span>
        </button>
        <button type="button" class="bnav-item {{ $isAnalitik ? 'active' : '' }}" data-panel="analitik">
            <i class="fas fa-chart-bar"></i>
            <span>Analitik</span>
        </button>
        <button type="button" class="bnav-item" data-panel="more">
            <i class="fas fa-ellipsis-h"></i>
            <span>Lainnya</span>
        </button>
    </nav>

    {{-- Mobile overlay --}}
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    {{-- Slide-up panel: Koleksi --}}
    <div class="mobile-menu-panel" id="panelKoleksi">
        <div class="panel-handle" data-panel-close></div>
        <div class="panel-body">
            <div class="panel-section-label">Daftar Koleksi</div>
            <a href="{{ route('koleksi.rekap_fakultas') }}" class="panel-nav-link {{ request()->routeIs('koleksi.rekap_fakultas') ? 'active' : '' }}">
                <i class="fas fa-university"></i> Per Fakultas
            </a>
            <a href="{{ route('koleksi.textbook') }}" class="panel-nav-link {{ request()->routeIs('koleksi.textbook') ? 'active' : '' }}">
                <i class="fas fa-book-open"></i> Text Book
            </a>
            <a href="{{ route('koleksi.ebook') }}" class="panel-nav-link {{ request()->routeIs('koleksi.ebook') ? 'active' : '' }}">
                <i class="fas fa-tablet-alt"></i> E-Book
            </a>
            <a href="{{ route('koleksi.jurnal') }}" class="panel-nav-link {{ request()->routeIs('koleksi.jurnal') ? 'active' : '' }}">
                <i class="fas fa-newspaper"></i> Journal
            </a>
            <a href="{{ route('koleksi.ejurnal') }}" class="panel-nav-link {{ request()->routeIs('koleksi.ejurnal') ? 'active' : '' }}">
                <i class="fas fa-globe"></i> E-Journal
            </a>
            <a href="{{ route('koleksi.prosiding') }}" class="panel-nav-link {{ request()->routeIs('koleksi.prosiding') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i> Prosiding
            </a>
            <a href="{{ route('koleksi.referensi') }}" class="panel-nav-link {{ request()->routeIs('koleksi.referensi') ? 'active' : '' }}">
                <i class="fas fa-bookmark"></i> Referensi
            </a>
            <a href="{{ route('koleksi.eresource') }}" class="panel-nav-link {{ request()->routeIs('koleksi.eresource') ? 'active' : '' }}">
                <i class="fas fa-database"></i> E-Resource
            </a>
        </div>
    </div>

    {{-- Slide-up panel: Analitik --}}
    <div class="mobile-menu-panel" id="panelAnalitik">
        <div class="panel-handle" data-panel-close></div>
        <div class="panel-body">
            <div class="panel-section-label">Kunjungan</div>
            <a href="{{ route('kunjungan.fakultasTable') }}" class="panel-nav-link {{ request()->routeIs('kunjungan.fakultasTable') ? 'active' : '' }}">
                <i class="fas fa-university"></i> Per Fakultas
            </a>
            <a href="{{ route('kunjungan.keseluruhan') }}" class="panel-nav-link {{ request()->routeIs('kunjungan.keseluruhan') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Keseluruhan
            </a>
            <a href="{{ route('kunjungan.prodi') }}" class="panel-nav-link {{ request()->routeIs('kunjungan.prodi') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Civitas Akademika
            </a>
            <a href="{{ route('kunjungan.cekKehadiran') }}" class="panel-nav-link {{ request()->routeIs('kunjungan.cekKehadiran') ? 'active' : '' }}">
                <i class="fas fa-search"></i> Cek Kunjungan
            </a>

            <div class="panel-section-label">Peminjaman</div>
            <a href="{{ route('peminjaman.peminjaman_fakultas') }}" class="panel-nav-link {{ request()->routeIs('peminjaman.peminjaman_fakultas') ? 'active' : '' }}">
                <i class="fas fa-university"></i> Per Fakultas
            </a>
            <a href="{{ route('peminjaman.keseluruhan') }}" class="panel-nav-link {{ request()->routeIs('peminjaman.keseluruhan') ? 'active' : '' }}">
                <i class="fas fa-chart-area"></i> Keseluruhan
            </a>
            <a href="{{ route('peminjaman.prodi') }}" class="panel-nav-link {{ request()->routeIs('peminjaman.prodi') ? 'active' : '' }}">
                <i class="fas fa-user-graduate"></i> Civitas Akademika
            </a>
            <a href="{{ route('peminjaman.cek_pinjaman') }}" class="panel-nav-link {{ request()->routeIs('peminjaman.cek_pinjaman') ? 'active' : '' }}">
                <i class="fas fa-search"></i> Cek Pinjaman
            </a>
            <a href="{{ route('peminjaman.berlangsung') }}" class="panel-nav-link {{ request()->routeIs('peminjaman.berlangsung') ? 'active' : '' }}">
                <i class="fas fa-clock"></i> Sedang Berlangsung
            </a>

            <div class="panel-section-label">Statistik Sirkulasi</div>
            <a href="{{ route('penggunaan.keterpakaian_koleksi') }}" class="panel-nav-link {{ request()->routeIs('penggunaan.keterpakaian_koleksi') ? 'active' : '' }}">
                <i class="fas fa-barcode"></i> Keterpakaian Koleksi
            </a>
            <a href="{{ route('penggunaan.cek_histori_buku_buku') }}" class="panel-nav-link {{ request()->routeIs('penggunaan.cek_histori_buku_buku') ? 'active' : '' }}">
                <i class="fas fa-history"></i> Cek Histori Buku
            </a>
            <a href="{{ route('penggunaan.sering_dibaca') }}" class="panel-nav-link {{ request()->routeIs('penggunaan.sering_dibaca') ? 'active' : '' }}">
                <i class="fas fa-trophy"></i> Buku Terlaris
            </a>
        </div>
    </div>

    {{-- Slide-up panel: Lainnya --}}
    <div class="mobile-menu-panel" id="panelMore">
        <div class="panel-handle" data-panel-close></div>
        <div class="panel-body">
            <div class="panel-section-label">Lainnya</div>
            <a href="{{ route('reward.pemustaka_teraktif') }}" class="panel-nav-link {{ request()->routeIs('reward.pemustaka_teraktif') ? 'active' : '' }}">
                <i class="fas fa-gift"></i> Pemustaka Teraktif
            </a>
            @auth
            @if(Auth::user()->isLibrarian())
            <a href="{{ route('admin.activity-log') }}" class="panel-nav-link {{ request()->routeIs('admin.activity-log') ? 'active' : '' }}">
                <i class="fas fa-shield-halved"></i> Audit Trail
            </a>
            @endif
            @endauth
        </div>
    </div>

    <div class="modal fade" id="welcomeModal" tabindex="-1" aria-labelledby="welcomeModalLabel">
        <div class="modal-dialog modal-dialog-centered welcome-modal-animate">
            <div class="modal-content text-center border-0 shadow-lg" style="border-radius: 1rem;">
                <div class="modal-body p-4 p-md-5">
                    <div class="welcome-modal-icon-container">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h4 class="modal-title fw-bold mb-2" id="welcomeModalGreeting">Selamat Datang!</h4>

                    <p class="text-muted">
                        Selamat datang di <b>Sistem Statistik UPT Perpustakaan dan Layanan Digital UMS</b>. Silahkan Jelajahi
                    </p>

                    <button type="button" class="btn btn-primary btn-lg mt-3" data-bs-dismiss="modal">
                        Mulai Jelajahi <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- SESSION TIMEOUT WARNING MODAL --}}
    {{-- Hanya ditampilkan untuk user yang sudah login --}}
    {{-- ============================================================ --}}
    @auth
    <div class="modal fade" id="sessionTimeoutModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="sessionTimeoutModalLabel" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">

                {{-- Gradient header bar --}}
                <div style="height: 6px; background: linear-gradient(90deg, #f59e0b, #ef4444);"></div>

                <div class="modal-body p-4 p-md-5 text-center">
                    {{-- Animated icon --}}
                    <div class="mb-4" style="position: relative; display: inline-block;">
                        <div style="
                            width: 90px; height: 90px; border-radius: 50%;
                            background: linear-gradient(135deg, #fef3c7, #fde68a);
                            display: flex; align-items: center; justify-content: center;
                            margin: 0 auto;
                            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3);
                            animation: pulseWarning 2s ease-in-out infinite;
                        ">
                            <i class="fas fa-clock" style="font-size: 2.5rem; color: #d97706;"></i>
                        </div>
                    </div>

                    <h4 class="fw-bold mb-2" id="sessionTimeoutModalLabel" style="color: var(--text-dark);">
                        Sesi Hampir Berakhir!
                    </h4>
                    <p class="text-muted mb-1">
                        Anda tidak aktif selama beberapa saat. Sesi akan otomatis berakhir dalam:
                    </p>

                    {{-- Countdown display --}}
                    <div class="my-3" style="
                        font-size: 3rem; font-weight: 800; letter-spacing: -1px;
                        background: linear-gradient(135deg, #f59e0b, #ef4444);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        background-clip: text;
                    " id="sessionCountdownDisplay">02:00</div>

                    <p class="text-muted small mb-4">
                        Klik <strong>Lanjutkan Sesi</strong> untuk tetap login, atau tunggu hingga otomatis logout.
                    </p>

                    <div class="d-flex gap-3 justify-content-center">
                        <button type="button" id="btnExtendSession" class="btn btn-primary px-4 py-2" style="border-radius: 0.75rem; font-weight: 600;">
                            <i class="fas fa-rotate-right me-2"></i>Lanjutkan Sesi
                        </button>
                        <form action="{{ route('cas.logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger px-4 py-2" style="border-radius: 0.75rem; font-weight: 600;">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endauth

    <div class="theme-fab">
        <button class="btn" id="theme-toggle" type="button" title="Ganti Tema">
            <i class="fas fa-moon"></i>
            <i class="fas fa-sun"></i>
        </button>
    </div>

    {{-- JS LINKS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    {{-- NProgress: harus dimuat sebelum inline script yang memakainya --}}
    <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.js"></script>
    @stack('scripts')
    @include('layouts.app-scripts')
</body>

</html>
