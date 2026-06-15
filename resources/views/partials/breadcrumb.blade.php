@php
/**
 * Breadcrumb Auto-Generator
 * Membaca route name saat ini dan membangun breadcrumb secara otomatis.
 * Tambahkan entri baru di $breadcrumbMap sesuai route baru yang dibuat.
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
];

$crumbs = $breadcrumbMap[$currentRoute] ?? null;
$showBreadcrumb = $crumbs !== null && $currentRoute !== 'dashboard';
@endphp

@if($showBreadcrumb)
<link rel="stylesheet" href="{{ asset('css/breadcrumb.css') }}">

<nav aria-label="breadcrumb" class="breadcrumb-nav px-3 px-md-4 py-2">
    <ol class="breadcrumb mb-0 align-items-center">
        {{-- Home always first --}}
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard') }}" class="breadcrumb-home" title="Beranda / Dashboard">
                <i class="fas fa-house-chimney" style="font-size: 0.7rem;"></i>
                <span class="d-none d-sm-inline">Beranda</span>
            </a>
        </li>

        {{-- Dynamic crumbs --}}
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
@endif

