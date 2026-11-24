<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') - @yield('title') | Library Data</title>
    <link rel="icon" href="{{ asset('img/logo4.png') }}?v={{ filemtime(public_path('img/logo4.png')) }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}?v={{ filemtime(public_path('img/logo4.png')) }}" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous">

    <link href="{{ asset('vendor/bootstrap/5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/unified-components.css') }}?v={{ filemtime(public_path('css/unified-components.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}?v={{ filemtime(public_path('css/app-layout.css')) }}">

    <style>
        .err-shell {
            min-height: 100vh;
            background-color: var(--main-bg);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
        }
        .err-header {
            background-color: var(--sidebar-bg);
            border-bottom: 1px solid var(--border-color);
        }
        .err-brand {
            color: var(--text-dark);
            text-decoration: none;
        }
        .err-brand:hover {
            color: var(--primary-color);
        }
        .err-card {
            max-width: 560px;
            width: 100%;
            background-color: var(--sidebar-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px !important;
            box-shadow: var(--card-shadow);
        }
        .err-code-num {
            font-size: clamp(3rem, 7vw, 4.25rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            font-variant-numeric: tabular-nums;
            color: var(--err-accent, var(--primary-color));
        }
        .err-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--err-soft, var(--primary-light));
            color: var(--err-accent, var(--primary-color));
        }
        body.dark-mode .err-shell,
        body.dark-mode.err-shell {
            --err-accent: var(--err-accent-dark, #93c5fd);
            --err-soft: var(--err-soft-dark, rgba(147, 197, 253, 0.16));
        }
        .err-desc {
            color: var(--text-light);
            font-size: var(--font-base);
            line-height: 1.6;
        }
        .err-btn {
            min-height: 44px;
            border-radius: 10px;
            font-weight: 600;
            padding: 0.625rem 1.25rem;
            cursor: pointer;
        }
        .err-btn-secondary {
            border: 1px solid var(--border-color);
            color: var(--text-dark);
            background-color: transparent;
        }
        .err-btn-secondary:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .err-theme-btn {
            min-width: 44px;
            min-height: 44px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background-color: transparent;
            color: var(--text-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .err-theme-btn:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        .err-btn:focus-visible,
        .err-theme-btn:focus-visible,
        .err-brand:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
        .err-debug {
            background-color: var(--main-bg);
            color: var(--text-dark);
            border: 1px dashed var(--border-color);
            font-size: var(--font-xs);
            word-break: break-word;
        }
        .err-footer {
            color: var(--text-light);
            font-size: var(--font-xs);
            border-top: 1px solid var(--border-color);
            background-color: var(--sidebar-bg);
        }
        body:not(.dark-mode) .icon-sun { display: none; }
        body.dark-mode .icon-moon { display: none; }
    </style>
</head>
<body class="err-shell {{ ($_COOKIE['theme'] ?? '') === 'dark' ? 'dark-mode' : '' }}" style="--err-accent: @yield('accent', '#3249b3'); --err-soft: @yield('soft', 'rgba(74, 105, 255, 0.12)'); --err-accent-dark: @yield('accent_dark', '#93c5fd'); --err-soft-dark: @yield('soft_dark', 'rgba(147, 197, 253, 0.16)');">

    @php
        try {
            $isLoggedIn = auth()->check();
        } catch (\Throwable $e) {
            $isLoggedIn = false;
        }
        $homeUrl = $isLoggedIn ? route('dashboard') : route('home');
        $homeLabel = $isLoggedIn ? 'Kembali ke Dashboard' : 'Kembali ke Beranda';
    @endphp

    <header class="err-header py-3 px-3 px-md-4">
        <div class="container d-flex align-items-center justify-content-between" style="max-width: 960px;">
            <a href="{{ $homeUrl }}" class="err-brand d-inline-flex align-items-center gap-2">
                <img src="{{ asset('img/logo4.png') }}" alt="Logo UMS" width="36" height="36" style="object-fit: contain;">
                <div>
                    <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;">Library Data</div>
                    <div style="font-size: var(--font-xs); color: var(--text-light);">UPT Perpustakaan dan Layanan Digital UMS</div>
                </div>
            </a>
            <button type="button" id="errThemeToggle" class="err-theme-btn" aria-label="Ganti tema tampilan" title="Ganti tema">
                <span class="icon-moon"><x-icon name="moon" /></span>
                <span class="icon-sun"><x-icon name="sun" /></span>
            </button>
        </div>
    </header>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center p-3 p-md-4">
        <div class="card no-hover err-card p-4 p-md-5 text-center">
            <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                <div class="err-icon-wrap">
                    <x-icon :name="trim($__env->yieldContent('icon', 'info-circle'))" size="32" />
                </div>
                <div class="err-code-num">@yield('code')</div>
            </div>

            <h1 class="h3 fw-bold mb-2 mt-1" style="color: var(--text-dark);">@yield('title')</h1>
            <p class="err-desc mb-4">@yield('message')</p>

            @yield('extra')

            <div class="d-flex flex-wrap gap-2 justify-content-center">
                @yield('actions')
                <a href="{{ $homeUrl }}" class="btn @hasSection('actions') err-btn-secondary @else btn-primary @endif err-btn d-inline-flex align-items-center gap-2">
                    <x-icon name="house" />
                    <span>{{ $homeLabel }}</span>
                </a>
                @yield('secondary_action')
            </div>
        </div>
    </main>

    <footer class="err-footer py-3 px-3 text-center">
        &copy; {{ date('Y') }} UPT Perpustakaan dan Layanan Digital UMS
    </footer>

    <script>
        document.getElementById('errThemeToggle')?.addEventListener('click', function () {
            const isDark = document.body.classList.toggle('dark-mode');
            document.cookie = 'theme=' + (isDark ? 'dark' : 'light') + '; path=/; max-age=31536000; SameSite=Lax';
        });
    </script>
</body>
</html>
