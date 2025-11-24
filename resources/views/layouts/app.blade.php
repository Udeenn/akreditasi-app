<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Library Data</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/x-icon">

    {{-- CSS LINKS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @stack('styles')

    <style>
        .sidebar-menu {
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) transparent;
        }

        .sidebar-menu::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar-menu::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 10px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb {
            background-color: rgba(var(--bs-primary-rgb), 0.5);
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: content-box;
        }

        .sidebar-menu::-webkit-scrollbar-thumb:hover {
            background-color: rgba(var(--bs-primary-rgb), 0.8);
        }

        body.dark-mode .sidebar-menu {
            scrollbar-color: #555 transparent;
        }

        body.dark-mode .sidebar-menu::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
        }

        body.dark-mode .sidebar-menu::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }

        .sidebar .nav-item>button.nav-link.active {
            background-color: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 4px 10px -2px rgba(74, 105, 255, 0.5);
        }

        .sidebar .nav-item>button.nav-link {
            background-color: transparent;
            border: none;
            padding: 0.75rem 1rem;
            text-align: left;
            width: 100%;
            display: flex;
            align-items: center;
            border-radius: 8px;
            color: var(--text-light);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar .nav-item>button.nav-link:hover,
        .sidebar .nav-item>button.nav-link:focus {
            background-color: var(--primary-light);
            color: var(--primary-color);
            box-shadow: none;
            outline: none;
        }

        /* === PALET WARNA MODERN (LIGHT & DARK MODE) === */
        :root {
            /* [DEFAULT LIGHT MODE] */
            --primary-color: #4A69FF;
            --primary-hover: #3b54cc;
            --primary-light: #eef2ff;
            --sidebar-bg: #ffffff;
            --main-bg: #f5f7fa;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --sidebar-width: 260px;
        }

        body.dark-mode {
            /* [DARK MODE OVERRIDE] */
            --primary-light: rgba(74, 105, 255, 0.15);
            --sidebar-bg: #1e293b;
            --main-bg: #0f172a;
            --text-dark: #e2e8f0;
            --text-light: #94a3b8;
            --border-color: #334155;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.2), 0 2px 4px -2px rgb(0 0 0 / 0.2);
        }



        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--main-bg);
            color: var(--text-dark);
            font-size: 0.95rem;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .main-wrapper,
        .sidebar,
        .app-header,
        .dropdown-menu,
        .card {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        .dropdown-menu {
            background-color: var(--sidebar-bg);
            border: 1px solid var(--border-color);
        }

        .dropdown-item {
            color: var(--text-light);
        }

        .dropdown-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            transition: transform 0.3s ease-in-out, background-color 0.3s ease, color 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1049;
            display: flex;
            flex-direction: column;
        }



        .content-area {
            flex-grow: 1;
            padding: 2rem;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease-in-out, background-color 0.3s ease;
            width: calc(100% - var(--sidebar-width));
        }

        /* === HEADER APLIKASI === */
        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: color-mix(in srgb, var(--sidebar-bg) 80%, transparent);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: background-color 0.3s ease;
        }

        .header-left .sidebar-toggle-btn {
            display: none;
            /* Sembunyikan di desktop */
            font-size: 1.25rem;
            color: var(--text-light);
        }

        .header-left .page-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .header-right .dropdown-toggle::after {
            display: none;
        }

        .header-right .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* === SIDEBAR MODERN === */
        .sidebar-header {
            padding: 1.5rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-title {
            font-weight: 600;
            color: var(--text-dark);
        }

        .sidebar-menu {
            overflow-y: auto;
            flex-grow: 1;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            color: var(--text-light);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 4px 10px -2px rgba(74, 105, 255, 0.5);
        }

        .sidebar .nav-icon {
            min-width: 35px;
            font-size: 1rem;
        }

        .sidebar .nav-arrow-small {
            font-size: 0.7rem;
            transition: transform 0.2s ease-in-out;
        }

        .sidebar .nav-link[aria-expanded="true"] .nav-arrow-small {
            transform: rotate(180deg);
        }

        .sidebar .sub-menu {
            list-style: none;
            padding-left: 2.2rem;
        }

        .sidebar .sub-menu .nav-link {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            position: relative;
        }

        /* body.dark-mode .text-body-emphasis {
            color: var(--bs-light-text-emphasis) !important;
        } */

        body.dark-mode .text-body-emphasis {
            color: #ffffff !important;
        }

        body.dark-mode .card-body .list-group-item {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
        }

        body.dark-mode .card-body .list-group-item-action:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        .sidebar .sub-menu .nav-link.active {
            font-weight: 600;
            color: var(--primary-color);
            background-color: transparent;
            box-shadow: none;
        }

        .sidebar .sub-menu .nav-link::before {
            content: '';
            position: absolute;
            left: -1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background-color: #cbd5e1;
        }

        .sidebar .sub-menu .nav-link.active::before {
            background-color: var(--primary-color);
        }

        /* DARK MODE STYLES (TIDAK PERLU DIUBAH) */
        body.dark-mode .text-muted {
            color: var(--text-light) !important;
        }

        body.dark-mode h5 {
            color: var(--text-dark);
        }


        body.dark-mode span.badge {
            color: var(--text-dark);
        }

        body.dark-mode th {
            color: var(--text-dark);
        }

        body.dark-mode td {
            color: var(--text-dark);
        }

        body.dark-mode .input-group-text {
            background-color: #1e293b;
            color: var(--text-dark);
            border-color: var(--border-color);
        }

        body.dark-mode .card-header {
            background-color: #1e293b;
            border-bottom-color: var(--border-color) !important;
        }

        body.dark-mode .form-control::placeholder {
            color: var(--text-light);
            opacity: 1;
        }

        body.dark-mode .card-header {
            color: var(--text-dark);
            background-color: #1e293b;
        }

        body.dark-mode .card-body {
            color: var(--text-dark);
            background-color: #1e293b;
        }

        body.dark-mode .nav-label {
            color: var(--text-light);
            opacity: 0.8;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #1e293b;
            color: var(--text-dark);
            border-color: var(--border-color);
        }

        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background-color: #1e293b;
            color: var(--text-dark);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(74, 105, 255, 0.25);
        }

        body.dark-mode a:not(.btn):not(.nav-link):not(.dropdown-item) {
            color: #a5b4fc;
        }

        body.dark-mode a:not(.btn):not(.nav-link):not(.dropdown-item):hover {
            color: #c7d2fe;
        }

        body.dark-mode .table {
            --bs-table-color: var(--text-dark);
            --bs-table-bg: transparent;
            --bs-table-border-color: var(--border-color);
            --bs-table-striped-bg: rgba(255, 255, 255, 0.05);
            --bs-table-striped-color: var(--text-dark);
            --bs-table-hover-bg: rgba(255, 255, 255, 0.1);
            --bs-table-hover-color: var(--text-dark);
        }

        body.dark-mode .table thead th {
            background-color: #1e293b;
        }

        body.dark-mode .alert {
            --bs-alert-bg: #1e293b;
            --bs-alert-border-color: var(--border-color);
            --bs-alert-color: var(--text-dark);
        }

        body.dark-mode .pagination .page-link {
            background-color: #1e293b;
            border-color: var(--border-color);
            color: var(--text-light);
        }

        body.dark-mode .pagination .page-link:hover {
            background-color: #334155;
        }

        body.dark-mode .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }

        body.dark-mode .pagination .page-item.disabled .page-link {
            color: #4b5563;
            background-color: transparent;
        }

        /* FAB Dark Mode */
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
            background-color: var(--sidebar-bg);
            color: var(--text-light);
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .theme-fab #theme-toggle:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }


        .modal-backdrop {
            backdrop-filter: blur(5px);
            background-color: rgba(15, 23, 42, 0.3);
            z-index: 1051;
        }

        body.dark-mode .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-backdrop.show {
            opacity: 1;
        }

        @keyframes floatAnimation {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        .welcome-modal-icon-container {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.5rem auto;
            /* Atur margin */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: #ffffff;
            background: linear-gradient(45deg, #0d6efd, #6f42c1);
            box-shadow: 0 10px 20px rgba(74, 105, 255, 0.3);
            /* Terapkan animasi */
            animation: floatAnimation 3s ease-in-out infinite;
        }

        .welcome-modal-animate.modal.fade .modal-dialog {
            transform: scale(0.8);
            opacity: 0;
            transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), opacity 0.3s ease;
        }

        .welcome-modal-animate.modal.fade.show .modal-dialog {
            transform: scale(1);
            opacity: 1;
        }

        body.dark-mode .modal-content {
            background-color: var(--sidebar-bg);
            border-color: var(--border-color);
        }

        #theme-toggle .fa-sun {
            display: none;
        }

        body.dark-mode .modal-title {
            color: var(--text-dark);
        }

        body.dark-mode .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        body.dark-mode #theme-toggle .fa-sun {
            display: inline-block;
        }

        body.dark-mode .card {
            background-color: var(--sidebar-bg);
            border-color: var(--border-color);
        }

        body.dark-mode #theme-toggle .fa-moon {
            display: none;
        }

        body.dark-mode .modal-content {
            background-color: var(--sidebar-bg);
            border-color: var(--border-color);
        }

        body.dark-mode .modal-header {
            border-bottom-color: var(--border-color);
        }

        body.dark-mode .modal-footer {
            border-top-color: var(--border-color);
        }

        body.dark-mode .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* FOOTER  */
        .app-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-light);
            transition: border-color 0.3s ease, color 0.3s ease;
        }

        /* === RESPONSIVE === */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                z-index: 1050;
                transform: translateX(0);
                box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
            }

            .content-area,
            .app-header {
                margin-left: 0;
                width: 100%;
            }

            .header-left .sidebar-toggle-btn {
                display: block;
            }

            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.3);
                z-index: 1049;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s;
            }

            .sidebar-backdrop.show {
                opacity: 1;
                visibility: visible;
            }


        }

        .sidebar .collapse {
            transition: none !important;
        }
    </style>
