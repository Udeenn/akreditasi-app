<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Analitik Pustaka</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/x-icon">

    {{-- CSS LINKS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @stack('styles')

    <style>
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

        /* === STRUKTUR UTAMA (dan elemen lain agar transisinya mulus) === */
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

        /* === STRUKTUR UTAMA === */
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
            z-index: 1050;
            display: flex;
            flex-direction: column;
        }

        .content-area {
            flex-grow: 1;
            padding: 2rem;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease-in-out, background-color 0.3s ease;
            width: calc(100% - var(--sidebar-width));
            /* [PENTING] Agar content area tidak overflow */
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
            /* Dihapus margin-left agar mengikuti content-area */
            transition: background-color 0.3s ease;
        }

        /* [BARU] Tombol Toggle Sidebar */
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

        /* FAB Dark Mode (TIDAK PERLU DIUBAH) */
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

        /* FOOTER (TIDAK PERLU DIUBAH) */
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
                transform: translateX(0);
                box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
            }

            .content-area,
            .app-header {
                margin-left: 0;
                width: 100%;
                /* Pastikan lebar penuh */
            }

            /* [PENTING] Tampilkan tombol toggle hanya di mobile */
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
    </style>
</head>

<body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark' ? 'dark-mode' : '' }}">
    <div class="main-wrapper">
        {{-- Sidebar --}}
        <aside id="sidebar" class="sidebar">
            @include('partials.sidebar')
        </aside>

        {{-- [DIUBAH] Wrapper untuk Header dan Konten --}}
        <div class="flex-grow-1 d-flex flex-column">
            {{-- [BARU] Header Aplikasi --}}
            <header class="app-header">
                <div class="header-left d-flex align-items-center gap-3">
                    {{-- [INI TOMBOLNYA] Tombol untuk menampilkan sidebar di mobile --}}
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
                    <div class="text-end">
                        <div id="current-date" class="small text-muted"></div>
                        <div id="current-time" class="fw-bold"></div>
                    </div>
                </div>
            </header>

            {{-- Main Content Area --}}
            <div class="content-area">
                <main class="flex-grow-1">
                    @yield('content')
                </main>

                @include('partials.footer')
            </div>
        </div>
    </div>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

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
        document.addEventListener('DOMContentLoaded', function() {
            // Skrip untuk Greeting dan Waktu (TIDAK BERUBAH)
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
            }

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

            // Skrip untuk Sidebar Toggle di Mobile (TIDAK BERUBAH)
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebarBtn');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    sidebarBackdrop.classList.toggle('show');
                });
            }

            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                });
            }

            // Skrip untuk Dark Mode Toggle (TIDAK BERUBAH)
            const themeToggle = document.getElementById('theme-toggle');
            const body = document.body;

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
        });
    </script>
</body>

</html>
