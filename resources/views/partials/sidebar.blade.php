<div class="sidebar-header d-flex align-items-center">
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none w-100">
        <i class="fas fa-chart-pie fs-4 me-3 text-primary "></i>
        {{-- <img src="{{ asset('img/sidebar.png') }}" alt="Logo" class="sidebar-logo" style="max-height: 100px;"> --}}
        <h5 class="sidebar-title m-0">Library Data</h5>
    </a>
</div>

<div class="sidebar-menu px-3 d-flex flex-column" id="sidebarMenu">
    <ul class="nav flex-column flex-grow-1 gap-1 mb-3">
        <li class="nav-label small text-muted text-uppercase mt-2 mb-2">Utama</li>
        {{-- Dashboard --}}
        <li class="nav-item">
            <a class="nav-link {{ Route::currentRouteName() === 'dashboard' ? 'active' : '' }}"
                href="{{ route('dashboard') }}">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-text">Dashboard</span>
            </a>

        </li>

        {{-- SDM --}}
        {{-- <li class="nav-label small text-muted text-uppercase mt-3 mb-2">SDM</li>
        @php $isSdmActive = request()->routeIs(['sdm.*']); @endphp
        <li class="nav-item">
            <a class="nav-link {{ $isSdmActive ? 'active' : '' }}" href="{{ route('sdm.index') }}">
                <i class="fas fa-users nav-icon"></i>
                <span class="nav-text">SDM</span>
            </a>
            <div class="collapse {{ $isSdmActive ? 'show' : '' }}" id="sdmCollapse">
                <ul class="nav flex-column mt-1 sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('sdm.index') ? 'active' : '' }}"
                            href="{{ route('sdm.index') }}">Data SDM</a></li>
                    <li><a class="nav-link {{ request()->routeIs('sdm.kualifikasi_pendidikan') ? 'active' : '' }}"
                            href="{{ route('sdm.kualifikasi_pendidikan') }}">Kualifikasi Pendidikan</a></li>
                    <li><a class="nav-link {{ request()->routeIs('sdm.jabatan_fungsional') ? 'active' : '' }}"
                            href="{{ route('sdm.jabatan_fungsional') }}">Jabatan Fungsional</a></li>
                    <li><a class="nav-link {{ request()->routeIs('sdm.pelatihan') ? 'active' : '' }}"
                            href="{{ route('sdm.pelatihan') }}">Pelatihan</a></li>
                    <li><a class="nav-link {{ request()->routeIs('sdm.kegiatan_ilmiah') ? 'active' : '' }}"
                            href="{{ route('sdm.kegiatan_ilmiah') }}">Kegiatan Ilmiah</a></li>
                    <li><a class="nav-link {{ request()->routeIs('sdm.kegiatan_lainnya') ? 'active' : '' }}"
                            href="{{ route('sdm.kegiatan_lainnya') }}">Kegiatan Lainnya</a></li>
                </ul>
        </li> --}}

        {{-- Daftar Koleksi --}}
        <li class="nav-label small text-muted text-uppercase mt-3 mb-2">Koleksi</li>
        @php $isDaftarKoleksiActive = request()->routeIs(['koleksi.*']); @endphp
        <li class="nav-item">
            <button class="nav-link {{ $isDaftarKoleksiActive ? 'active' : '' }}" type="button"
                data-bs-toggle="collapse" data-bs-target="#daftarKoleksiCollapse"
                aria-expanded="{{ $isDaftarKoleksiActive ? 'true' : 'false' }}" data-bs-auto-close="outside"
                aria-controls="daftarKoleksiCollapse">
                <i class="fas fa-book nav-icon"></i>
                <span class="nav-text">Daftar Koleksi</span>
                <i class="fas fa-chevron-down ms-auto nav-arrow-small"></i>
            </button>
            <div class="collapse {{ $isDaftarKoleksiActive ? 'show' : '' }}" id="daftarKoleksiCollapse">
                <ul class="nav flex-column mt-1 sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('koleksi.rekap_fakultas') ? 'active' : '' }}"
                            href="{{ route('koleksi.rekap_fakultas') }}">Per Fakultas</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.textbook') ? 'active' : '' }}"
                            href="{{ route('koleksi.textbook') }}">Text Book</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.ebook') ? 'active' : '' }}"
                            href="{{ route('koleksi.ebook') }}">E-Book</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.jurnal') ? 'active' : '' }}"
                            href="{{ route('koleksi.jurnal') }}">Journal</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.ejurnal') ? 'active' : '' }}"
                            href="{{ route('koleksi.ejurnal') }}">E-Journal</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.prosiding') ? 'active' : '' }}"
                            href="{{ route('koleksi.prosiding') }}">Prosiding</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.referensi') ? 'active' : '' }}"
                            href="{{ route('koleksi.referensi') }}">Referensi</a></li>
                    <li><a class="nav-link {{ request()->routeIs('koleksi.eresource') ? 'active' : '' }}"
                            href="{{ route('koleksi.eresource') }}">E-Resource</a></li>
        </li>
        {{-- <li><a class="nav-link {{ request()->routeIs('koleksi.periodikal') ? 'active' : '' }}"
                            href="{{ route('koleksi.periodikal') }}">Majalah</a></li> --}}

    </ul>
