@extends('layouts.app')

@section('title', 'Statistik Peminjaman (Rentang Waktu)')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        /* --- MODERN DASHBOARD STYLING --- */
        :root {
            --primary-soft: rgba(13, 110, 253, 0.1);
            --success-soft: rgba(25, 135, 84, 0.1);
            --warning-soft: rgba(255, 193, 7, 0.1);
            --info-soft: rgba(13, 202, 240, 0.1);
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 12px !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            overflow: hidden !important;
        }

        /* Header Putih di Light Mode */
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--bs-body-color);
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }

        /* Icon Box */
        .icon-box {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.5rem;
        }

        .bg-primary-soft {
            background-color: var(--primary-soft);
            color: #0d6efd;
        }

        .bg-success-soft {
            background-color: var(--success-soft);
            color: #198754;
        }

        .bg-warning-soft {
            background-color: var(--warning-soft);
            color: #ffc107;
        }

        .bg-info-soft {
            background-color: var(--info-soft);
            color: #0dcaf0;
        }

        /* Table Styling */
        .table thead th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        /* --- DARK MODE ADAPTATION --- */
        body.dark-mode .card {
            background-color: #1e1e2d;
            border: 1px solid #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .card-header {
            background-color: #1e293b !important;
            border-bottom-color: #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .text-muted {
            color: #a1a5b7 !important;
        }

        body.dark-mode .table {
            color: #ffffff;
            border-color: #2b2b40;
        }

        body.dark-mode .table thead th {
            background-color: #2b2b40;
            color: #ffffff;
            border-bottom-color: #3f4254;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select,
        body.dark-mode .input-group-text {
            background-color: #1b1b29;
            border-color: #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .text-body {
            color: #ffffff !important;
        }

        body.dark-mode .modal-content {
            background-color: #1e1e2d;
            border-color: #2b2b40;
            color: #fff;
        }

        body.dark-mode .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* --- SELECT2 DARK MODE FIXES --- */
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection {
            background-color: #1b1b29 !important;
            border-color: #2b2b40 !important;
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-dropdown {
            background-color: #1e1e2d !important;
            border-color: #2b2b40 !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            background-color: #334155 !important;
            border-color: #2b2b40 !important;
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__rendered,
        body.dark-mode .select2-results__option {
            color: #ffffff !important;
        }

        body.dark-mode .select2-results__option--highlighted {
            background-color: #0d6efd !important;
            color: white !important;
        }

        body.dark-mode .select2-results__option[aria-selected=true] {
            background-color: rgba(13, 110, 253, 0.2) !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">

        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                    <div
                        class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0">
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-calendar-alt me-2"></i>Statistik Peminjaman
                            </h3>
                            <p class="mb-0 opacity-75">
                                Analisis data sirkulasi peminjaman buku perpustakaan berdasarkan rentang waktu.
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-book-reader fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FILTER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold text-primary"><i class="fas fa-filter me-1"></i> Filter Data</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('peminjaman.peminjaman_rentang_tanggal') }}"
                            class="row g-3 align-items-end" id="filterForm">

                            <div class="col-md-3">
                                <label for="filter_type" class="form-label small text-muted fw-bold">Tampilkan Data</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text  border-0"><i class="fas fa-list text-muted"></i></span>
                                    <select name="filter_type" id="filter_type" class="form-select border-0  fw-semibold">
                                        <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>
                                            Harian (Per Hari)</option>
                                        <option value="monthly" {{ ($filterType ?? '') == 'monthly' ? 'selected' : '' }}>
                                            Bulanan (Per Bulan)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4" id="dailyFilter"
                                style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                                <label class="form-label small text-muted fw-bold">Rentang Tanggal</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" name="start_date" id="start_date" class="form-control border-0 "
                                        value="{{ $startDate ?? \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') }}">
                                    <span class="input-group-text border-0  text-muted">s.d.</span>
                                    <input type="date" name="end_date" id="end_date" class="form-control border-0 "
                                        value="{{ $endDate ?? \Carbon\Carbon::now()->format('Y-m-d') }}">
                                </div>
                            </div>

                            <div class="col-md-4" id="monthlyFilter"
                                style="{{ ($filterType ?? '') == 'monthly' ? '' : 'display: none;' }}">
                                <label class="form-label small text-muted fw-bold">Rentang Tahun</label>
                                <div class="input-group input-group-sm">
                                    @php
                                        $currentYear = date('Y');
                                        $loopStartYear = $currentYear - 10;
                                        $loopEndYear = $currentYear;
                                    @endphp
                                    <select name="start_year" id="start_year" class="form-select border-0 ">
                                        @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                            <option value="{{ $year }}"
                                                {{ ($startYear ?? $currentYear) == $year ? 'selected' : '' }}>
                                                {{ $year }}
                                            </option>
                                        @endfor
                                    </select>
                                    <span class="input-group-text border-0  text-muted">s.d.</span>
                                    <select name="end_year" id="end_year" class="form-select border-0 ">
                                        @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                            <option value="{{ $year }}"
                                                {{ ($endYear ?? $currentYear) == $year ? 'selected' : '' }}>
                                                {{ $year }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2 ms-auto">
                                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">
                                    <i class="fas fa-search me-1"></i> Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if (!empty($statistics) && !$statistics->isEmpty())

            {{-- 3. STATISTIK CARDS --}}
            <div class="row g-3 g-md-4 mb-4">
                {{-- Card 1: Buku Terpinjam --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-primary-soft me-3 rounded-circle">
                                <i class="fas fa-book"></i>
                            </div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Buku Terpinjam</h6>
                                <h2 class="fw-bold mb-0 text-body">{{ number_format($totalBooks) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Total Peminjam --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-info-soft me-3 rounded-circle">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Peminjam</h6>
                                <h2 class="fw-bold mb-0 text-body">{{ number_format($totalBorrowers) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Total Pengembalian (UPDATED) --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-warning-soft me-3 rounded-circle">
                                <i class="fas fa-undo-alt"></i> {{-- Icon changed to Undo/Return --}}
                            </div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Pengembalian</h6>
                                <h2 class="fw-bold mb-0 text-body">{{ number_format($totalReturns) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Rerata --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-success-soft me-3 rounded-circle">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Rerata
                                    {{ ($filterType ?? 'daily') == 'daily' ? 'Harian' : 'Bulanan' }}</h6>
                                <h2 class="fw-bold mb-0 text-body">{{ number_format($rerataPeminjaman, 1) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. CHART SECTION --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-4 px-4">
                            <h5 class="fw-bold mb-0 text-body"><i class="fas fa-chart-area me-2 text-primary"></i>Grafik
                                Tren Peminjaman</h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div style="height: 350px; position: relative;">
                                <canvas id="peminjamanChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. TABEL DATA SECTION --}}
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0 text-primary">
                                <i class="fas fa-table me-2"></i>Rincian Data Peminjaman
                            </h6>
                            <button type="button" id="exportCsvBtn" class="btn btn-success btn-sm fw-bold shadow-sm">
                                <i class="fas fa-file-csv me-1"></i> Export CSV
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="min-width: 600px;">
                                    <thead class="">
                                        <tr>
                                            <th class="text-center py-3 px-4 border-bottom-0" width="5%">No</th>
                                            <th class="py-3 px-4 border-bottom-0">Periode</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Buku Terpinjam</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Total Sirkulasi</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($statistics as $index => $stat)
                                            <tr>
                                                <td class="text-center text-muted fw-bold">
                                                    {{ $statistics->firstItem() + $index }}</td>
                                                <td class="px-4 fw-medium text-body">
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
                                                    <span
                                                        class="badge bg-primary-soft text-primary rounded-pill px-3">{{ $stat->jumlah_peminjaman_buku }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge border text-body rounded-pill px-3">{{ number_format($stat->total_sirkulasi) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary view-detail-btn rounded-pill px-3 shadow-sm"
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
                        <div class="card-footer border-0 py-3">
                            <div class="d-flex justify-content-end">
                                {{ $statistics->appends(request()->except('page'))->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- EMPTY STATE --}}
            <div class="row justify-content-center mt-5">
                <div class="col-12 col-md-6">
                    <div class="text-center p-5 border-0  rounded-4">
                        <i class="fas fa-search fa-3x text-muted mb-3 opacity-50"></i>
                        <h5 class="fw-bold text-body">Data Tidak Ditemukan</h5>
                        <p class="text-muted">Silakan gunakan filter di atas untuk menampilkan statistik peminjaman.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal Detail Peminjaman --}}
        <div class="modal fade" id="detailPeminjamanModal" tabindex="-1" aria-labelledby="detailPeminjamanModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 py-3">
                        <div>
                            <h5 class="modal-title fw-bold text-body" id="detailPeminjamanModalLabel">
                                <i class="fas fa-list-ul me-2"></i> Detail Peminjaman
                            </h5>
                            <span class="text-muted small">Periode: <span id="modal-periode-display"
                                    class="fw-bold text-primary"></span></span>
                        </div>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div id="loadingSpinner" class="text-center py-5">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-2 small fw-bold">Sedang mengambil data...</p>
                        </div>

                        <div id="dataSection" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="detailTable">
                                    <thead class=" sticky-top">
                                        <tr>
                                            <th class="text-center py-3 px-4 border-bottom-0" style="width: 5%;">No</th>
                                            <th class="py-3 px-4 border-bottom-0" style="width: 20%;">Nama Peminjam</th>
                                            <th class="py-3 px-4 border-bottom-0" style="width: 15%;">NIM</th>
                                            <th class="py-3 px-4 border-bottom-0">Detail Transaksi Buku</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detailTbody">
                                        {{-- Injected via JS --}}
                                    </tbody>
                                </table>
                            </div>
                            <div id="modalPagination" class="d-flex justify-content-center py-3  border-light">
                            </div>
                        </div>

                        <div id="emptyMessage" class="text-center py-5" style="display:none;">
                            <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Tidak ada data detail ditemukan.</p>
                        </div>
                    </div>
                    <div class="modal-footer border-0 py-3">
                        <a href="#" id="btnExportDetailCsv"
                            class="btn btn-success btn-sm me-2 shadow-sm rounded-pill px-4">
                            <i class="fas fa-file-csv me-1"></i> Export CSV
                        </a>
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4"
                            data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPT ASLI (TIDAK DIUBAH) --}}
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
            toggleFilters();
            filterTypeSelect.addEventListener('change', toggleFilters);

            // --- CHART LOGIC (DIPERBAIKI) ---
            const fullStatistics = @json($fullStatisticsForChart ?? []);
            const filterType = "{{ $filterType ?? 'daily' }}";

            if (fullStatistics.length > 0) {
                // 1. Siapkan Data
                const chartLabels = fullStatistics.map(item => moment(item.periode).format(filterType === 'daily' ?
                    'D MMM YYYY' : 'MMM YYYY'));

                const chartDataBooks = fullStatistics.map(item => item.jumlah_peminjaman_buku);
                const chartDataReturns = fullStatistics.map(item => item.jumlah_pengembalian);
                const chartDataBorrowers = fullStatistics.map(item => item
                    .jumlah_peminjam_unik); // DATA PEMINJAM DITAMBAHKAN

                const ctx = document.getElementById('peminjamanChart').getContext('2d');

                // 2. Setup Gradient Warna
                // Biru (Peminjaman)
                let gradientBlue = ctx.createLinearGradient(0, 0, 0, 400);
                gradientBlue.addColorStop(0, 'rgba(13, 110, 253, 0.5)');
                gradientBlue.addColorStop(1, 'rgba(13, 110, 253, 0.05)');

                // Kuning (Pengembalian)
                let gradientYellow = ctx.createLinearGradient(0, 0, 0, 400);
                gradientYellow.addColorStop(0, 'rgba(255, 193, 7, 0.5)');
                gradientYellow.addColorStop(1, 'rgba(255, 193, 7, 0.05)');

                // Cyan/Info (Peminjam Unik)
                let gradientInfo = ctx.createLinearGradient(0, 0, 0, 400);
                gradientInfo.addColorStop(0, 'rgba(13, 202, 240, 0.5)');
                gradientInfo.addColorStop(1, 'rgba(13, 202, 240, 0.05)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                                label: 'Buku Terpinjam',
                                data: chartDataBooks,
                                borderColor: '#0d6efd', // Primary Blue
                                backgroundColor: gradientBlue,
                                pointBackgroundColor: '#0d6efd',
                                pointBorderColor: '#fff',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
                            },
                            {
                                label: 'Total Pengembalian',
                                data: chartDataReturns,
                                borderColor: '#ffc107', // Warning Yellow
                                backgroundColor: gradientYellow,
                                pointBackgroundColor: '#ffc107',
                                pointBorderColor: '#fff',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
                            },
                            {
                                label: 'Total Peminjam', // DATASET BARU
                                data: chartDataBorrowers,
                                borderColor: '#0dcaf0', // Info Cyan
                                backgroundColor: gradientInfo,
                                pointBackgroundColor: '#0dcaf0',
                                pointBorderColor: '#fff',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2,
                                hidden: false // Set true jika ingin defaultnya sembunyi
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 10,
                                    color: "#858796",
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: "#e9ecef",
                                    borderDash: [2],
                                    drawBorder: false
                                },
                                ticks: {
                                    padding: 10,
                                    color: "#858796",
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8,
                                    padding: 20,
                                    font: {
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: "rgba(33, 37, 41, 0.95)",
                                bodyColor: "#ffffff",
                                titleColor: '#ffffff',
                                titleFont: {
                                    size: 13,
                                    weight: 'bold'
                                },
                                borderColor: '#6c757d',
                                borderWidth: 0,
                                padding: 12,
                                displayColors: true, // Tampilkan kotak warna di tooltip
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
                    const baseUrlExport = "{{ route('peminjaman.export_detail') }}";
                    btnExport.href =
                        `${baseUrlExport}?periode=${periode}&filter_type=${filterType}`;

                    let periodeText = (filterType === 'daily') ? moment(periode).format(
                        'D MMMM YYYY') : moment(periode, 'YYYY-MM').format('MMMM YYYY');
                    modalPeriodeDisplay.innerText = periodeText;

                    loadingSpinner.style.display = 'block';
                    dataSection.style.display = 'none';
                    document.getElementById('emptyMessage').style.display = 'none';
                    detailTbody.innerHTML = '';

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
                    if (url) fetchDetailData(url);
                }
            });

            async function fetchDetailData(url) {
                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    const result = await response.json();
                    renderModalContent(result);
                    loadingSpinner.style.display = 'none';
                    if (result.data && result.data.length > 0) {
                        dataSection.style.display = 'block';
                    } else {
                        document.getElementById('emptyMessage').style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    loadingSpinner.style.display = 'none';
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
                            return `<div class="my-1 py-1"><i class="fas fa-book text-muted me-1"></i> ${buku.judul_buku} ${badge} <small class="text-muted ms-1">(${buku.waktu_transaksi})</small></div>`;
                        }).join('');

                        allRowsHtml += `<tr>
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
                            if (link.url || link.label === '...') {
                                if (link.url && link.label.indexOf('...') === -1) {
                                    let label = link.label;
                                    if (label.includes('Previous') || label.includes('&laquo;')) label =
                                        'Previous';
                                    else if (label.includes('Next') || label.includes('&raquo;')) label =
                                        'Next';
                                    let activeClass = link.active ? 'active' : '';
                                    paginationHtml +=
                                        `<li class="page-item ${activeClass}"><a class="page-link" href="${link.url}">${label}</a></li>`;
                                }
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

                    // Tambahkan detail periode ke judul (opsional, agar lebih informatif)
                    if (filterType === 'daily') {
                        const startDate = document.getElementById('start_date').value;
                        const endDate = document.getElementById('end_date').value;
                        title += ` (Harian: ${startDate} s/d ${endDate})`;
                    } else {
                        const startYear = document.getElementById('start_year').value;
                        const endYear = document.getElementById('end_year').value;
                        title += ` (Bulanan: ${startYear} s/d ${endYear})`;
                    }

                    csv.push(title);
                    csv.push("");
                    const headers = ['No', 'Periode', 'Buku Terpinjam', 'Total Pengembalian',
                        'Total Sirkulasi', 'Total Peminjam'
                    ];
                    csv.push(headers.join(delimiter));

                    // --- ISI DATA ---
                    dataToExport.forEach((row, index) => {
                        let periode;
                        if (filterType === 'daily') {
                            periode = moment(row.periode).format('DD MMMM YYYY');
                        } else {
                            periode = moment(row.periode, 'YYYY-MM').format('MMMM YYYY');
                        }
                        const pinjam = row.jumlah_peminjaman_buku || 0;
                        const kembali = row.jumlah_pengembalian || 0;
                        const sirkulasi = row.total_sirkulasi || (parseInt(pinjam) + parseInt(
                            kembali));
                        const peminjam = row.jumlah_peminjam_unik || 0;

                        let rowData = [
                            index + 1,
                            `"${periode}"`,
                            pinjam,
                            kembali,
                            sirkulasi,
                            peminjam
                        ];

                        csv.push(rowData.join(delimiter));
                    });

                    // --- PROSES DOWNLOAD ---
                    const csvString = csv.join('\n');
                    const BOM = "\uFEFF"; // Agar karakter khusus terbaca benar di Excel
                    const blob = new Blob([BOM + csvString], {
                        type: 'text/csv;charset=utf-8;'
                    });

                    const link = document.createElement("a");
                    let fileName = 'statistik_sirkulasi_' + new Date().toISOString().slice(0, 10).replace(
                        /-/g, '') + '.csv';

                    if (navigator.msSaveBlob) { // IE 10+
                        navigator.msSaveBlob(blob, fileName);
                    } else {
                        link.href = URL.createObjectURL(blob);
                        link.download = fileName;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(link.href); // Bersihkan memory
                    }
                });
            }
        });
    </script>
@endsection
