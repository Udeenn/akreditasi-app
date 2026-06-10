<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Library Data</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('img/logo4.png') }}?v=1.2" type="image/png">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}?v=1.2" type="image/png">
    {{-- CSS LINKS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/unified-components.css') }}">
    {{-- NProgress (top loading bar) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.css">
    <style>
        /* ── Breadcrumb ── */
        .breadcrumb-nav {
            border-bottom: 1px solid var(--border-color, rgba(0,0,0,0.08));
            background: transparent;
        }
        .breadcrumb {
            font-size: 0.8rem;
            gap: 0;
        }
        .breadcrumb-item + .breadcrumb-item::before {
            content: '/';
            color: var(--text-light, #9ca3af);
            opacity: 0.5;
            padding: 0 0.4rem;
        }
        .breadcrumb-item a,
        .breadcrumb-home {
            color: var(--primary-color, #4A69FF);
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .breadcrumb-item a:hover,
        .breadcrumb-home:hover {
            opacity: 0.7;
        }
        .breadcrumb-item.active {
            color: var(--text-dark, #1f2937);
            font-weight: 500;
        }
        .breadcrumb-group {
            color: var(--text-light, #6b7280);
        }
        body.dark-mode .breadcrumb-item.active {
            color: rgba(255,255,255,0.85);
        }
        body.dark-mode .breadcrumb-group {
            color: rgba(255,255,255,0.45);
        }
        /* ── NProgress custom style ── */
        #nprogress .bar {
            background: linear-gradient(90deg, #4A69FF, #818cf8, #4A69FF) !important;
            background-size: 200% 100% !important;
            animation: shimmerBar 1.4s ease infinite !important;
            height: 3px !important;
            box-shadow: 0 0 10px #4A69FF, 0 0 5px #818cf8 !important;
        }
        #nprogress .peg {
            box-shadow: 0 0 10px #4A69FF, 0 0 5px #818cf8 !important;
        }
        #nprogress .spinner-icon {
            display: none !important; /* Sembunyikan spinner kecil bawaan NProgress */
        }
        @keyframes shimmerBar {
            0%   { background-position: 200% center; }
            100% { background-position: -200% center; }
        }

        /* ── Page overlay loader ── */
        #page-loader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 1.25rem;
            background: rgba(10, 10, 30, 0.82);
            backdrop-filter: blur(6px);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }
        #page-loader.show {
            opacity: 1;
            visibility: visible;
        }
        .loader-ring {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: 3px solid rgba(74, 105, 255, 0.15);
            border-top-color: #4A69FF;
            border-right-color: #818cf8;
            animation: spin 0.8s cubic-bezier(0.6, 0.2, 0.4, 0.8) infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loader-text {
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            color: rgba(255,255,255,0.55);
            letter-spacing: 0.5px;
            animation: fadePulse 1.6s ease-in-out infinite;
        }
        @keyframes fadePulse {
            0%, 100% { opacity: 0.4; }
            50%       { opacity: 0.9; }
        }
    </style>

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
            transition: background-color 0.3s ease, margin-left 0.3s ease-in-out;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
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


        @keyframes pulseWarning {
            0%, 100% { transform: scale(1); box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3); }
            50% { transform: scale(1.08); box-shadow: 0 12px 32px rgba(245, 158, 11, 0.5); }
        }

        /* Progress ring for countdown */
        #sessionTimeoutModal .modal-content {
            transition: none;
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
                display: none !important;
            }

            .content-area,
            .app-header {
                margin-left: 0;
                width: 100%;
            }

            .content-area {
                padding: 1rem;
                padding-bottom: calc(1rem + 80px); /* space for bottom nav */
            }

            .app-header {
                padding: 0.75rem 1rem;
            }

            .header-left .sidebar-toggle-btn {
                display: none; /* No longer needed, bottom nav replaces it */
            }

            /* Theme FAB: move above bottom nav */
            .theme-fab {
                bottom: 5.5rem !important;
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

        /* ===== MOBILE BOTTOM NAV BAR ===== */
        .mobile-bottom-nav,
        .mobile-menu-panel,
        .mobile-menu-overlay {
            display: none;
        }

        @media (max-width: 991.98px) {
            .mobile-bottom-nav {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 1050;
                background-color: var(--sidebar-bg);
                border-top: 1px solid var(--border-color);
                padding: 0.35rem 0;
                padding-bottom: calc(0.35rem + env(safe-area-inset-bottom, 0px));
                justify-content: space-around;
                align-items: center;
                box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.08);
                backdrop-filter: blur(12px);
            }

            .mobile-bottom-nav .bnav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                color: var(--text-light);
                font-size: 0.65rem;
                font-weight: 500;
                padding: 0.25rem 0.5rem;
                border-radius: 10px;
                transition: all 0.2s ease;
                position: relative;
                flex: 1;
                max-width: 80px;
                -webkit-tap-highlight-color: transparent;
                cursor: pointer;
                background: none;
                border: none;
            }

            .mobile-bottom-nav .bnav-item i {
                font-size: 1.2rem;
                margin-bottom: 2px;
                transition: transform 0.2s ease;
            }

            .mobile-bottom-nav .bnav-item.active,
            .mobile-bottom-nav .bnav-item:active {
                color: var(--primary-color);
            }

            .mobile-bottom-nav .bnav-item.active i {
                transform: scale(1.1);
            }

            .mobile-bottom-nav .bnav-item.active::after {
                content: '';
                position: absolute;
                top: -4px;
                left: 50%;
                transform: translateX(-50%);
                width: 20px;
                height: 3px;
                border-radius: 3px;
                background: var(--primary-color);
            }

            /* --- Slide-up menu panel --- */
            .mobile-menu-panel {
                display: block;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 1051;
                background-color: var(--sidebar-bg);
                border-top-left-radius: 20px;
                border-top-right-radius: 20px;
                box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.15);
                transform: translateY(100%);
                transition: transform 0.35s cubic-bezier(0.32, 0.72, 0, 1);
                max-height: 75vh;
                overflow-y: auto;
                overscroll-behavior: contain;
            }

            .mobile-menu-panel.show {
                transform: translateY(0);
            }

            .mobile-menu-panel .panel-handle {
                display: flex;
                justify-content: center;
                padding: 0.75rem 0 0.25rem;
                cursor: pointer;
            }

            .mobile-menu-panel .panel-handle::after {
                content: '';
                width: 40px;
                height: 4px;
                border-radius: 4px;
                background: var(--border-color);
            }

            .mobile-menu-panel .panel-body {
                padding: 0.5rem 1rem 1.5rem;
                padding-bottom: calc(1.5rem + env(safe-area-inset-bottom, 0px));
            }

            .mobile-menu-panel .panel-section-label {
                font-size: 0.7rem;
                text-transform: uppercase;
                font-weight: 700;
                color: var(--text-light);
                letter-spacing: 0.05em;
                padding: 0.75rem 0.5rem 0.35rem;
                margin-top: 0.25rem;
            }

            .mobile-menu-panel .panel-nav-link {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.7rem 0.75rem;
                border-radius: 10px;
                text-decoration: none;
                color: var(--text-dark);
                font-size: 0.88rem;
                font-weight: 500;
                transition: all 0.15s ease;
            }

            .mobile-menu-panel .panel-nav-link:active {
                background-color: var(--primary-light);
                transform: scale(0.98);
            }

            .mobile-menu-panel .panel-nav-link.active {
                background-color: var(--primary-color);
                color: #fff;
            }

            .mobile-menu-panel .panel-nav-link i {
                width: 24px;
                text-align: center;
                font-size: 0.95rem;
            }

            .mobile-menu-overlay {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 1050;
                background: rgba(0, 0, 0, 0.35);
                backdrop-filter: blur(2px);
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }

            .mobile-menu-overlay.show {
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

                // $('#greeting-text').text(greetingText);
                // $('#greeting-icon').removeClass().addClass(iconClass);

                $('#welcomeModalGreeting').text(greetingText + "!");
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
                        // No-op: bottom nav handles mobile navigation now
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

            // ============================================================
            // MOBILE BOTTOM NAV — slide-up panels
            // ============================================================
            (function() {
                const overlay = document.getElementById('mobileMenuOverlay');
                const panels = {
                    koleksi: document.getElementById('panelKoleksi'),
                    analitik: document.getElementById('panelAnalitik'),
                    more: document.getElementById('panelMore'),
                };
                let activePanel = null;

                function closeAllPanels() {
                    Object.values(panels).forEach(function(p) {
                        if (p) p.classList.remove('show');
                    });
                    if (overlay) overlay.classList.remove('show');
                    activePanel = null;
                }

                // Bottom nav buttons
                document.querySelectorAll('.mobile-bottom-nav .bnav-item[data-panel]').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var panelName = btn.getAttribute('data-panel');
                        var panel = panels[panelName];
                        if (!panel) return;

                        if (activePanel === panelName) {
                            closeAllPanels();
                        } else {
                            closeAllPanels();
                            panel.classList.add('show');
                            if (overlay) overlay.classList.add('show');
                            activePanel = panelName;
                        }
                    });
                });

                // Close on overlay tap
                if (overlay) {
                    overlay.addEventListener('click', closeAllPanels);
                }

                // Close on panel handle tap
                document.querySelectorAll('[data-panel-close]').forEach(function(handle) {
                    handle.addEventListener('click', closeAllPanels);
                });

                // Swipe-down to close
                Object.values(panels).forEach(function(panel) {
                    if (!panel) return;
                    var startY = 0;
                    panel.addEventListener('touchstart', function(e) {
                        startY = e.touches[0].clientY;
                    }, { passive: true });
                    panel.addEventListener('touchmove', function(e) {
                        var diff = e.touches[0].clientY - startY;
                        if (diff > 60) {
                            closeAllPanels();
                        }
                    }, { passive: true });
                });
            })();

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


        // ============================================================
        // SESSION TIMEOUT LOGIC (Frontend Guard)
        // ============================================================
        @auth
        (function () {
            // Timeout dari config server (menit) → dikonversi ke detik
            const IDLE_TIMEOUT_SECONDS = {{ config('session.idle_timeout', 30) }} * 60;
            // Tampilkan warning 2 menit sebelum logout
            const WARNING_BEFORE_SECONDS = 120;

            let idleTimer = null;
            let countdownTimer = null;
            let warningModal = null;
            let countdownSeconds = WARNING_BEFORE_SECONDS;

            // Inisialisasi modal Bootstrap
            const modalEl = document.getElementById('sessionTimeoutModal');
            if (modalEl) {
                warningModal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
            }

            function formatTime(seconds) {
                const m = String(Math.floor(seconds / 60)).padStart(2, '0');
                const s = String(seconds % 60).padStart(2, '0');
                return `${m}:${s}`;
            }

            function startCountdown() {
                countdownSeconds = WARNING_BEFORE_SECONDS;
                const display = document.getElementById('sessionCountdownDisplay');
                if (display) display.textContent = formatTime(countdownSeconds);

                countdownTimer = setInterval(function () {
                    countdownSeconds--;
                    if (display) display.textContent = formatTime(countdownSeconds);

                    if (countdownSeconds <= 0) {
                        clearInterval(countdownTimer);
                        // Paksa logout via form submit
                        const logoutForm = modalEl ? modalEl.querySelector('form') : null;
                        if (logoutForm) {
                            logoutForm.submit();
                        } else {
                            window.location.href = '{{ route("cas.login") }}';
                        }
                    }
                }, 1000);
            }

            function showWarning() {
                if (warningModal) {
                    warningModal.show();
                    startCountdown();
                }
            }

            function hideWarning() {
                if (warningModal) {
                    warningModal.hide();
                }
                clearInterval(countdownTimer);
            }

            function resetIdleTimer() {
                clearTimeout(idleTimer);
                // Tampilkan warning saat mendekati batas idle
                const warnAfter = (IDLE_TIMEOUT_SECONDS - WARNING_BEFORE_SECONDS) * 1000;
                if (warnAfter > 0) {
                    idleTimer = setTimeout(showWarning, warnAfter);
                }
            }

            // Tombol "Lanjutkan Sesi" → ping server untuk refresh last_activity
            const btnExtend = document.getElementById('btnExtendSession');
            if (btnExtend) {
                btnExtend.addEventListener('click', function () {
                    hideWarning();
                    // Kirim request ringan ke server untuk refresh sesi
                    fetch('{{ url("/dashboard") }}', {
                        method: 'HEAD',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        credentials: 'same-origin'
                    }).then(function(res) {
                        if (res.status === 401 || res.status === 419) {
                            window.location.href = '{{ route("cas.login") }}';
                        }
                    }).catch(function() {
                        // Abaikan error jaringan
                    });
                    resetIdleTimer();
                });
            }

            // Pantau aktivitas user
            const activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];
            activityEvents.forEach(function (event) {
                document.addEventListener(event, function () {
                    // Sembunyikan modal jika muncul dan user aktif kembali
                    if (warningModal && modalEl && modalEl.classList.contains('show')) {
                        // Jangan reset saat modal terbuka – biarkan user klik tombol
                        return;
                    }
                    resetIdleTimer();
                }, { passive: true });
            });

            // Handle jika server mengembalikan 401 (session expired di sisi server)
            const originalFetch = window.fetch;
            window.fetch = function (...args) {
                return originalFetch.apply(this, args).then(function (response) {
                    if (response.status === 401) {
                        const cloned = response.clone();
                        cloned.json().then(function (data) {
                            if (data && data.session_expired) {
                                window.location.href = '{{ route("cas.login") }}';
                            }
                        }).catch(function() {});
                    }
                    return response;
                });
            };

            // Mulai timer saat halaman dimuat
            resetIdleTimer();
        })();
        @endauth

        // ============================================================
        // GLOBAL PAGE LOADER (NProgress + Overlay)
        // ============================================================
        (function () {
            // Konfigurasi NProgress
            NProgress.configure({
                showSpinner: false,
                trickleSpeed: 120,
                minimum: 0.12,
                easing: 'ease',
                speed: 400,
            });

            const loader = document.getElementById('page-loader');

            function showLoader() {
                NProgress.start();
                if (loader) loader.classList.add('show');
            }

            function hideLoader() {
                NProgress.done();
                if (loader) loader.classList.remove('show');
            }

            // ── Intercept semua klik link internal ──
            document.addEventListener('click', function (e) {
                const anchor = e.target.closest('a');
                if (!anchor) return;

                const href = anchor.getAttribute('href');
                if (!href) return;

                // Skip: external, hash, javascript:, data-no-loader
                const isExternal  = anchor.hostname && anchor.hostname !== window.location.hostname;
                const isHash      = href.startsWith('#');
                const isJs        = href.startsWith('javascript');
                const isSkip      = anchor.hasAttribute('data-no-loader');
                const isBlank     = anchor.target === '_blank';
                const isDownload  = anchor.hasAttribute('download');

                if (isExternal || isHash || isJs || isSkip || isBlank || isDownload) return;

                // Overlay hanya untuk link yang bukan navigasi sidebar ringan
                // Gunakan atribut data-heavy="true" untuk trigger overlay
                if (anchor.hasAttribute('data-heavy')) {
                    showLoader();
                } else {
                    NProgress.start();
                }
            }, true);

            // ── Intercept submit form ──
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!form || form.hasAttribute('data-no-loader')) return;
                // Form dengan data-heavy → overlay, lainnya hanya NProgress
                if (form.hasAttribute('data-heavy')) {
                    showLoader();
                } else {
                    NProgress.start();
                }
            }, true);

            // ── Selesai saat halaman terbuka penuh ──
            window.addEventListener('pageshow', function (e) {
                hideLoader();
            });

            // ── Jika kembali dari history (back button) ──
            window.addEventListener('popstate', function () {
                hideLoader();
            });

            // ── Fallback: hide loader setelah 10 detik (safety net) ──
            let safetyTimer = null;
            document.addEventListener('click', function (e) {
                const anchor = e.target.closest('a[href]');
                if (!anchor) return;
                clearTimeout(safetyTimer);
                safetyTimer = setTimeout(hideLoader, 10000);
            }, true);

        })();

        });
    </script>

</body>

</html>