</head>

<body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark' ? 'dark-mode' : '' }}">
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
                    <h5 class="m-0 fw-normal" id="greeting-header">
                        <span id="greeting-icon"></span>
                        <span id="greeting-text"></span>
                    </h5>
                </div>
                <div class="header-right">
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end me-2 d-none d-lg-block">
                            <div id="current-date" class="small text-muted"></div>
                            <div id="current-time" class="fw-bold"></div>
                        </div>

                        <div class="d-flex align-items-center">
                            @guest
                                {{-- Ini diubah jadi tombol untuk mentrigger modal --}}
                                <button type="button" class="btn btn-sm btn-outline-secondary text-body-emphasis"
                                    data-bs-toggle="modal" data-bs-target="#loginModal"><i
                                        class="fas fa-sign-in-alt me-1"></i>
                                    Login
                                </button>
                            @else
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger text-body-emphasis">
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
                    <div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
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
                    @yield('content')
                </main>

                @include('partials.footer')
            </div>
        </div>
    </div>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="modal fade" id="welcomeModal" tabindex="-1" aria-labelledby="welcomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered welcome-modal-animate">
            <div class="modal-content text-center border-0 shadow-lg" style="border-radius: 1rem;">
                <div class="modal-body p-4 p-md-5">
                    <div class="welcome-modal-icon-container">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h4 class="modal-title fw-bold mb-2" id="welcomeModalGreeting">Selamat Datang!</h4>

                    <p class="text-muted">
                        Selamat datang di <b>Sistem Perpustakaan Pendukung Data Akreditasi Prodi</b>. Silahkan Jelajahi
                        data
                        statistik Perpustakaan dengan mudah.
                    </p>

                    <button type="button" class="btn btn-primary btn-lg mt-3" data-bs-dismiss="modal">
                        Mulai Jelajahi <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="loginModalLabel">
                        <i class="fas fa-user-shield me-2 text-primary"></i>
                        Masuk Sebagai Staff Perpustakaan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="modal-body p-4">

                        @if ($errors->any() && !$errors->has('username') && !$errors->has('password'))
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="modal_username" class="form-label fw-bold">Username</label>
                            <input id="modal_username" type="text"
                                class="form-control form-control-lg @error('username') is-invalid @enderror"
                                name="username" value="{{ old('username') }}" required autocomplete="username"
                                autofocus placeholder="Masukkan username">
                            @error('username')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>
                        <div class="mb-3">
                            <label for="modal_password" class="form-label fw-bold">Password</label>
                            <input id="modal_password" type="password"
                                class="form-control form-control-lg @error('password') is-invalid @enderror"
                                name="password" value="{{ old('password') }}" required
                                autocomplete="current-password" placeholder="Masukkan password">
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"
                            style="--bs-btn-bg: #4A69FF; --bs-btn-border-color: #4A69FF;">
                            Masuk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="theme-fab">
        <button class="btn" id="theme-toggle" type="button" title="Ganti Tema">
            <i class="fas fa-moon"></i>
            <i class="fas fa-sun"></i>
        </button>
    </div>

    {{-- JS LINKS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')

    <script>
        @if (session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
                document.getElementById('notificationModalMessage').textContent = "{{ session('success') }}";
                modal.show();
            });
        @endif
        document.addEventListener('DOMContentLoaded', function() {
            function updateGreeting() {
                const now = new Date();
                const hour = now.getHours();
                let greetingText = "";
                let iconClass = "";

                if (hour >= 5 && hour < 12) {
                    greetingText = "Selamat Pagi";
                    iconClass = "fas fa-sun text-warning me-2";
                } else if (hour >= 12 && hour < 15) {
                    greetingText = "Selamat Siang";
                    iconClass = "fas fa-cloud-sun text-info me-2";
                } else if (hour >= 15 && hour < 19) {
                    greetingText = "Selamat Sore";
                    iconClass = "fas fa-cloud-sun text-primary me-2";
                } else {
                    greetingText = "Selamat Malam";
                    iconClass = "fas fa-moon me-2";
                }

                $('#greeting-text').text(greetingText);
                $('#greeting-icon').removeClass().addClass(iconClass);

                $('#welcomeModalGreeting').text(greetingText + "!");
            }

            @if (
                $errors->has('username') ||
                    $errors->has('password') ||
                    ($errors->any() && !$errors->has('username') && !$errors->has('password')))
                // 1. Tampilkan modal jika login GAGAL (ada error)
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            @elseif (session('show_login_modal'))
                // 2. TAMPILKAN MODAL JIKA DIALIHKAN DARI HALAMAN PROTEKSI
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            @endif


            function updateTime() {
                const now = new Date();
                const dateOptions = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                const timeOptions = {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                };
                const formattedDate = new Intl.DateTimeFormat('id-ID', dateOptions).format(now);
                const formattedTime = new Intl.DateTimeFormat('id-ID', timeOptions).format(now).replace(/\./g,
                    ':');

                $('#current-date').text(formattedDate);
                $('#current-time').text(formattedTime);
            }

            updateGreeting();
            updateTime();
            setInterval(updateTime, 1000);

            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebarBtn');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const body = document.body;

            function isMobile() {
                return window.innerWidth < 992;
            }
            if (!isMobile() && localStorage.getItem('sidebarState') === 'collapsed') {
                body.classList.add('sidebar-collapsed');
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    if (isMobile()) {
                        sidebar.classList.toggle('show');
                        sidebarBackdrop.classList.toggle('show');
                    } else {
                        body.classList.toggle('sidebar-collapsed');

                        if (body.classList.contains('sidebar-collapsed')) {
                            localStorage.setItem('sidebarState', 'collapsed');
                        } else {
                            localStorage.setItem('sidebarState', 'expanded');
                        }
                    }
                });
            }
            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', function() {
                    if (isMobile()) {
                        sidebar.classList.remove('show');
                        sidebarBackdrop.classList.remove('show');
                    }
                });
            }

            const themeToggle = document.getElementById('theme-toggle');

            if (localStorage.getItem('theme') === 'dark') {
                body.classList.add('dark-mode');
            }

            themeToggle.addEventListener('click', function() {
                body.classList.toggle('dark-mode');

                if (body.classList.contains('dark-mode')) {
                    localStorage.setItem('theme', 'dark');
                } else {
                    localStorage.setItem('theme', 'light');
                }
            });

            const welcomeModalEl = document.getElementById('welcomeModal');
            if (welcomeModalEl && !localStorage.getItem('welcomeModalShown')) {


                const welcomeModal = new bootstrap.Modal(welcomeModalEl);
                welcomeModal.show();
                localStorage.setItem('welcomeModalShown', 'true');
            }

            document.querySelectorAll('.collapse').forEach(function(collapseEl) {
                const targetId = collapseEl.id;
                const button = document.querySelector(`[data-bs-target="#${targetId}"]`);

                if (button && !button.classList.contains('active')) {
                    const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
                    if (bsCollapse && collapseEl.classList.contains('show')) {
                        bsCollapse.hide();
                    }
                }
            });




        });
    </script>

</body>

</html>
