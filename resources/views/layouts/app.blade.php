<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Akreditasi Perpustakaan</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/x-icon">

    {{-- CSS LINKS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @stack('styles')

    <style>
        /* CSS Custom Properties for easy theme changes */
        :root {
            --sidebar-bg: #1e283b;
            --sidebar-color: #f1f3f5;
            --main-bg: #f5f7fa;
            --accent-color: #5d87ff;
            --link-hover-bg: rgba(255, 255, 255, 0.08);
            --link-active-bg: rgba(255, 255, 255, 0.15);
            --box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--main-bg);
            color: #495057;
            overflow-x: hidden;
            font-size: 1rem;
        }

        /* Sidebar Styling - Lebih halus dan modern */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-color);
            transition: all 0.3s ease-in-out;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1050;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            overflow-y: auto;
        }

        .sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(30, 40, 59, 0.4);
            z-index: 1049;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s;
        }

        .sidebar-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        .sidebar-header {
            min-height: 120px;
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link {
            color: var(--sidebar-color);
            font-size: 0.95rem;
            padding: 12px 20px;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link:hover {
            background-color: var(--link-hover-bg);
            color: #ffffff;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background-color: var(--link-active-bg);
            color: #ffffff;
            font-weight: 600;
            position: relative;
        }

        /* Aksen di sisi kiri menu aktif */
        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 70%;
            width: 4px;
            background-color: var(--accent-color);
            border-radius: 0 5px 5px 0;
        }

        .sidebar .nav-link i {
            font-size: 1.1rem;
            min-width: 30px;
        }

        .sidebar .nav-link .nav-arrow {
            color: rgba(255, 255, 255, 0.5);
            transition: transform 0.2s ease-in-out;
        }

        .sidebar .nav-link[aria-expanded="true"] .nav-arrow {
            transform: rotate(180deg);
        }

        .sidebar .collapse .nav-link {
            padding-left: 50px;
            font-size: 0.875rem;
            color: #b2b2b2;
            transition: transform 0.2s ease-in-out;
        }

        .sidebar .collapse .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--sidebar-color);
            transform: translateX(3px);
        }

        .sidebar .collapse .nav-link.active {
            font-weight: 500;
            color: #ffffff;
        }

        /* Main Content Area */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
            padding-left: 260px;
            transition: padding-left 0.3s ease-in-out;
        }

        .content-area {
            flex-grow: 1;
            padding: 30px;
            background-color: var(--main-bg);
        }

        /* Header utama */
        .page-header {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }

        /* Navbar untuk mobile */
        .navbar-top {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 10px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Footer */
        .app-footer {
            background-color: #ffffff;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: auto;
            border-radius: 12px 12px 0 0;
        }

        /* MEDIA QUERIES FOR RESPONSIVENESS */

        /* Laptop/Tablet (width < 992px) */
        @media (max-width: 991.98px) {
            .sidebar {
                left: -260px;
                box-shadow: none;
                width: 80vw;
                max-width: 260px;
            }

            .sidebar.show {
                left: 0;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            }

            .main-wrapper {
                padding-left: 0 !important;
                flex-direction: column;
                /* Stacks the content and footer */
            }

            .content-area {
                padding: 15px;
                /* Reduced padding for smaller screens */
            }

            .page-header {
                padding: 15px 10px;
                margin-bottom: 15px;
                font-size: 1rem;
            }

            .app-footer {
                padding: 10px;
                font-size: 0.85rem;
            }

            .navbar-top {
                padding: 8px 10px;
            }

            h5,
            .fs-5 {
                font-size: 1.1rem !important;
            }

            /* Hiding the date/time on smaller tablets for cleaner look */
            .page-header .text-end {
                display: none !important;
            }

            /* Centering the greeting on smaller screens */
            #greeting-header {
                flex-grow: 1;
                text-align: center;
            }
        }

        /* Mobile (width < 576px) */
        @media (max-width: 575.98px) {
            .sidebar {
                width: 95vw;
                max-width: 260px;
            }

            .content-area {
                padding: 8px;
                /* Even smaller padding for mobile */
            }

            .page-header {
                padding: 8px 5px;
                font-size: 0.95rem;
                flex-direction: column;
                /* Stack header elements */
                align-items: flex-start;
                gap: 10px;
                /* Space between greeting and logout button */
            }

            .page-header .btn {
                width: 100%;
                /* Make logout button full width */
            }

            h5,
            .fs-5 {
                font-size: 1rem !important;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        {{-- Sidebar --}}
        <div id="sidebar" class="sidebar">
            @include('partials.sidebar')
        </div>

        {{-- Main Content Area --}}
        <div class="content-area d-flex flex-column">
            {{-- Top Navbar (untuk mobile toggle) --}}
            <nav class="navbar-top d-lg-none sticky-top mt-2">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="navbar-brand-mobile">
                        <img src="{{ asset('img/sidebar.png') }}" alt="Logo" style="height: 35px;">
                    </div>

                    <button class="navbar-toggler-custom" type="button" id="toggleSidebarBtn"
                        aria-label="Toggle navigation">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </nav>

            {{-- Header Utama --}}
            <header class="d-flex justify-content-between align-items-center page-header mt-2">
                <div class="d-flex align-items-center">
                    {{-- Greeting dan logo --}}
                    <img src="{{ asset('img/logo0.png') }}" alt="Logo" style="height: 40px; margin-right: 10px;">
                    {{-- <h5 class="m-0 fs-5 fw-medium text-primary" id="greeting-header">
                        <span class="me-2" id="greeting-icon"></span>
                        <span id="greeting-text"></span>
                    </h5> --}}
                </div>
                <div class="d-flex align-items-center">
                    {{-- Tanggal dan waktu --}}
                    <div class="text-end me-3 d-none d-md-block">
                        <div id="current-date" class="small text-muted"></div>
                        <div id="current-time" class="fw-bold fs-4 text-dark"></div>
                    </div>
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </button>
                        </form>
                    @endauth
                </div>
            </header>

            <main class="flex-grow-1">
                <div class="container">
                    @yield('content')
                </div>
            </main>

            @include('partials.footer')
        </div>
    </div>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    {{-- JS LINKS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>

    @stack('scripts')
    <script>
        $(document).ready(function() {
            function updateGreeting() {
                const now = new Date();
                const hour = now.getHours();
                let greetingText = "";
                let iconClass = "";

                if (hour >= 5 && hour < 12) {
                    greetingText = "Selamat Pagi";
                    iconClass = "fas fa-sun text-warning";
                } else if (hour >= 12 && hour < 15) {
                    greetingText = "Selamat Siang";
                    iconClass = "fas fa-cloud-sun text-warning";
                } else if (hour >= 15 && hour < 19) {
                    greetingText = "Selamat Sore";
                    iconClass = "fas fa-cloud-sun text-warning";
                } else {
                    greetingText = "Selamat Malam";
                    iconClass = "fas fa-moon text-primary";
                }

                $('#greeting-text').text(greetingText);
                $('#greeting-icon').removeClass().addClass(iconClass);
            }

            updateGreeting();
            //setInterval(updateGreeting, 60000);
        });

        $(document).ready(function() {
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
                const formattedTime = new Intl.DateTimeFormat('id-ID', timeOptions).format(now);

                $('#current-date').text(formattedDate);
                $('#current-time').text(formattedTime);
            }

            updateTime();
            setInterval(updateTime, 1000);

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Script untuk sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebarBtn');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const mainWrapper = document.querySelector('.main-wrapper');

            function openSidebar() {
                sidebar.classList.add('show');
                sidebarBackdrop.classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('show');
                sidebarBackdrop.classList.remove('show');
                document.body.style.overflow = '';
            }

            function toggleSidebar() {
                if (sidebar.classList.contains('show')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleSidebar);
            }
            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', closeSidebar);
            }

            function handleResize() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                    mainWrapper.style.paddingLeft = '0';
                    document.body.style.overflow = '';
                } else {
                    sidebar.classList.add('show');
                    sidebarBackdrop.classList.remove('show');
                    mainWrapper.style.paddingLeft = '260px';
                    document.body.style.overflow = '';
                }
            }
            handleResize();
            window.addEventListener('resize', handleResize);

            // Handle collapse arrows for nested menus
            document.querySelectorAll('.sidebar .nav-link[data-bs-toggle="collapse"]').forEach(function(element) {
                element.addEventListener('click', function() {
                    const targetId = this.getAttribute('href');
                    const targetCollapse = document.querySelector(targetId);
                    const navArrow = this.querySelector('.nav-arrow, .nav-arrow-small');
                    if (targetCollapse && targetCollapse.classList.contains('show')) {
                        navArrow.style.transform = 'rotate(0deg)';
                    } else {
                        navArrow.style.transform = 'rotate(180deg)';
                    }
                });
            });

            document.querySelectorAll('.sidebar .collapse.show').forEach(function(collapseElement) {
                const parentLink = document.querySelector(
                    `[data-bs-toggle="collapse"][href="#${collapseElement.id}"]`);
                if (parentLink) {
                    parentLink.querySelector('.nav-arrow, .nav-arrow-small').style.transform =
                        'rotate(180deg)';
                }
            });
        });
    </script>
</body>

</html>