</div>
</li>

{{-- Analitik --}}
<li class="nav-label small text-muted text-uppercase mt-3 mb-2">Analitik</li>
{{-- Data Kunjungan --}}
@php $isKunjunganActive = request()->routeIs(['kunjungan.*']); @endphp
<li class="nav-item">
    <button class="nav-link {{ $isKunjunganActive ? 'active' : '' }}" type="button" data-bs-toggle="collapse"
        data-bs-target="#kunjunganCollapse" aria-expanded="{{ $isKunjunganActive ? 'true' : 'false' }}"
        data-bs-auto-close="outside" aria-controls="kunjunganCollapse">
        <i class="fas fa-users nav-icon"></i>
        <span class="nav-text">Kunjungan</span>
        <i class="fas fa-chevron-down ms-auto nav-arrow-small"></i>
    </button>
    <div class="collapse {{ $isKunjunganActive ? 'show' : '' }}" id="kunjunganCollapse">
        <ul class="nav flex-column mt-1 sub-menu">
            <li><a class="nav-link {{ request()->routeIs('kunjungan.fakultasTable') ? 'active' : '' }}"
                    href="{{ route('kunjungan.fakultasTable') }}">Per Fakultas</a>
            </li>
            {{-- <li><a class="nav-link {{ request()->routeIs('kunjungan.tanggalTable') ? 'active' : '' }}"
                            href="{{ route('kunjungan.tanggalTable') }}">Perpustakaan</a></li> --}}
            <li><a class="nav-link {{ request()->routeIs('kunjungan.kunjungan_gabungan') ? 'active' : '' }}"
                    href="{{ route('kunjungan.kunjungan_gabungan') }}">Perpustakaan</a></li>
            <li><a class="nav-link {{ request()->routeIs('kunjungan.prodiTable') ? 'active' : '' }}"
                    href="{{ route('kunjungan.prodiTable') }}">Civitas Akademika</a></li>
            <li><a class="nav-link {{ request()->routeIs('kunjungan.cekKehadiran') ? 'active' : '' }}"
                    href="{{ route('kunjungan.cekKehadiran') }}">Cek Kunjungan</a></li>
        </ul>
    </div>
</li>

