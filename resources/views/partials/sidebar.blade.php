<div class="sidebar-header d-flex align-items-center justify-content-between p-4">
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
        <img src="{{ asset('img/sidebar.png') }}" alt="Logo" class="sidebar-logo" style="max-height: 200px;">
    </a>
</div>

<div class="sidebar-menu px-3 d-flex flex-column min-vh-100 mt-3" id="sidebarMenu">
    <ul class="nav flex-column flex-grow-1 gap-2">
        {{-- Dashboard --}}
        <li class="nav-item">
            <a data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard"
                class="nav-link d-flex align-items-center rounded py-2 px-3 {{ request()->is('dashboard') ? 'active' : '' }}"
                href="{{ route('dashboard') }}">
                <i class="fas fa-home me-3"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>

        {{-- Data SDM --}}
        {{-- @php
            $isSdmActive = request()->routeIs([
                'staff.*',
                'ijazah.*',
                'transkrip.*',
                'pelatihan.*',
                'skp.*',
                'sertifikasi.*',
                'mou.*',
            ]);
        @endphp
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center rounded py-2 px-3 {{ $isSdmActive ? 'active' : '' }}"
                data-bs-toggle="collapse" href="#sdmCollapse" id="sdmMenuBtn">
                <i class="fas fa-users me-3"></i>
                <span class="nav-text">Data SDM</span>
                <i class="fas fa-chevron-right ms-auto nav-arrow"></i>
            </a>
            <div class="collapse {{ $isSdmActive ? 'show' : '' }}" id="sdmCollapse">
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('staff.*') ? 'active' : '' }}"
                            href="{{ route('staff.index') }}">
                            <i class="fas fa-id-card me-2"></i>Master Data Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('ijazah.*') ? 'active' : '' }}"
                            href="{{ route('ijazah.index') }}">
                            <i class="fas fa-graduation-cap me-2"></i>Data Ijazah
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('transkrip.*') ? 'active' : '' }}"
                            href="{{ route('transkrip.index') }}">
                            <i class="fas fa-file-alt me-2"></i>Data Transkrip
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('pelatihan.*') ? 'active' : '' }}"
                            href="{{ route('pelatihan.index') }}">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Data Pelatihan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('skp.*') ? 'active' : '' }}"
                            href="{{ route('skp.index') }}">
                            <i class="fas fa-tasks me-2"></i>Data SKP
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('sertifikasi.*') ? 'active' : '' }}"
                            href="{{ route('sertifikasi.index') }}">
                            <i class="fas fa-certificate me-2"></i>Data Sertifikasi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('mou.*') ? 'active' : '' }}"
                            href="{{ route('mou.index') }}">
                            <i class="fas fa-handshake me-2"></i>Data MoU
                        </a>
                    </li>
                </ul>
            </div>
        </li> --}}

        {{-- Daftar Koleksi --}}
        @php
            $isDaftarKoleksiActive = request()->routeIs(['koleksi.*']);
        @endphp
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center rounded py-2 px-3 {{ $isDaftarKoleksiActive ? 'active' : '' }}"
                data-bs-toggle="collapse" href="#daftarKoleksiCollapse" id="daftarKoleksiMenuBtn">
                <i class="fas fa-book me-3"></i>
                <span class="nav-text">Daftar Koleksi</span>
                <i class="fas fa-chevron-right ms-auto nav-arrow"></i>
            </a>
            <div class="collapse {{ $isDaftarKoleksiActive ? 'show' : '' }}" id="daftarKoleksiCollapse">
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link rounded py-1 px-3 {{ request()->routeIs('koleksi.jurnal') ? 'active' : '' }}"
                            href="{{ route('koleksi.jurnal') }}">
                            <i class="fas fa-journal-whills me-2"></i>Journal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-1 px-3 {{ request()->routeIs('koleksi.ebook') ? 'active' : '' }}"
                            href="{{ route('koleksi.ebook') }}">
                            <i class="fas fa-tablet-alt me-2"></i>E-Book
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-1 px-3 {{ request()->routeIs('koleksi.textbook') ? 'active' : '' }}"
                            href="{{ route('koleksi.textbook') }}">
                            <i class="fas fa-book me-2"></i>Text Book
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-1 px-3 {{ request()->routeIs('koleksi.prosiding') ? 'active' : '' }}"
                            href="{{ route('koleksi.prosiding') }}">
                            <i class="fas fa-newspaper me-2"></i>Prosiding
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-1 px-3 {{ request()->routeIs('koleksi.periodikal') ? 'active' : '' }}"
                            href="{{ route('koleksi.periodikal') }}">
                            <i class="fas fa-calendar-week me-2"></i>Majalah
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-1 px-3 {{ request()->routeIs('koleksi.referensi') ? 'active' : '' }}"
                            href="{{ route('koleksi.referensi') }}">
                            <i class="fas fa-bookmark me-2"></i>References
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Data Kunjungan --}}
        @php
            $isKunjunganActive = request()->routeIs(['kunjungan.*']);
        @endphp
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center rounded py-2 px-3 {{ $isKunjunganActive ? 'active' : '' }}"
                data-bs-toggle="collapse" href="#kunjunganCollapse" id="kunjunganMenuBtn">
                <i class="fas fa-users me-3"></i>
                <span class="nav-text">Data Kunjungan</span>
                <i class="fas fa-chevron-right ms-auto nav-arrow"></i>
            </a>
            <div class="collapse {{ $isKunjunganActive ? 'show' : '' }}" id="kunjunganCollapse">
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('kunjungan.kunjungan_gabungan') ? 'active' : '' }}"
                            href="{{ route('kunjungan.kunjungan_gabungan') }}">
                            <i class="fas fa-file-alt me-2"></i>Kunjungan Perhari
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('kunjungan.tanggalTable') ? 'active' : '' }}"
                            href="{{ route('kunjungan.tanggalTable') }}">
                            <i class="fas fa-calendar-alt me-2"></i>Keseluruhan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('kunjungan.prodiTable') ? 'active' : '' }}"
                            href="{{ route('kunjungan.prodiTable') }}">
                            <i class="fas fa-table me-2"></i>Mahasiswa & Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('kunjungan.cekKehadiran') ? 'active' : '' }}"
                            href="{{ route('kunjungan.cekKehadiran') }}">
                            <i class="fas fa-clipboard-check me-2"></i>Cek Kehadiran
                        </a>
                    </li>

                </ul>
            </div>
        </li>

        {{-- Data Peminjaman --}}
        @php
            $isPeminjamanActive = request()->routeIs(['peminjaman.*']);
        @endphp
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center rounded py-2 px-3 {{ $isPeminjamanActive ? 'active' : '' }}"
                data-bs-toggle="collapse" href="#peminjamanCollapse" id="peminjamanMenuBtn">
                <i class="fas fa-book-reader me-3"></i>
                <span class="nav-text">Data Peminjaman</span>
                <i class="fas fa-chevron-right ms-auto nav-arrow"></i>
            </a>
            <div class="collapse {{ $isPeminjamanActive ? 'show' : '' }}" id="peminjamanCollapse">
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('peminjaman.peminjaman_rentang_tanggal') ? 'active' : '' }}"
                            href="{{ route('peminjaman.peminjaman_rentang_tanggal') }}">
                            <i class="fas fa-calendar-day me-2"></i>Keseluruhan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('peminjaman.peminjaman_prodi_chart') ? 'active' : '' }}"
                            href="{{ route('peminjaman.peminjaman_prodi_chart') }}">
                            <i class="fas fa-chart-line me-2"></i>Statistik Prodi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('peminjaman.check_history') ? 'active' : '' }}"
                            href="{{ route('peminjaman.check_history') }}">
                            <i class="fas fa-history me-2"></i>Cek Peminjaman
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('peminjaman.berlangsung') ? 'active' : '' }}"
                            href="{{ route('peminjaman.berlangsung') }}">
                            <i class="fas fa-handshake me-2"></i>Sedang Berlangsung
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('peminjaman.keterpakaian_koleksi') ? 'active' : '' }}"
                            href="{{ route('peminjaman.keterpakaian_koleksi') }}">
                            <i class="fas fa-archive me-2"></i>Keterpakaian Koleksi
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        {{-- Reward --}}
        @php
            $isRewardActive = request()->routeIs(['reward.*']);
        @endphp
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center rounded py-2 px-3 {{ $isRewardActive ? 'active' : '' }}"
                data-bs-toggle="collapse" href="#rewardCollapse" id="rewardMenuBtn">
                <i class="fas fa-gift me-3"></i>
                <span class="nav-text">Reward</span>
                <i class="fas fa-chevron-right ms-auto nav-arrow"></i>
            </a>
            <div class="collapse {{ $isRewardActive ? 'show' : '' }}" id="rewardCollapse">
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('reward.pengunjung_teraktif') ? 'active' : '' }}"
                            href="{{ route('reward.pengunjung_teraktif') }}">
                            <i class="fas fa-trophy me-2"></i> Pengunjung Teraktif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded py-2 px-3 {{ request()->routeIs('reward.peminjam_teraktif') ? 'active' : '' }}"
                            href="{{ route('reward.peminjam_teraktif') }}">
                            <i class="fas fa-history me-2"></i>Peminjam Teraktif
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>

    <ul class="nav flex-column py-3 border-top border-secondary">
        <li class="nav-item">
            <a data-bs-toggle="tooltip" data-bs-placement="right" title="Credit & Developers"
                class="nav-link d-flex align-items-center rounded py-2 px-3 {{ request()->routeIs('credit.index') ? 'active' : '' }}"
                href="{{ route('credit.index') }}">
                <i class="fas fa-info-circle me-3"></i>
                <span class="nav-text">Developers</span>
            </a>
        </li>
    </ul>
</div>
