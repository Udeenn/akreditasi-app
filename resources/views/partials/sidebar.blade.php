{{-- resources/views/partials/sidebar.blade.php --}}

<div class="sidebar-header d-flex align-items-center">
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none w-100">
        <i class="fas fa-chart-pie fs-4 me-3 text-primary my-3"></i>
        {{-- <img src="{{ asset('img/sidebar.png') }}" alt="Logo" class="sidebar-logo" style="max-height: 100px;"> --}}
        <h5 class="sidebar-title m-0">Data Pustaka</h5>
    </a>
</div>

<div class="sidebar-menu px-3 d-flex flex-column" id="sidebarMenu">
    <ul class="nav flex-column flex-grow-1 gap-1 mb-3">
        <li class="nav-label small text-muted text-uppercase mt-2 mb-2">Utama</li>
        {{-- Dashboard --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>

        {{-- Daftar Koleksi --}}
        <li class="nav-label small text-muted text-uppercase mt-3 mb-2">Koleksi</li>
        @php $isDaftarKoleksiActive = request()->routeIs(['koleksi.*']); @endphp
        <li class="nav-item">
            <a class="nav-link {{ $isDaftarKoleksiActive ? 'active' : '' }}" data-bs-toggle="collapse"
                href="#daftarKoleksiCollapse">
                <i class="fas fa-book nav-icon"></i>
                <span class="nav-text">Daftar Koleksi</span>
                <i class="fas fa-chevron-down ms-auto nav-arrow-small"></i>
            </a>
            <div class="collapse {{ $isDaftarKoleksiActive ? 'show' : '' }}" id="daftarKoleksiCollapse">
                <ul class="nav flex-column mt-1 sub-menu">
                    {{-- Semua link koleksi di sini... --}}
                    <li><a class="nav-link {{ request()->routeIs('koleksi.jurnal') ? 'active' : '' }}"
                            href="{{ route('koleksi.jurnal') }}">Journal</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.ebook') ? 'active' : '' }}"
                            href="{{ route('koleksi.ebook') }}">E-Book</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.textbook') ? 'active' : '' }}"
                            href="{{ route('koleksi.textbook') }}">Text Book</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.prosiding') ? 'active' : '' }}"
                            href="{{ route('koleksi.prosiding') }}">Prosiding</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.periodikal') ? 'active' : '' }}"
                            href="{{ route('koleksi.periodikal') }}">Majalah</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.referensi') ? 'active' : '' }}"
                            href="{{ route('koleksi.referensi') }}">Referensi</a></li>
                </ul>
            </div>
        </li>

        {{-- Analitik --}}
        <li class="nav-label small text-muted text-uppercase mt-3 mb-2">Analitik</li>
        {{-- Data Kunjungan --}}
        @php $isKunjunganActive = request()->routeIs(['kunjungan.*']); @endphp
        <li class="nav-item">
            <a class="nav-link {{ $isKunjunganActive ? 'active' : '' }}" data-bs-toggle="collapse"
                href="#kunjunganCollapse">
                <i class="fas fa-users nav-icon"></i>
                <span class="nav-text">Data Kunjungan</span>
                <i class="fas fa-chevron-down ms-auto nav-arrow-small"></i>
            </a>
            <div class="collapse {{ $isKunjunganActive ? 'show' : '' }}" id="kunjunganCollapse">
                <ul class="nav flex-column mt-1 sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('kunjungan.tanggalTable') ? 'active' : '' }}"
                            href="{{ route('kunjungan.tanggalTable') }}">Perpustakaan</a></li>
                    <li><a class="nav-link {{ request()->routeIs('kunjungan.kunjungan_gabungan') ? 'active' : '' }}"
                            href="{{ route('kunjungan.kunjungan_gabungan') }}">Perpustakaan Perbagian</a></li>
                    <li><a class="nav-link {{ request()->routeIs('kunjungan.prodiTable') ? 'active' : '' }}"
                            href="{{ route('kunjungan.prodiTable') }}">Civitas Akademika</a></li>
                    <li><a class="nav-link {{ request()->routeIs('kunjungan.cekKehadiran') ? 'active' : '' }}"
                            href="{{ route('kunjungan.cekKehadiran') }}">Cek Kehadiran</a></li>
                </ul>
            </div>
        </li>

        {{-- Data Peminjaman --}}
        @php $isPeminjamanActive = request()->routeIs(['peminjaman.*']); @endphp
        <li class="nav-item">
            <a class="nav-link {{ $isPeminjamanActive ? 'active' : '' }}" data-bs-toggle="collapse"
                href="#peminjamanCollapse">
                <i class="fas fa-book-reader nav-icon"></i>
                <span class="nav-text">Data Peminjaman</span>
                <i class="fas fa-chevron-down ms-auto nav-arrow-small"></i>
            </a>
            <div class="collapse {{ $isPeminjamanActive ? 'show' : '' }}" id="peminjamanCollapse">
                <ul class="nav flex-column mt-1 sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('peminjaman.peminjaman_rentang_tanggal') ? 'active' : '' }}"
                            href="{{ route('peminjaman.peminjaman_rentang_tanggal') }}">Keseluruhan</a></li>
                    <li><a class="nav-link {{ request()->routeIs('peminjaman.peminjaman_prodi_chart') ? 'active' : '' }}"
                            href="{{ route('peminjaman.peminjaman_prodi_chart') }}">Civitas Akademika</a></li>
                    <li><a class="nav-link {{ request()->routeIs('peminjaman.check_history') ? 'active' : '' }}"
                            href="{{ route('peminjaman.check_history') }}">Cek Peminjaman</a></li>
                    <li><a class="nav-link {{ request()->routeIs('peminjaman.berlangsung') ? 'active' : '' }}"
                            href="{{ route('peminjaman.berlangsung') }}">Sedang Berlangsung</a></li>
                    <li><a class="nav-link {{ request()->routeIs('peminjaman.keterpakaian_koleksi') ? 'active' : '' }}"
                            href="{{ route('peminjaman.keterpakaian_koleksi') }}">Keterpakaian Koleksi</a></li>
                </ul>
            </div>
        </li>

        {{-- Reward --}}
        @php $isRewardActive = request()->routeIs(['reward.*']); @endphp
        <li class="nav-item">
            <a class="nav-link {{ $isRewardActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#rewardCollapse">
                <i class="fas fa-gift nav-icon"></i>
                <span class="nav-text">Reward</span>
                <i class="fas fa-chevron-down ms-auto nav-arrow-small"></i>
            </a>
            <div class="collapse {{ $isRewardActive ? 'show' : '' }}" id="rewardCollapse">
                <ul class="nav flex-column mt-1 sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('reward.pemustaka_teraktif') ? 'active' : '' }}"
                            href="{{ route('reward.pemustaka_teraktif') }}">Pemustaka Teraktif</a></li>
                </ul>
            </div>
        </li>
    </ul>

    <ul class="nav flex-column mt-auto py-3 border-top border-light-subtle">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('credit.index') ? 'active' : '' }}"
                href="{{ route('credit.index') }}">
                <i class="fas fa-info-circle nav-icon"></i>
                <span class="nav-text">Developers</span>
            </a>
        </li>
    </ul>
</div>
