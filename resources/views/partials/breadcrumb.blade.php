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
<style>
    .breadcrumb-nav {
        background: linear-gradient(135deg, rgba(var(--bs-primary-rgb, 74, 105, 255), 0.04) 0%, transparent 100%);
        border-bottom: 1px solid var(--border-color, #e5e7eb);
        transition: background 0.3s ease, border-color 0.3s ease;
    }

    .breadcrumb {
        --bs-breadcrumb-divider: '';
        gap: 0;
        flex-wrap: nowrap;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .breadcrumb::-webkit-scrollbar { display: none; }

    .breadcrumb-item {
        display: flex;
        align-items: center;
        font-size: 0.78rem;
        font-weight: 500;
        white-space: nowrap;
    }

    /* Separator chevron */
    .breadcrumb-item + .breadcrumb-item::before {
        content: "";
        display: inline-block;
        width: 14px;
        height: 14px;
        margin: 0 4px;
        background-color: currentColor;
        opacity: 0.35;
        flex-shrink: 0;
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 18 15 12 9 6'/%3E%3C/svg%3E");
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 18 15 12 9 6'/%3E%3C/svg%3E");
        -webkit-mask-size: contain;
        mask-size: contain;
        -webkit-mask-repeat: no-repeat;
        mask-repeat: no-repeat;
        -webkit-mask-position: center;
        mask-position: center;
    }

    .breadcrumb-item a,
    .breadcrumb-home {
        color: var(--text-light, #64748b);
        text-decoration: none;
        transition: color 0.2s ease;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .breadcrumb-item a:hover,
    .breadcrumb-home:hover {
        color: var(--primary-color, #4a69ff);
    }

    .breadcrumb-group {
        color: var(--text-light, #64748b);
    }

    .breadcrumb-item.active {
        color: var(--primary-color, #4a69ff);
        font-weight: 600;
    }

    /* Dark mode */
    body.dark-mode .breadcrumb-nav {
        background: linear-gradient(135deg, rgba(74, 105, 255, 0.06) 0%, transparent 100%);
        border-bottom-color: var(--border-color, #334155);
    }

    body.dark-mode .breadcrumb-item a,
    body.dark-mode .breadcrumb-home,
    body.dark-mode .breadcrumb-group {
        color: #94a3b8;
    }

    body.dark-mode .breadcrumb-item a:hover,
    body.dark-mode .breadcrumb-home:hover {
        color: #a5b4fc;
    }

    body.dark-mode .breadcrumb-item.active {
        color: #a5b4fc;
    }
</style>

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