{{-- Data Peminjaman --}}
@php $isPeminjamanActive = request()->routeIs(['peminjaman.*']); @endphp
<li class="nav-item">
    <button class="nav-link {{ $isPeminjamanActive ? 'active' : '' }}" type="button" data-bs-toggle="collapse"
        data-bs-target="#peminjamanCollapse" aria-expanded="{{ $isPeminjamanActive ? 'true' : 'false' }}"
        data-bs-auto-close="outside" aria-controls="peminjamanCollapse">
        <i class="fas fa-book-reader nav-icon"></i>
        <span class="nav-text">Peminjaman</span>
        <i class="fas fa-chevron-down ms-auto nav-arrow-small"></i>
    </button>
    <div class="collapse {{ $isPeminjamanActive ? 'show' : '' }}" id="peminjamanCollapse">
        <ul class="nav flex-column mt-1 sub-menu">
            <li><a class="nav-link {{ request()->routeIs('peminjaman.peminjaman_rentang_tanggal') ? 'active' : '' }}"
                    href="{{ route('peminjaman.peminjaman_rentang_tanggal') }}">Keseluruhan</a></li>
            <li><a class="nav-link {{ request()->routeIs('peminjaman.peminjaman_prodi_chart') ? 'active' : '' }}"
                    href="{{ route('peminjaman.peminjaman_prodi_chart') }}">Civitas Akademika</a></li>
            <li><a class="nav-link {{ request()->routeIs('peminjaman.check_history') ? 'active' : '' }}"
                    href="{{ route('peminjaman.check_history') }}">Cek Pinjaman</a></li>
            <li><a class="nav-link {{ request()->routeIs('peminjaman.berlangsung') ? 'active' : '' }}"
                    href="{{ route('peminjaman.berlangsung') }}">Sedang Berlangsung</a></li>
        </ul>
    </div>
</li>

{{-- Data Penggunaan --}}
@php $isPenggunaanActive = request()->routeIs(['penggunaan.*']); @endphp
<li class="nav-item">
    <button class="nav-link {{ $isPenggunaanActive ? 'active' : '' }}" type="button" data-bs-toggle="collapse"
        data-bs-target="#penggunaanCollapse" aria-expanded="{{ $isPenggunaanActive ? 'true' : 'false' }}"
        data-bs-auto-close="outside" aria-controls="penggunaanCollapse">
        <i class="fas fa-barcode nav-icon"></i>
        <span class="nav-text">Statistik Sirkulasi</span>
        <i class="fas fa-chevron-down ms-auto nav-arrow-small"></i>
    </button>
    <div class="collapse {{ $isPenggunaanActive ? 'show' : '' }}" id="penggunaanCollapse">
        <ul class="nav flex-column mt-1 sub-menu">
            <li><a class="nav-link {{ request()->routeIs('penggunaan.keterpakaian_koleksi') ? 'active' : '' }}"
                    href="{{ route('penggunaan.keterpakaian_koleksi') }}">Keterpakaian Koleksi</a>
            </li>
            <li><a class="nav-link {{ request()->routeIs('penggunaan.cek_histori') ? 'active' : '' }}"
                    href="{{ route('penggunaan.cek_histori') }}">Cek Histori Buku</a>
            </li>
            <li><a class="nav-link {{ request()->routeIs('penggunaan.sering_dibaca') ? 'active' : '' }}"
                    href="{{ route('penggunaan.sering_dibaca') }}">Buku Terlaris</a>
            </li>
        </ul>
    </div>
</li>

{{-- Reward --}}
@php $isRewardActive = request()->routeIs(['reward.*']); @endphp
{{-- <li class="nav-item">
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
        </li> --}}
<li class="nav-item">
    <a class="nav-link {{ Route::currentRouteName() === 'reward.pemustaka_teraktif' ? 'active' : '' }}"
        href="{{ route('reward.pemustaka_teraktif') }}">
        <i class="fas fa-gift nav-icon"></i>
        <span class="nav-text">Pemustaka Teraktif</span>
    </a>
</li>
</ul>

<ul class="nav flex-column mt-auto py-3 border-top border-light-subtle">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('credit.index') ? 'active' : '' }}"
            href="{{ route('credit.index') }}">
            <i class="fas fa-info-circle nav-icon"></i>
            <span class="nav-text">Pengembang</span>
        </a>
    </li>
</ul>
</div>
