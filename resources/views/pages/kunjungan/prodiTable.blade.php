@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
            color: var(--text-dark) !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-dropdown {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            background-color: #334155 !important;
            border-color: var(--border-color) !important;
            color: var(--text-dark) !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__rendered {
            color: #ffffff !important;
        }

        body.dark-mode .select2-results__option {
            color: #ffffff !important;
        }

        body.dark-mode .select2-results__option--highlighted {
            background-color: var(--primary-color) !important;
            color: white !important;
        }

        body.dark-mode .select2-results__option[aria-selected=true] {
            background-color: rgba(var(--bs-primary-rgb), 0.2) !important;
            color: var(--text-dark);
        }
    </style>
@endpush
@extends('layouts.app')

@section('title', 'Statistik Kunjungan Civitas')
@section('content')
    <div class="container">
        <div class="card bg-white shadow-sm mb-4">
            <div class="card-body">
                <h4 class="mb-0">Statistik Kunjungan Civitas</h4>
                <small class="text-muted">Ringkasan data kunjungan berdasarkan program studi dan periode</small>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header
                <h6 class="mb-0">Filter Data</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('kunjungan.prodiTable') }}" class="row g-3 align-items-end"
                    id="filterForm">
                    <div class="col-md-auto">
                        <label for="filter_type" class="form-label">Tampilkan Data:</label>
                        <select name="filter_type" id="filter_type" class="form-select">
                            <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>Per Hari
                            </option>
                            <option value="yearly" {{ ($filterType ?? '') == 'yearly' ? 'selected' : '' }}>Per Bulan
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="prodi" class="form-label">Pilih Prodi/Tipe User</label>
                        <select name="prodi" id="prodi" class="form-select">
                            {{-- <option value="semua" {{ request('prodi', 'semua') == 'semua' ? 'selected' : '' }}>
                                (Semua) -- Seluruh Prodi
                            </option> --}}
                            @foreach ($listProdi as $kode => $nama)
                                <option class="custom-option" value="{{ $kode }}"
                                    {{ request('prodi') == $kode ? 'selected' : '' }}>
                                    ({{ $kode }})
                                    - {{ $nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3" id="dailyFilterStart"
                        style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                        <label for="tanggal_awal" class="form-label">Tanggal Awal:</label>
                        <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control"
                            value="{{ $tanggalAwal ?? \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3" id="dailyFilterEnd"
                        style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                        <label for="tanggal_akhir" class="form-label">Tanggal Akhir:</label>
                        <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control"
                            value="{{ $tanggalAkhir ?? \Carbon\Carbon::now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3" id="yearlyFilter"
                        style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                        <label for="tahun_awal" class="form-label">Tahun Awal:</label>
                        <input type="number" name="tahun_awal" id="tahun_awal" class="form-control"
                            value="{{ $tahunAwal ?? \Carbon\Carbon::now()->year }}">
                    </div>
                    <div class="col-md-3" id="yearlyFilterEnd"
                        style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                        <label for="tahun_akhir" class="form-label">Tahun Akhir:</label>
                        <input type="number" name="tahun_akhir" id="tahun_akhir" class="form-control"
                            value="{{ $tahunAkhir ?? \Carbon\Carbon::now()->year }}">
                    </div>

                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($hasFilter)
            {{-- Bagian Chart --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0 me-3">Grafik Kunjungan {{ $listProdi[request('prodi')] ?? 'Seluruh Prodi/Tipe User' }}
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="kunjunganChart"></canvas>
                </div>
            </div>

            {{-- Bagian Tabel --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 me-3">Tabel Statistik Kunjungan</h5>
                    <div class="d-flex align-items-center">
                        <div class="dropdown me-2">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="entriesDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Tampilkan {{ $data->perPage() }} entri
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="entriesDropdown">
                                <li><a class="dropdown-item @if ($perPage == 10) active @endif"
                                        href="{{ request()->fullUrlWithQuery(['per_page' => 10]) }}">10</a></li>
                                <li><a class="dropdown-item @if ($perPage == 100) active @endif"
                                        href="{{ request()->fullUrlWithQuery(['per_page' => 100]) }}">100</a></li>
                                <li><a class="dropdown-item @if ($perPage == 1000) active @endif"
                                        href="{{ request()->fullUrlWithQuery(['per_page' => 1000]) }}">1000</a></li>
                            </ul>
                        </div>
                        <button id="downloadFullCsvBtn" class="btn btn-success btn-md">
                            <i class="fas fa-file-csv me-1"></i> Export CSV
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="alert alert-primary py-2 m-0">
                                <i class="fas fa-book me-2"></i> Total Keseluruhan:
                                <span class="fw-bold">{{ number_format($totalKeseluruhanKunjungan, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-info py-2 m-0">
                                <i class="fas fa-list-ol me-2"></i> Total Entri Data:
                                <span class="fw-bold">{{ number_format($data->total(), 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="alert alert-secondary py-2 m-0">
                                <i class="fas fa-filter me-2"></i>
                                <span class="fw-bold">
                                    @if (($filterType ?? 'daily') == 'daily')
                                        @if ($tanggalAwal && $tanggalAkhir)
                                            {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }} s/d
                                            {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}
                                        @else
                                            Tidak Ada
                                        @endif
                                    @elseif (($filterType ?? '') == 'yearly')
                                        @if ($tahunAwal && $tahunAkhir)
                                            Tahun {{ $tahunAwal }} s/d {{ $tahunAkhir }}
                                        @else
                                            Semua Tahun
                                        @endif
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal / Bulan</th>
                                    <th>Prodi</th>
                                    <th>Jumlah Kunjungan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($data->isEmpty())
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada data untuk periode ini.</td>
                                    </tr>
                                @else
                                    @foreach ($data as $row)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                @if (($filterType ?? 'daily') == 'yearly')
                                                    {{ \Carbon\Carbon::parse($row->tanggal_kunjungan)->locale('id')->isoFormat('MMMM Y') }}
                                                @else
                                                    {{ \Carbon\Carbon::parse($row->tanggal_kunjungan)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                                @endif
                                            </td>
                                            <td>{{ $row->nama_prodi }}</td>
                                            <td>{{ $row->jumlah_kunjungan_harian }}</td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm view-detail-btn"
                                                    data-bs-toggle="modal" data-bs-target="#detailPengunjungModal"
                                                    data-tanggal="{{ $row->tanggal_kunjungan }}"
                                                    data-filter-type="{{ $filterType ?? 'daily' }}"
                                                    data-kode-identifikasi="{{ $row->kode_identifikasi }}"
                                                    data-total="{{ $row->jumlah_kunjungan_harian }}">
                                                    <i class="fas fa-eye me-1"></i> Lihat Detail
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    {{-- Pagination --}}
                    <div class="d-flex justify-content-end">
                        {{ $data->links() }}
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info text-center mt-4">
                Silakan gunakan filter di atas untuk menampilkan data statistik kunjungan.
            </div>
        @endif

        {{-- Modal untuk detail pengunjung --}}
        <div class="modal fade" id="detailPengunjungModal" tabindex="-1" aria-labelledby="detailPengunjungModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailPengunjungModalLabel">Detail Pengunjung <small
                                id="modalPeriodeSpan"></small>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fs-6">Kode Identifikasi: <strong id="modalKodeTipeSpan"></strong></span>
                            <span class="fs-6">Total: <strong id="modalTotalSpan"></strong></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Cardnumber</th>
                                        <th>Jumlah Kunjungan</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyDetailPengunjung">
                                    <tr>
                                        <td colspan="4" class="text-center">Memuat data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3" id="modalPagination"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="exportDetailPengunjungCsvBtn">
                            <i class="fas fa-file-csv me-2"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/locale/id.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Variabel Global & Elemen HTML ---
            const filterForm = document.getElementById('filterForm');
            const filterTypeSelect = document.getElementById('filter_type');
            const dailyFilterStart = document.getElementById('dailyFilterStart');
            const dailyFilterEnd = document.getElementById('dailyFilterEnd');
            const yearlyFilter = document.getElementById('yearlyFilter');
            const yearlyFilterEnd = document.getElementById('yearlyFilterEnd');
            const detailModalEl = new bootstrap.Modal(document.getElementById('detailPengunjungModal'));
            const modalPeriodeSpan = document.getElementById('modalPeriodeSpan');
            const modalKodeTipeSpan = document.getElementById('modalKodeTipeSpan');
            const tbodyDetailPengunjung = document.getElementById('tbodyDetailPengunjung');
            const modalPagination = document.getElementById('modalPagination');
            const modalTotalSpan = document.getElementById('modalTotalSpan');

            let currentDetailTanggal = '';
            let currentFilterType = '';
            let currentKodeIdentifikasi = '';

            // Mengambil nilai dari PHP ke JavaScript
            const listProdi = @json($listProdi);
            const tanggalAwal = @json($tanggalAwal);
            const tanggalAkhir = @json($tanggalAkhir);
            const tahunAwal = @json($tahunAwal ?? null);
            const tahunAkhir = @json($tahunAkhir ?? null);
            const loadingMessage = `<tr><td colspan="4" class="text-center">Memuat data...</td></tr>`;

            // --- Logika Tampilan Form Filter Dinamis ---
            filterTypeSelect.addEventListener('change', function() {
                if (this.value === 'daily') {
                    dailyFilterStart.style.display = 'block';
                    dailyFilterEnd.style.display = 'block';
                    yearlyFilter.style.display = 'none';
                    yearlyFilterEnd.style.display = 'none';
                } else {
                    dailyFilterStart.style.display = 'none';
                    dailyFilterEnd.style.display = 'none';
                    yearlyFilter.style.display = 'block';
                    yearlyFilterEnd.style.display = 'block';
                }
            });

            // --- Logika untuk Chart (hanya dijalankan jika ada data) ---
            const hasFilter = {{ json_encode($hasFilter) }};

            if (hasFilter) {
                const chartCtx = document.getElementById('kunjunganChart').getContext('2d');
                let kunjunganChart = null;
                const chartData = @json($chartData);
                const filterType = '{{ $filterType }}';

                if (kunjunganChart) {
                    kunjunganChart.destroy();
                }

                const chartLabels = chartData.map(item => item.label);
                const chartValues = chartData.map(item => item.total_kunjungan);
                const formattedLabels = chartLabels.map(label => {
                    const date = moment(label);
                    if (filterType === 'yearly') {
                        return date.format('MMM YYYY');
                    } else {
                        return date.format('ddd, DD MMM');
                    }
                });

                kunjunganChart = new Chart(chartCtx, {
                    type: 'bar',
                    data: {
                        labels: formattedLabels,
                        datasets: [{
                            label: 'Jumlah Kunjungan',
                            data: chartValues,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            tension: 1,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                beginAtZero: true,
                                type: 'category',
                                ticks: {
                                    callback: function(val, index) {
                                        const date = moment(chartLabels[index]);
                                        if (filterType === 'daily') {
                                            return date.format('D MMM');
                                        } else {
                                            return date.format('MMM YYYY');
                                        }
                                    }
                                },
                                title: {
                                    display: true,
                                    text: (filterType === 'daily') ? 'Tanggal' : 'Bulan'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Jumlah Kunjungan'
                                },
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        const date = moment(chartLabels[context[0].dataIndex]);
                                        if (filterType === 'daily') {
                                            return date.format('dddd, D MMMM YYYY');
                                        } else {
                                            return date.format('MMMM YYYY');
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }
            async function loadDetailData(page = 1) {
                tbodyDetailPengunjung.innerHTML = loadingMessage;
                modalPagination.innerHTML = '';
                modalTotalSpan.textContent = '...';

                let tanggalParam = '';
                if (currentFilterType === 'yearly') {
                    tanggalParam = `bulan=${currentDetailTanggal.substring(0, 7)}`;
                } else {
                    tanggalParam = `tanggal=${currentDetailTanggal}`;
                }
                const url =
                    `{{ route('kunjungan.get_detail_pengunjung') }}?${tanggalParam}&kode_identifikasi=${currentKodeIdentifikasi}&per_page=10&page=${page}`;

                try {
                    const response = await fetch(url);
                    const result = await response.json();

                    tbodyDetailPengunjung.innerHTML = '';
                    if (response.ok && result.data && result.data.length > 0) {
                        modalTotalSpan.textContent = result.total;

                        result.data.forEach((pengunjung, index) => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                            <td>${(result.from || 1) + index}</td>
                            <td>${pengunjung.nama || 'Tidak Diketahui'}</td>
                            <td>${pengunjung.cardnumber}</td>
                            <td><span class="badge bg-secondary rounded-pill">${pengunjung.visit_count}x</span></td>
                        `;
                            tbodyDetailPengunjung.appendChild(tr);
                        });

                        // Buat link paginasi
                        renderPagination(result);
                    } else {
                        const tr = document.createElement('tr');
                        tr.innerHTML =
                            `<td colspan="4" class="text-center">Tidak ada detail nama pengunjung ditemukan.</td>`;
                        tbodyDetailPengunjung.appendChild(tr);
                        modalTotalSpan.textContent = 0;
                    }
                } catch (error) {
                    console.error('Error fetching detail:', error);
                    tbodyDetailPengunjung.innerHTML =
                        `<tr><td colspan="4" class="text-danger text-center">Terjadi kesalahan jaringan atau server.</td></tr>`;
                    modalTotalSpan.textContent = 'Error';
                }
            }

            function renderPagination(data) {
                const ul = document.createElement('ul');
                ul.classList.add('pagination', 'pagination-sm', 'm-0');

                // Tombol Previous
                const prevLi = document.createElement('li');
                prevLi.classList.add('page-item', 'prev');
                if (!data.prev_page_url) {
                    prevLi.classList.add('disabled');
                }
                const prevLink = document.createElement('a');
                prevLink.classList.add('page-link');
                prevLink.href = '#';
                prevLink.innerHTML = '&laquo;';
                prevLi.appendChild(prevLink);
                ul.appendChild(prevLi);

                // Tautan Halaman
                for (let i = 1; i <= data.last_page; i++) {
                    const li = document.createElement('li');
                    li.classList.add('page-item');
                    if (i === data.current_page) {
                        li.classList.add('active');
                    }
                    const a = document.createElement('a');
                    a.classList.add('page-link');
                    a.href = '#';
                    a.textContent = i;
                    a.dataset.page = i;
                    li.appendChild(a);
                    ul.appendChild(li);
                }

                // Tombol Next
                const nextLi = document.createElement('li');
                nextLi.classList.add('page-item', 'next');
                if (!data.next_page_url) {
                    nextLi.classList.add('disabled');
                }
                const nextLink = document.createElement('a');
                nextLink.classList.add('page-link');
                nextLink.href = '#';
                nextLink.innerHTML = '&raquo;';
                nextLi.appendChild(nextLink);
                ul.appendChild(nextLi);

                modalPagination.appendChild(ul);

                if (data.prev_page_url) {
                    prevLi.addEventListener('click', function(e) {
                        e.preventDefault();
                        loadDetailData(data.current_page - 1);
                    });
                }
                if (data.next_page_url) {
                    nextLi.addEventListener('click', function(e) {
                        e.preventDefault();
                        loadDetailData(data.current_page + 1);
                    });
                }

                modalPagination.querySelectorAll('.page-item a').forEach(link => {
                    if (link.dataset.page) {
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            const page = parseInt(this.dataset.page);
                            loadDetailData(page);
                        });
                    }
                });
            }

            // --- Event listener untuk tombol "Lihat Detail" ---
            document.querySelectorAll('.view-detail-btn').forEach(button => {
                button.addEventListener('click', async function() {
                    // Ambil data dari atribut `data-*` di tombol
                    currentFilterType = this.dataset.filterType;
                    currentKodeIdentifikasi = this.dataset.kodeIdentifikasi;

                    let periodeText = '';
                    if (currentFilterType === 'yearly') {
                        const bulanTahun = this.dataset.tanggal;
                        currentDetailTanggal = bulanTahun;
                        periodeText = new Date(`${bulanTahun}-01`).toLocaleDateString('id-ID', {
                            month: 'long',
                            year: 'numeric'
                        });
                    } else {
                        const tanggalKunjungan = this.dataset.tanggal;
                        currentDetailTanggal = tanggalKunjungan;
                        periodeText = new Date(tanggalKunjungan).toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric'
                        });
                    }

                    // Tampilkan modal
                    detailModalEl.show();
                    modalPeriodeSpan.textContent = `(${periodeText})`;
                    modalKodeTipeSpan.textContent = listProdi[currentKodeIdentifikasi] ||
                        currentKodeIdentifikasi;
                    loadDetailData(1);
                });
            });

            const downloadFullCsvBtn = document.getElementById('downloadFullCsvBtn');
            const exportDetailPengunjungCsvBtn = document.getElementById('exportDetailPengunjungCsvBtn');

            if (downloadFullCsvBtn) {
                downloadFullCsvBtn.addEventListener('click', async function() {
                    const params = new URLSearchParams(window.location.search);
                    const filterType = params.get('filter_type') || 'daily';
                    const prodiSelect = document.getElementById('prodi');
                    const prodiCode = prodiSelect ? prodiSelect.value : 'semua';
                    let prodiName = 'Semua_Prodi';

                    if (prodiCode !== 'semua') {
                        const selectedOption = prodiSelect.options[prodiSelect.selectedIndex];
                        prodiName = selectedOption.textContent.trim()
                            .replace(/\s-\s|[\(\)]/g, '')
                            .replace(/\s+/g,
                                '_');
                    }

                    let fileName = `kunjungan_${prodiName}`;

                    // Tentukan nama file berdasarkan filter
                    if (filterType === 'yearly') {
                        const tahunAwal = params.get('tahun_awal');
                        const tahunAkhir = params.get('tahun_akhir');
                        if (tahunAwal && tahunAkhir) {
                            fileName += `_tahun_${tahunAwal}`;
                            if (tahunAwal !== tahunAkhir) {
                                fileName += `_${tahunAkhir}`;
                            }
                        }
                    } else { // daily
                        const tanggalAwal = params.get('tanggal_awal');
                        const tanggalAkhir = params.get('tanggal_akhir');
                        if (tanggalAwal && tanggalAkhir) {
                            fileName += `_${tanggalAwal}_s.d._${tanggalAkhir}`;
                        } else {
                            fileName += `_${new Date().toISOString().slice(0, 10)}`;
                        }
                    }

                    const exportUrl =
                        `{{ route('kunjungan.get_prodi_export_data') }}?${params.toString()}`;

                    try {
                        const response = await fetch(exportUrl);
                        if (!response.ok) {
                            throw new Error('Gagal mengunduh file CSV.');
                        }
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `${fileName}.csv`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    } catch (error) {
                        alert(error.message);
                    }
                });
            }

            if (exportDetailPengunjungCsvBtn) {
                exportDetailPengunjungCsvBtn.addEventListener('click', async function() {
                    if (!currentDetailTanggal || !currentKodeIdentifikasi) {
                        alert("Tidak ada data detail untuk diekspor.");
                        return;
                    }

                    let tanggalParam = '';
                    if (currentFilterType === 'yearly') {
                        tanggalParam = `bulan=${currentDetailTanggal.substring(0, 7)}`;
                    } else {
                        tanggalParam = `tanggal=${currentDetailTanggal}`;
                    }
                    const url =
                        `{{ route('kunjungan.get_detail_pengunjung') }}?${tanggalParam}&kode_identifikasi=${currentKodeIdentifikasi}&export=true`;

                    try {
                        const response = await fetch(url);
                        const result = await response.json();

                        if (response.ok) {
                            if (result.length === 0) {
                                alert(
                                    "Tidak ada data detail pengunjung untuk diekspor pada periode ini."
                                );
                                return;
                            }

                            let csv = [];
                            const delimiter = ';';
                            const headers = ['No', 'Nama', 'Cardnumber', 'Jumlah Kunjungan'];
                            csv.push(headers.map(h => `"${h}"`).join(delimiter));

                            let counter = 1;
                            result.forEach(pengunjung => {
                                const rowData = [
                                    `"${counter++}"`,
                                    `"${(pengunjung.nama || '').replace(/"/g, '""')}"`,
                                    `"${(pengunjung.cardnumber || '').replace(/"/g, '""')}"`,
                                    `"${pengunjung.visit_count}"`
                                ];
                                csv.push(rowData.join(delimiter));
                            });

                            const BOM = "\uFEFF";
                            const csvString = csv.join('\n');
                            const blob = new Blob([BOM + csvString], {
                                type: 'text/csv;charset=utf-8;'
                            });

                            const link = document.createElement("a");
                            link.href = URL.createObjectURL(blob);
                            link.download =
                                `detail_kunjungan_${currentKodeIdentifikasi}_${currentDetailTanggal}.csv`;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } else {
                            alert(result.error ||
                                "Terjadi kesalahan saat mengambil data export detail.");
                        }
                    } catch (error) {
                        console.error('Error fetching export data:', error);
                        alert("Terjadi kesalahan teknis saat mencoba mengekspor data detail.");
                    }
                });
            }
        });
    </script>
@endpush
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#prodi').select2({
                theme: 'bootstrap-5',
            });
        });
    </script>
@endpush
