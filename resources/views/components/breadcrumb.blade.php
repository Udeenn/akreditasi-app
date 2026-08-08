@props(['title' => '', 'icon' => 'fas fa-chart-pie'])

@php
/**
 * Breadcrumb Component
 * Membaca route name saat ini dan membangun breadcrumb secara otomatis.
 */

$currentRoute = Route::currentRouteName() ?? '';

// ── Route → Breadcrumb map ────────────────────────────────────────────
// Format: 'route.name' => [['label', 'route.name' atau null jika current]]
$breadcrumbMap = [
    // Dashboard
    'dashboard' => [
        ['Beranda', null],
    ],

    // ── Koleksi ──────────────────────────────────────────────────────
    'koleksi.rekap_fakultas' => [
        ['Koleksi', null],
        ['Per Fakultas', null],
    ],
    'koleksi.textbook' => [
        ['Koleksi', null],
        ['Text Book', null],
    ],
    'koleksi.ebook' => [
        ['Koleksi', null],
        ['E-Book', null],
    ],
    'koleksi.jurnal' => [
        ['Koleksi', null],
        ['Journal', null],
    ],
    'koleksi.ejurnal' => [
        ['Koleksi', null],
        ['E-Journal', null],
    ],
    'koleksi.prosiding' => [
        ['Koleksi', null],
        ['Prosiding', null],
    ],
    'koleksi.referensi' => [
        ['Koleksi', null],
        ['Referensi', null],
    ],
    'koleksi.tren_pertambahan' => [
        ['Koleksi', null],
        ['Tren Pertambahan', null],
    ],
    'koleksi.eresource' => [
        ['Koleksi', null],
        ['E-Resource', null],
    ],
    'koleksi.periodikal' => [
        ['Koleksi', null],
        ['Periodikal', null],
    ],
    'koleksi.prodi' => [
        ['Koleksi', null],
        ['Per Program Studi', null],
    ],

    // ── Kunjungan ────────────────────────────────────────────────────
    'kunjungan.tanggalTable' => [
        ['Analitik', null],
        ['Kunjungan', null],
        ['Harian', null],
    ],
    'kunjungan.fakultasTable' => [
        ['Analitik', null],
        ['Kunjungan', null],
        ['Per Fakultas', null],
    ],
    'kunjungan.keseluruhan' => [
        ['Analitik', null],
        ['Kunjungan', null],
        ['Keseluruhan', null],
    ],
    'kunjungan.prodi' => [
        ['Analitik', null],
        ['Kunjungan', null],
        ['Civitas Akademika', null],
    ],
    'kunjungan.cekKehadiran' => [
        ['Analitik', null],
        ['Kunjungan', null],
        ['Cek Kunjungan', null],
    ],

    // ── Peminjaman ───────────────────────────────────────────────────
    'peminjaman.peminjaman_fakultas' => [
        ['Analitik', null],
        ['Peminjaman', null],
        ['Per Fakultas', null],
    ],
    'peminjaman.keseluruhan' => [
        ['Analitik', null],
        ['Peminjaman', null],
        ['Keseluruhan', null],
    ],
    'peminjaman.prodi' => [
        ['Analitik', null],
        ['Peminjaman', null],
        ['Civitas Akademika', null],
    ],
    'peminjaman.cek_pinjaman' => [
        ['Analitik', null],
        ['Peminjaman', null],
        ['Cek Pinjaman', null],
    ],
    'peminjaman.berlangsung' => [
        ['Analitik', null],
        ['Peminjaman', null],
        ['Sedang Berlangsung', null],
    ],
    'peminjaman.buku_terlaris_prodi' => [
        ['Analitik', null],
        ['Peminjaman', null],
        ['Buku Terlaris per Prodi', null],
    ],

    // ── Statistik Sirkulasi ──────────────────────────────────────────
    'penggunaan.keterpakaian_koleksi' => [
        ['Statistik Sirkulasi', null],
        ['Keterpakaian Koleksi', null],
    ],
    'penggunaan.cek_histori_buku_buku' => [
        ['Statistik Sirkulasi', null],
        ['Cek Histori Buku', null],
    ],
    'penggunaan.sering_dibaca' => [
        ['Statistik Sirkulasi', null],
        ['Buku Terlaris', null],
    ],

    // ── Reward ───────────────────────────────────────────────────────
    'reward.pemustaka_teraktif' => [
        ['Reward', null],
        ['Pemustaka Teraktif', null],
    ],
    'reward.peminjam_teraktif' => [
        ['Reward', null],
        ['Peminjam Teraktif', null],
    ],

    // ── Credit ────────────────────────────────────────────────────────
    'credit.index' => [
        ['Tentang Aplikasi', null],
    ],

    // ── Admin ─────────────────────────────────────────────────────────
    'admin.activity-log' => [
        ['Admin', null],
        ['Audit Trail', null],
    ],
    'cnclass.index' => [
        ['Admin', null],
        ['Pengaturan CN Class', null],
    ],
];

$crumbs = $breadcrumbMap[$currentRoute] ?? null;
$showBreadcrumb = $crumbs !== null && $currentRoute !== 'dashboard';
@endphp

@if($showBreadcrumb)
<link rel="stylesheet" href="{{ asset('css/breadcrumb.css?v=' . time()) }}">


@php
    $heroTitle = $title ?? '';
    if(empty($heroTitle) && !empty($crumbs)) {
        $lastCrumb = end($crumbs);
        $heroTitle = $lastCrumb[0];
        reset($crumbs);
    }
@endphp

<div class="page-hero">
    <div class="d-flex align-items-md-center justify-content-between flex-column flex-md-row gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="stat-icon" style="background: rgba(74,105,255,0.15); color: #4A69FF;">
                <i class="{{ $icon }}"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--text-dark);">{{ $heroTitle }}</h4>
                @if(isset($subtitle) && !empty((string) $subtitle))
                    <p class="mb-0 small" style="color: var(--text-light); margin-top: 0.2rem;">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        
        <div class="d-flex justify-content-md-end">
            <nav aria-label="breadcrumb" class="breadcrumb-nav py-0 px-0" style="background: transparent;">
                <ol class="breadcrumb mb-0 align-items-center">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="breadcrumb-home" title="Beranda / Dashboard">
                            <i class="fas fa-house-chimney" style="font-size: 0.75rem;"></i>
                        </a>
                    </li>

                    @foreach($crumbs as $i => $crumb)
                        @php [$label, $routeName] = $crumb; @endphp
                        @if($loop->last)
                            <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                        @else
                            <li class="breadcrumb-item">
                                @if($routeName && Route::has($routeName))
                                    <a href="{{ route($routeName) }}">{{ $label }}</a>
                                @else
                                    <span class="breadcrumb-group">{{ $label }}</span>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
    </div>
</div>
@endif

