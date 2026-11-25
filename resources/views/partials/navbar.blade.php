<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
        <img src="{{ asset('img/logo.png') }}" alt="Logo" style="height: 40px;" class="me-2">
        <span>Dashboard</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav ms-auto">
            {{-- Data SDM --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="sdmDropdown" role="button"
                    data-bs-toggle="dropdown">
                    <i class="fas fa-users me-1"></i> Data SDM
                </a>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li><a class="dropdown-item" href="{{ route('staff.index') }}"><i
                                class="fas fa-id-card me-2"></i>Master Data Staff</a></li>
                    <li><a class="dropdown-item" href="{{ route('ijazah.index') }}"><i
                                class="fas fa-graduation-cap me-2"></i>Data Ijazah</a></li>
                    <li><a class="dropdown-item" href="{{ route('transkrip.index') }}"><i
                                class="fas fa-file-alt me-2"></i>Data Transkrip</a></li>
                    <li><a class="dropdown-item" href="{{ route('pelatihan.index') }}"><i
                                class="fas fa-chalkboard-teacher me-2"></i>Data Pelatihan</a></li>
                    <li><a class="dropdown-item" href="{{ route('skp.index') }}"><i class="fas fa-tasks me-2"></i>Data
                            SKP</a></li>
                    <li><a class="dropdown-item" href="{{ route('sertifikasi.index') }}"><i
                                class="fas fa-certificate me-2"></i>Data Sertifikasi</a></li>
                    <li><a class="dropdown-item" href="{{ route('mou.index') }}"><i
                                class="fas fa-handshake me-2"></i>Data MoU</a></li>
                </ul>
            </li>

            {{-- Daftar Koleksi --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="koleksiDropdown" role="button"
                    data-bs-toggle="dropdown">
                    <i class="fas fa-book me-1"></i> Daftar Koleksi
                </a>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li class="dropdown-header">Statistik Koleksi</li>
                    <li><a class="dropdown-item" href="{{ route('koleksi.prodi') }}"><i
                                class="fas fa-chart-pie me-2"></i>Per Prodi</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li class="dropdown-header">Jenis Koleksi</li>
                    <li><a class="dropdown-item" href="{{ route('koleksi.jurnal') }}"><i
                                class="fas fa-journal-whills me-2"></i>Journal</a></li>
                    <li><a class="dropdown-item" href="{{ route('koleksi.ebook') }}"><i
                                class="fas fa-tablet-alt me-2"></i>E-Book</a></li>
                    <li><a class="dropdown-item" href="{{ route('koleksi.textbook') }}"><i
                                class="fas fa-book me-2"></i>Text Book</a></li>
                    <li><a class="dropdown-item" href="{{ route('koleksi.prosiding') }}"><i
                                class="fas fa-newspaper me-2"></i>Prosiding</a></li>
                    <li><a class="dropdown-item" href="{{ route('koleksi.periodikal') }}"><i
                                class="fas fa-calendar-week me-2"></i>Periodical</a></li>
                    <li><a class="dropdown-item" href="{{ route('koleksi.referensi') }}"><i
                                class="fas fa-book-open me-2"></i>References</a></li>
                </ul>
            </li>

            {{-- Kunjungan --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="kunjunganDropdown" role="button"
                    data-bs-toggle="dropdown">
                    <i class="fas fa-users me-1"></i> Data Kunjungan
                </a>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li><a class="dropdown-item" href="{{ route('kunjungan.prodi') }}"><i
                                class="fas fa-table me-2"></i>Per Prodi</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
