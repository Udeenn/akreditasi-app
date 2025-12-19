@extends('layouts.app')

@section('title', 'Statistik Peminjaman (Rentang Waktu)')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }

        /* Dark Mode Select2 Styles */
        body.dark-mode .select2-container--bootstrap-5 .select2-selection {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__rendered {
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__arrow b {
            border-color: #adb5bd transparent transparent transparent !important;
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

        body.dark-mode .select2-results__option {
            color: #ffffff !important;
        }

        body.dark-mode .select2-results__option--highlighted {
            background-color: var(--primary-color) !important;
            color: white !important;
        }

        body.dark-mode .select2-results__option[aria-selected=true] {
            background-color: rgba(var(--bs-primary-rgb), 0.2) !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    <div class="container px-4">
        <div class="d-flex align-items-center mb-4">
            <i class="fas fa-calendar fa-2x text-primary me-3"></i>
            <div>
                <h4 class="mb-0">Statistik Peminjaman Keseluruhan</h4>
                <small class="text-muted">
                    Menampilkan statistik peminjaman buku perpustakaan berdasarkan rentang
                    tanggal / tahun yang dipilih.
                </small>
            </div>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold "><i class="fas fa-filter me-1"></i> Filter Periode Data</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('peminjaman.peminjaman_rentang_tanggal') }}"
                    class="row g-3 align-items-end" id="filterForm">

                    <div class="col-md-3">
                        <label for="filter_type" class="form-label small text-uppercase fw-bold text-muted">Tampilkan
                            Data</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text "><i class="fas fa-list"></i></span>
                            <select name="filter_type" id="filter_type" class="form-select">
                                <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>Harian
                                    (Per Hari)</option>
                                <option value="monthly" {{ ($filterType ?? '') == 'monthly' ? 'selected' : '' }}>Bulanan
                                    (Per Bulan)</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4" id="dailyFilter"
                        style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                        <label class="form-label small text-uppercase fw-bold text-muted">Rentang Tanggal</label>
                        <div class="input-group input-group-sm">
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="{{ $startDate ?? \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') }}">
                            <span class="input-group-text">s.d.</span>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ $endDate ?? \Carbon\Carbon::now()->format('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="col-md-4" id="monthlyFilter"
                        style="{{ ($filterType ?? '') == 'monthly' ? '' : 'display: none;' }}">
                        <label class="form-label small text-uppercase fw-bold text-muted">Rentang Tahun</label>
                        <div class="input-group input-group-sm">
                            @php
                                $currentYear = date('Y');
                                $loopStartYear = $currentYear - 10;
                                $loopEndYear = $currentYear;
                            @endphp
                            <select name="start_year" id="start_year" class="form-select">
                                @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                    <option value="{{ $year }}"
                                        {{ ($startYear ?? $currentYear) == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                            <span class="input-group-text ">s.d.</span>
                            <select name="end_year" id="end_year" class="form-select">
                                @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                    <option value="{{ $year }}"
                                        {{ ($endYear ?? $currentYear) == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 shadow-sm ">
                            <i class="fas fa-search me-1"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger border-left-danger shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            </div>
        @endif

        @if (!empty($statistics) && !$statistics->isEmpty())

            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2 stat-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold  text-uppercase mb-1">Total Buku
                                        Terpinjam</div>
                                    <div class="h4 mb-0 fw-bold text-gray-800">{{ number_format($totalBooks) }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-book fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2 stat-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">Total Peminjam</div>
                                    <div class="h4 mb-0 fw-bold text-gray-800">
                                        {{ number_format($totalBorrowers) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2 stat-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">Total Transaksi
                                    </div>
                                    <div class="h4 mb-0 fw-bold text-gray-800">{{ $statistics->total() }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2 stat-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                        Rerata {{ ($filterType ?? 'daily') == 'daily' ? 'Harian' : 'Bulanan' }}
                                    </div>
                                    <div class="h4 mb-0 fw-bold text-gray-800">
                                        {{ number_format($rerataPeminjaman, 1) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold "><i class="fas fa-chart-area me-1"></i> Grafik Tren
                        Peminjaman</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="position: relative; height: 40vh;">
                        <canvas id="peminjamanChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold "><i class="fas fa-table me-1"></i> Rincian Data
                        Peminjaman</h6>
                    <button type="button" id="exportCsvBtn" class="btn btn-success btn-sm shadow-sm">
                        <i class="fas fa-file-csv me-1"></i> Export CSV
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th class="ps-3">Periode</th>
                                    <th class="text-center">Buku Terpinjam</th>
                                    <th class="text-center">Jumlah Transaksi</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($statistics as $index => $stat)
                                    <tr>
                                        <td class="text-center text-muted">{{ $statistics->firstItem() + $index }}
                                        </td>
                                        <td class="ps-3 text-gray-800">
                                            @if (($filterType ?? 'daily') == 'daily')
                                                @if ($stat->periode)
                                                    <i
                                                        class="far fa-calendar-alt me-2 text-muted"></i>{{ \Carbon\Carbon::parse($stat->periode)->format('d F Y') }}
                                                @else
                                                    -
                                                @endif
                                            @else
                                                @if ($stat->periode)
                                                    <i
                                                        class="far fa-calendar me-2 text-muted"></i>{{ \Carbon\Carbon::createFromFormat('Y-m', $stat->periode)->format('F Y') }}
                                                @else
                                                    -
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="px-3">{{ $stat->jumlah_peminjaman_buku }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class=" px-3">{{ $stat->jumlah_peminjam_unik }}</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary view-detail-btn  px-3"
                                                data-bs-toggle="modal" data-bs-target="#detailPeminjamanModal"
                                                data-periode="{{ $stat->periode }}">
                                                <i class="fas fa-eye me-1"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer py-3">
                    <div class="d-flex justify-content-end">
                        {{ $statistics->appends(request()->except('page'))->links() }}
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" alt="No Data"
                    style="width: 150px; opacity: 0.5;">
                <h4 class="mt-3 text-gray-800">Data Tidak Ditemukan</h4>
                <p class="text-muted">Silakan gunakan filter di atas untuk menampilkan statistik peminjaman.</p>
            </div>
        @endif

        {{-- Modal Detail Peminjaman (FIXED) --}}
        <div class="modal fade" id="detailPeminjamanModal" tabindex="-1" aria-labelledby="detailPeminjamanModalLabel"
            >
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header ">
                        <h5 class="modal-title fw-bold" id="detailPeminjamanModalLabel">
                            <i class="fas fa-list-ul me-2"></i> Detail Peminjaman
                        </h5>
                        <div class="ms-auto d-flex align-items-center">


                            <button type="button" class="btn-close btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted fw-bold">Periode: <span id="modal-periode-display"
                                    class=""></span></span>
                        </div>
                        <div id="loadingSpinner" class="text-center py-5">
                            <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-2 fw-bold">Sedang mengambil data...</p>
                        </div>
                        <div id="dataSection" style="display: none;">
                            <div class="card border-0 shadow-sm">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="detailTable">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 5%;">No</th>
                                                <th style="width: 20%;">Nama Peminjam</th>
                                                <th style="width: 15%;">NIM</th>
                                                <th>Detail Transaksi Buku</th>
                                            </tr>
                                        </thead>
                                        <tbody id="detailTbody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div id="modalPagination" class="d-flex justify-content-center mt-4"></div>
                        </div>

                        {{-- Pesan Jika Kosong --}}
                        <div id="emptyMessage" class="alert alert-warning text-center mt-3 shadow-sm border-0"
                            style="display:none;">
                            <i class="fas fa-info-circle me-2"></i> Tidak ada data detail ditemukan.
                        </div>

                    </div>
                    <div class="modal-footer">
                        <a href="#" id="btnExportDetailCsv" class="btn btn-success btn-sm me-2 shadow-sm">
                            <i class="fas fa-file-csv me-1"></i> Export CSV
                        </a>
                        <button type="button" class="btn btn-secondary  btn-sm px-4"
                            data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/locale/id.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const filterTypeSelect = document.getElementById('filter_type');
            const dailyFilterDiv = document.getElementById('dailyFilter');
            const monthlyFilterDiv = document.getElementById('monthlyFilter');

            function toggleFilters() {
                if (filterTypeSelect.value === 'daily') {
                    dailyFilterDiv.style.display = 'block';
                    monthlyFilterDiv.style.display = 'none';
                } else {
                    dailyFilterDiv.style.display = 'none';
                    monthlyFilterDiv.style.display = 'block';
                }
            }
            // Init state
            toggleFilters();
            // Listener
            filterTypeSelect.addEventListener('change', toggleFilters);

            const fullStatistics = @json($fullStatisticsForChart ?? []);
            const filterType = "{{ $filterType ?? 'daily' }}";

            if (fullStatistics.length > 0) {
                const chartLabels = fullStatistics.map(item => moment(item.periode).format(filterType === 'daily' ?
                    'D MMM YYYY' : 'MMM YYYY'));
                const chartDataBooks = fullStatistics.map(item => item.jumlah_peminjaman_buku);
                const chartDataBorrowers = fullStatistics.map(item => item.jumlah_peminjam_unik);

                const ctx = document.getElementById('peminjamanChart').getContext('2d');

                // Gradient Setup
                let gradientBlue = ctx.createLinearGradient(0, 0, 0, 400);
                gradientBlue.addColorStop(0, 'rgba(78, 115, 223, 0.5)');
                gradientBlue.addColorStop(1, 'rgba(78, 115, 223, 0.05)');

                let gradientRed = ctx.createLinearGradient(0, 0, 0, 400);
                gradientRed.addColorStop(0, 'rgba(231, 74, 59, 0.5)');
                gradientRed.addColorStop(1, 'rgba(231, 74, 59, 0.05)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Buku Terpinjam',
                            data: chartDataBooks,
                            borderColor: '#4e73df',
                            backgroundColor: gradientBlue,
                            pointBackgroundColor: '#4e73df',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#4e73df',
                            tension: 0.4, // Smoother curve
                            fill: true,
                            borderWidth: 2
                        }, {
                            label: 'Jumlah Peminjam',
                            data: chartDataBorrowers,
                            borderColor: '#e74a3b',
                            backgroundColor: gradientRed,
                            pointBackgroundColor: '#e74a3b',
                            pointBorderColor: '#fff',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 10,
                                    color: "#858796"
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: "rgb(234, 236, 244)",
                                    borderDash: [2],
                                    drawBorder: false
                                },
                                ticks: {
                                    padding: 10,
                                    color: "#858796"
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8
                                }
                            },
                            tooltip: {
                                backgroundColor: "rgb(255,255,255)",
                                bodyColor: "#858796",
                                titleColor: '#6e707e',
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                padding: 15,
                                callbacks: {
                                    title: function(context) {
                                        const item = fullStatistics[context[0].dataIndex];
                                        return moment(item.periode).format(filterType === 'daily' ?
                                            'dddd, D MMMM YYYY' : 'MMMM YYYY');
                                    }
                                }
                            }
                        }
                    }
                });
            }

            const detailModalElement = document.getElementById('detailPeminjamanModal');
            const detailModal = new bootstrap.Modal(detailModalElement);
            const modalPeriodeDisplay = document.getElementById('modal-periode-display');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const dataSection = document.getElementById('dataSection');
            const detailTbody = document.getElementById('detailTbody');
            const modalPaginationContainer = document.getElementById('modalPagination');
            let currentDetailUrl = '';

            document.querySelectorAll('.view-detail-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const periode = this.dataset.periode;
                    const filterType = document.getElementById('filter_type').value;

                    const btnExport = document.getElementById('btnExportDetailCsv');
                    // Pastikan route digenerate oleh blade
                    const baseUrlExport = "{{ route('peminjaman.export_detail') }}";
                    btnExport.href =
                        `${baseUrlExport}?periode=${periode}&filter_type=${filterType}`;
                    // Set Label Periode
                    let periodeText = (filterType === 'daily') ?
                        moment(periode).format('D MMMM YYYY') :
                        moment(periode, 'YYYY-MM').format('MMMM YYYY');
                    modalPeriodeDisplay.innerText = periodeText;

                    // Reset UI State sebelum fetch
                    loadingSpinner.style.display = 'block'; // Tampilkan Spinner
                    dataSection.style.display = 'none'; // Sembunyikan Tabel
                    emptyMessage.style.display = 'none'; // Sembunyikan Pesan Kosong
                    detailTbody.innerHTML = ''; // Bersihkan tabel lama

                    // Fetch Data
                    const url =
                        `{{ route('peminjaman.get_detail') }}?periode=${periode}&filter_type=${filterType}`;
                    fetchDetailData(url);

                    detailModal.show();
                });
            });

            modalPaginationContainer.addEventListener('click', function(event) {
                if (event.target.tagName === 'A' && event.target.classList.contains('page-link')) {
                    event.preventDefault();
                    const url = event.target.href;
                    if (url) {
                        fetchDetailData(url);
                    }
                }
            });

            async function fetchDetailData(url) {
                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    const result = await response.json();

                    // Panggil fungsi render
                    renderModalContent(result);

                    // SUKSES: Sembunyikan spinner, Tampilkan Data
                    loadingSpinner.style.display = 'none';

                    if (result.data && result.data.length > 0) {
                        dataSection.style.display = 'block';
                    } else {
                        emptyMessage.style.display = 'block';
                    }

                } catch (error) {
                    console.error('Error:', error);
                    loadingSpinner.style.display = 'none';
                    // Tampilkan error di dalam tbody jika perlu, atau alert
                    detailTbody.innerHTML =
                        `<tr><td colspan="4" class="text-center text-danger fw-bold">Gagal memuat data.</td></tr>`;
                    dataSection.style.display = 'block';
                }
            }

            function renderModalContent(result) {
                if (result.data && result.data.length > 0) {
                    let allRowsHtml = '';
                    result.data.forEach((peminjam, index) => {
                        const detailBukuHtml = peminjam.detail_buku.map(buku => {
                            let badge = buku.tipe_transaksi === 'issue' ?
                                '<span class="badge bg-primary ms-1"><i class="fas fa-arrow-up me-1"></i>Pinjam</span>' :
                                (buku.tipe_transaksi === 'renew' ?
                                    '<span class="badge bg-warning text-dark ms-1"><i class="fas fa-sync me-1"></i>Perpanjang</span>' :
                                    '<span class="badge bg-success ms-1"><i class="fas fa-arrow-down me-1"></i>Kembali</span>'
                                );

                            return `<div class="my-1 py-1">
                            <i class="fas fa-book text-muted me-1"></i> ${buku.judul_buku} ${badge}
                            <small class="text-muted ms-1">(${buku.waktu_transaksi})</small>
                        </div>`;
                        }).join('');

                        allRowsHtml += `
                <tr>
                    <td class="text-center fw-bold text-secondary">${result.from + index}</td>
                    <td>${peminjam.nama_peminjam}</td>
                    <td><span>${peminjam.nim}</span></td>
                    <td class="p-0"><div class="px-2 py-2 small">${detailBukuHtml}</div></td>
                </tr>`;
                    });

                    detailTbody.innerHTML = allRowsHtml;

                    let paginationHtml = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
                    if (result.links) {
                        result.links.forEach(link => {
                            // Cek jika bukan separator (...)
                            if (link.url || link.label === '...') {
                                // Abaikan label "..." jika tidak ada url, tapi biasanya kita hide
                            }

                            if (link.url && link.label.indexOf('...') === -1) {
                                let label = link.label;

                                // LOGIKA BARU: Cek apakah ini tombol Previous atau Next
                                if (label.includes('Previous') || label.includes('&laquo;')) {
                                    // OPSI 1: Pakai Teks Bersih
                                    label = 'Previous';
                                    // OPSI 2: Pakai Icon (Lebih disarankan)
                                    // label = '<i class="fas fa-chevron-left"></i>';
                                } else if (label.includes('Next') || label.includes('&raquo;')) {
                                    // OPSI 1: Pakai Teks Bersih
                                    label = 'Next';
                                    // OPSI 2: Pakai Icon (Lebih disarankan)
                                    // label = '<i class="fas fa-chevron-right"></i>';
                                }

                                // Render HTML (perbaikan active class logic sedikit agar aman)
                                let activeClass = link.active ? 'active' : '';

                                paginationHtml += `
                <li class="page-item ${activeClass}">
                    <a class="page-link" href="${link.url}">${label}</a>
                </li>`;
                            }
                        });
                    }
                    paginationHtml += '</ul></nav>';
                    modalPaginationContainer.innerHTML = paginationHtml;
                }
            }

            const exportCsvBtn = document.getElementById('exportCsvBtn');

            if (exportCsvBtn) {
                exportCsvBtn.addEventListener('click', function() {
                    const dataToExport = @json($fullStatisticsForChart ?? []);

                    if (!dataToExport || dataToExport.length === 0) {
                        alert("Tidak ada data untuk diekspor.");
                        return;
                    }

                    let csv = [];
                    const delimiter = ';';
                    let title = "Laporan Statistik Peminjaman";

                    if (filterType === 'daily') {
                        const startDate = document.getElementById('start_date').value;
                        const endDate = document.getElementById('end_date').value;
                        title += ` Harian: ${startDate} sampai ${endDate}`;
                    } else {
                        const startYear = document.getElementById('start_year').value;
                        const endYear = document.getElementById('end_year').value;
                        title += ` Bulanan Tahun ${startYear} s.d. ${endYear}`;
                    }
                    csv.push(title);
                    csv.push('');

                    // Header tabel
                    let headers = ['No', 'Periode', 'Jumlah Buku Terpinjam', 'Jumlah Peminjam'];
                    csv.push(headers.join(delimiter));

                    // Tambahkan data baris per baris
                    dataToExport.forEach((row, index) => {
                        let periode;
                        if (filterType === 'daily') {
                            periode = moment(row.periode).format('DD MMMM YYYY');
                        } else {
                            periode = moment(row.periode, 'YYYY-MM').format('MMMM YYYY');
                        }

                        let rowData = [
                            index + 1,
                            `"${periode}"`,
                            row.jumlah_peminjaman_buku,
                            row.jumlah_peminjam_unik
                        ];
                        csv.push(rowData.join(delimiter));
                    });

                    const csvString = csv.join('\n');
                    const BOM = "\uFEFF";
                    const blob = new Blob([BOM + csvString], {
                        type: 'text/csv;charset=utf-8;'
                    });

                    const link = document.createElement("a");
                    let fileName = 'statistik_peminjaman';

                    if (filterType === 'daily') {
                        const startDate = document.getElementById('start_date').value;
                        const endDate = document.getElementById('end_date').value;
                        fileName += `_harian_${startDate}_sampai_${endDate}`;
                    } else {
                        const startYear = document.getElementById('start_year').value;
                        const endYear = document.getElementById('end_year').value;
                        fileName += `_bulanan_${startYear}-${endYear}`;
                    }

                    fileName += `_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.csv`;

                    if (navigator.msSaveBlob) {
                        navigator.msSaveBlob(blob, fileName);
                    } else {
                        link.href = URL.createObjectURL(blob);
                        link.download = fileName;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(link.href);
                    }
                });
            }
        });
    </script>
@endsection
