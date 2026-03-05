@extends('layouts.app')

@section('title', 'Statistik Peminjaman per Program Studi')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <style>
        /* Pastikan teks tabel dan info DataTable otomatis putih/terang */
        #yajraTable,
        .dataTables_info,
        .dataTables_length label,
        .dataTables_filter label {
            color: var(--text-dark) !important;
        }

        /* Memperbaiki baris tabel (TD) agar teksnya terlihat */
        #yajraTable tbody td {
            color: var(--text-dark) !important;
        }

        /* Memperbaiki Dropdown & Input agar tidak ada background putih yang menabrak teks */
        .dataTables_length select,
        .dataTables_filter input {
            background-color: var(--bs-tertiary-bg) !important;
            color: var(--text-dark) !important;
            border: 1px solid var(--bs-border-color) !important;
        }

        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--text-dark) !important;
        }

        /* Perbaiki input search agar background tidak putih terang */
        .dataTables_filter input,
        .dataTables_length select {
            background-color: var(--sidebar-bg) !important;
            color: var(--text-dark) !important;
            border: 1px solid var(--bs-border-color) !important;
        }

        #yajraTable {
            --bs-table-bg: transparent;
            --bs-table-accent-bg: transparent;
            --bs-table-striped-bg: transparent;
            --bs-table-hover-bg: transparent;
            color: var(--text-dark) !important;
        }

        #yajraTable thead th {
            background-color: transparent !important;
            color: var(--text-dark) !important;
            border-bottom: 2px solid var(--bs-border-color);
            letter-spacing: 0.5px;
        }

        #yajraTable tbody td {
            background-color: transparent !important;
            color: var(--text-dark) !important;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--bs-border-color);
            vertical-align: middle;
        }

        #yajraTable tbody tr:hover {
            background-color: transparent !important;
            cursor: default;
        }

        /* Card mengikuti warna body/tema */
        .card {
            background-color: var(--bs-body-bg);
            border-color: var(--bs-border-color);
        }

        .card-header {
            background-color: var(--bs-body-bg);
            border-bottom-color: var(--bs-border-color);
        }

        /* Padding Kanan Kiri (Search, Length, Info, Pagination) */
        div.dataTables_wrapper div.dataTables_length {
            padding-left: 1.5rem !important;
            padding-top: 1rem;
        }

        div.dataTables_wrapper div.dataTables_filter {
            padding-right: 1.5rem !important;
            padding-top: 1rem;
        }

        div.dataTables_wrapper div.dataTables_info {
            padding-top: 1.5rem !important;
            padding-left: 1.5rem !important;
        }

        div.dataTables_wrapper div.dataTables_paginate {
            padding-top: 1.5rem !important;
            padding-right: 1.5rem !important;
            padding-bottom: 1rem;
        }

        /* Modern Styling Components */
        .icon-box {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
        .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0; }

        /* Select2 Dark Mode Support */
        body.dark-mode .select2-container--bootstrap-5 .select2-selection {
            background-color: #1b1b29 !important;
            border-color: #2b2b40 !important;
            color: #ffffff !important;
        }
        body.dark-mode .select2-container--bootstrap-5 .select2-selection__rendered {
            color: #ffffff !important;
        }
        body.dark-mode .select2-container--bootstrap-5 .select2-dropdown {
            background-color: #1e1e2d !important;
            border-color: #2b2b40 !important;
        }
        body.dark-mode .select2-container--bootstrap-5 .select2-search__field {
            background-color: #334155 !important;
            border-color: #2b2b40 !important;
            color: #ffffff !important;
        }
        body.dark-mode .select2-results__option {
            color: #ffffff !important;
        }
        body.dark-mode .select2-results__option--highlighted {
            background-color: #0d6efd !important;
            color: white !important;
        }

    </style>
@endpush

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">

        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden rounded-4">
                    <div
                        class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0 text-center text-md-start">
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-chart-pie me-2"></i>Statistik Sirkulasi Prodi
                            </h3>
                            <p class="mb-0 opacity-75">
                                Analisis data peminjaman dan pengembalian berdasarkan program studi.
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-university fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FILTER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header border-bottom-0">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-filter me-2"></i> Filter Data</h6>
                    </div>
                    <div class="card-body pt-0">
                        <form method="GET" action="{{ route('peminjaman.prodi') }}"
                            class="row g-3 align-items-end" id="filterForm">
                            <div class="col-md-2">
                                <label for="filter_type" class="form-label small text-muted fw-bold">Mode Tampilan</label>
                                <select name="filter_type" id="filter_type" class="form-select border-0  fw-bold">
                                    <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>
                                        Harian</option>
                                    <option value="monthly" {{ ($filterType ?? '') == 'monthly' ? 'selected' : '' }}>Bulanan
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4" id="dailyFilter"
                                style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                                <label class="form-label small text-muted fw-bold">Rentang Tanggal</label>
                                <div class="input-group">
                                    <input type="date" name="start_date" id="start_date" class="form-control border-0 "
                                        value="{{ $startDate }}">
                                    <span class="input-group-text border-0  text-muted">s/d</span>
                                    <input type="date" name="end_date" id="end_date" class="form-control border-0 "
                                        value="{{ $endDate }}">
                                </div>
                            </div>

                            <div class="col-md-4" id="monthlyFilter"
                                style="{{ ($filterType ?? '') == 'monthly' ? '' : 'display: none;' }}">
                                <label class="form-label small text-muted fw-bold">Rentang Tahun</label>
                                <div class="input-group">
                                    <select name="start_year" id="start_year" class="form-select border-0 ">
                                        @for ($y = date('Y'); $y >= date('Y') - 10; $y--)
                                            <option value="{{ $y }}" {{ $startYear == $y ? 'selected' : '' }}>
                                                {{ $y }}</option>
                                        @endfor
                                    </select>
                                    <span class="input-group-text border-0  text-muted">s.d.</span>
                                    <select name="end_year" id="end_year" class="form-select border-0 ">
                                        @for ($y = date('Y'); $y >= date('Y') - 10; $y--)
                                            <option value="{{ $y }}" {{ $endYear == $y ? 'selected' : '' }}>
                                                {{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="selected_prodi" class="form-label small text-muted fw-bold">Program
                                    Studi</label>
                                <select name="selected_prodi" id="selected_prodi" class="form-select border-0 ">
                                    @foreach ($prodiOptions as $prodi)
                                        <option value="{{ $prodi->authorised_value }}"
                                            {{ $selectedProdiCode == $prodi->authorised_value ? 'selected' : '' }}>
                                            {{ $prodi->authorised_value }} - {{ $prodi->lib }} 
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 ms-auto">
                                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                                    <i class="fas fa-search me-1"></i> Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if ($dataExists)
            {{-- 3. STATISTIK CARDS (GAYA MODERN) --}}
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
                    <div class="card border-0 shadow-sm h-100 hover-lift rounded-4">
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
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-header border-bottom-0 pt-4 px-4">
                            <h5 class="fw-bold mb-0">
                                <i class="fas fa-chart-line text-info me-2"></i>Tren Sirkulasi
                            </h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div style="height: 350px; position: relative;">
                                <canvas id="peminjamanProdiChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. TABEL DATA SECTION --}}
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div
                            class="card-header py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                            <h6 class="fw-bold m-0 text-primary">
                                <i class="fas fa-table me-2"></i>Rincian Data Sirkulasi
                            </h6>
                            <button type="button" id="exportCsvBtn"
                                class="btn btn-success btn-sm fw-bold shadow-sm px-3"><i class="fas fa-file-csv me-2"></i> Export CSV
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <!-- Custom Search Input -->
                            <div class="mb-3">
                                <input type="text" class="form-control" id="searchInput" placeholder="Cari periode...">
                            </div>

                            <div class="table-responsive">
                                <table id="yajraTable" class="table table-hover align-middle mb-0 unified-table" style="width:100%">
                                    <thead class="">
                                        <tr>
                                            <th class="text-center py-3 px-2 border-bottom-0" width="5%">No</th>
                                            <th class="py-3 px-4 border-bottom-0">Periode</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Peminjaman</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Perpanjangan</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Pengembalian</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Total Sirkulasi</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($allStatistics as $index => $stat)
                                            <tr>
                                                <td class="text-center text-muted fw-bold">
                                                    {{ $index + 1 }}</td>
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
                                                        class="badge bg-primary-soft text-primary rounded-pill px-3">{{ number_format($stat->jumlah_issue) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-info-soft text-info rounded-pill px-3">{{ number_format($stat->jumlah_renew) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-success-soft text-success rounded-pill px-3">{{ number_format($stat->jumlah_pengembalian) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge border text-body rounded-pill px-3 fw-bold">{{ number_format($stat->total_sirkulasi) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-sm btn-primary shadow-sm view-detail-btn"
                                                        data-bs-toggle="modal" data-bs-target="#detailPeminjamanModal"
                                                        data-periode="{{ $stat->periode }}">
                                                        <i class="fas fa-eye me-1"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
                        <p class="text-muted">Silakan sesuaikan filter Program Studi atau Rentang Waktu di atas.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal Detail --}}
        <div class="modal fade" id="detailPeminjamanModal" tabindex="-1" aria-labelledby="detailPeminjamanModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 py-3">
                        <div>
                            <h5 class="modal-title fw-bold text-body" id="detailPeminjamanModalLabel">
                                <i class="fas fa-list-ul me-2"></i> Detail Sirkulasi
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
                                            <th class="py-3 px-4 border-bottom-0">Detail Buku</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detailTbody"></tbody>
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
                            class="btn btn-success btn-sm fw-bold shadow-sm px-3"><i class="fas fa-file-csv me-2"></i> Export CSV
                        </a>
                        <button type="button" class="btn btn-secondary btn-sm px-4"
                            data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/locale/id.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Select2 Init
            $('#selected_prodi').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Filter Toggle Logic
            const filterTypeSelect = document.getElementById('filter_type');
            const dailyFilter = document.getElementById('dailyFilter');
            const monthlyFilter = document.getElementById('monthlyFilter');

            function toggleFilters() {
                if (filterTypeSelect.value === 'daily') {
                    dailyFilter.style.display = 'block';
                    monthlyFilter.style.display = 'none';
                } else {
                    dailyFilter.style.display = 'none';
                    monthlyFilter.style.display = 'block';
                }
            }
            toggleFilters();
            filterTypeSelect.addEventListener('change', toggleFilters);

            // --- DATATABLES INIT ---
            var table = $('#yajraTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json",
                    "thousands": ".",
                    "decimal": ",",
                    "paginate": {
                        "previous": "Sebelumnya",
                        "next": "Selanjutnya"
                    }
                },
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Semua"]
                ],
                "dom": '<"d-flex justify-content-between mb-3"lp>rt<"d-flex justify-content-between mt-3"ip>',
                "columnDefs": [{
                    "searchable": false,
                    "orderable": false,
                    "targets": [0, 6] // Kolom No dan Aksi tidak di-sort
                }],
                "order": []
            });

            // Bind Custom Search Input
            $('#searchInput').on('keyup change', function() {
                table.search(this.value).draw();
            });

            // Chart Logic
            const fullStatistics = @json($allStatistics ?? []);
            const filterType = "{{ $filterType ?? 'daily' }}";

            if (fullStatistics.length > 0) {
                const chartLabels = fullStatistics.map(item => moment(item.periode).format(filterType === 'daily' ?
                    'D MMM YYYY' : 'MMM YYYY'));
                const ctx = document.getElementById('peminjamanProdiChart').getContext('2d');

                let gradientBlue = ctx.createLinearGradient(0, 0, 0, 400);
                gradientBlue.addColorStop(0, 'rgba(13, 110, 253, 0.5)');
                gradientBlue.addColorStop(1, 'rgba(13, 110, 253, 0.05)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                                label: 'Total Sirkulasi',
                                data: fullStatistics.map(item => item.total_sirkulasi),
                                borderColor: '#0d6efd',
                                backgroundColor: gradientBlue,
                                pointBackgroundColor: '#0d6efd',
                                pointBorderColor: '#fff',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
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
                                display: false
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
                                displayColors: true,
                                callbacks: {
                                    title: function(context) {
                                        const item = fullStatistics[context[0].dataIndex];
                                        return (filterType === 'daily') ? moment(item.periode).format('D MMMM YYYY') : moment(item.periode, 'YYYY-MM').format('MMMM YYYY');
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ... (Bagian JavaScript Modal dan Export sama persis dengan sebelumnya) ...
            // Modal Detail Logic
            const detailModalElement = document.getElementById('detailPeminjamanModal');
            const detailModal = new bootstrap.Modal(detailModalElement);
            const modalPeriodeDisplay = document.getElementById('modal-periode-display');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const dataSection = document.getElementById('dataSection');
            const detailTbody = document.getElementById('detailTbody');
            const modalPaginationContainer = document.getElementById('modalPagination');

            $('#yajraTable').on('click', '.view-detail-btn', function(e) {
                e.preventDefault();
                const periode = this.dataset.periode;
                const filterType = document.getElementById('filter_type').value;
                    const selectedProdiCode = document.getElementById('selected_prodi').value;
                    const btnExport = document.getElementById('btnExportDetailCsv');

                    btnExport.href =
                        `{{ route('peminjaman.export_detail_prodi') }}?periode=${periode}&filter_type=${filterType}&prodi_code=${selectedProdiCode}`;

                    let periodeText = (filterType === 'daily') ? moment(periode).format(
                        'D MMMM YYYY') : moment(periode, 'YYYY-MM').format('MMMM YYYY');
                    modalPeriodeDisplay.innerText = periodeText;

                    loadingSpinner.style.display = 'block';
                    dataSection.style.display = 'none';
                    document.getElementById('emptyMessage').style.display = 'none';
                    detailTbody.innerHTML = '';

                    const url =
                        `{{ route('peminjaman.peminjamDetail') }}?periode=${periode}&filter_type=${filterType}&prodi_code=${selectedProdiCode}`;
                    fetchDetailData(url);
                    detailModal.show();
            });

            modalPaginationContainer.addEventListener('click', function(event) {
                if (event.target.tagName === 'A' && event.target.classList.contains('page-link')) {
                    event.preventDefault();
                    const url = event.target.href;
                    if (url && url !== '#') fetchDetailData(url);
                }
            });

            async function fetchDetailData(url) {
                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('Network error');
                    const result = await response.json();

                    renderModalContent(result);
                    loadingSpinner.style.display = 'none';

                    if (result.data && result.data.data && result.data.data.length > 0) {
                        dataSection.style.display = 'block';
                    } else {
                        document.getElementById('emptyMessage').style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    loadingSpinner.style.display = 'none';
                    detailTbody.innerHTML =
                        `<tr><td colspan="4" class="text-center text-danger">Gagal memuat data.</td></tr>`;
                    dataSection.style.display = 'block';
                }
            }

            function renderModalContent(result) {
                const paginator = result.data;
                if (paginator && paginator.data && paginator.data.length > 0) {
                    let rows = '';
                    paginator.data.forEach((item, index) => {
                        const books = item.buku.map(b => {
                            let badge = b.transaksi === 'issue' ?
                                '<span class="badge bg-primary ms-1">Pinjam</span>' :
                                (b.transaksi === 'renew' ?
                                    '<span class="badge bg-warning text-dark ms-1">Perpanjang</span>' :
                                    '<span class="badge bg-success ms-1">Kembali</span>');
                            return `<div class="mb-1 pb-1 "><i class="fas fa-book text-muted me-1"></i> ${b.title} ${badge} <small class="text-muted">(${b.waktu_transaksi})</small></div>`;
                        }).join('');

                        rows += `<tr>
                            <td class="text-center text-muted fw-bold">${paginator.from + index}</td>
                            <td>${item.nama_peminjam}</td>
                            <td><span class="badge border text-body">${item.cardnumber}</span></td>
                            <td><div class="small">${books}</div></td>
                        </tr>`;
                    });
                    detailTbody.innerHTML = rows;

                    let nav = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
                    if (paginator.prev_page_url) nav +=
                        `<li class="page-item"><a class="page-link" href="${paginator.prev_page_url}">&laquo;</a></li>`;
                    else nav += `<li class="page-item disabled"><span class="page-link">&laquo;</span></li>`;
                    nav +=
                        `<li class="page-item disabled"><span class="page-link">Halaman ${paginator.current_page} dari ${paginator.last_page}</span></li>`;
                    if (paginator.next_page_url) nav +=
                        `<li class="page-item"><a class="page-link" href="${paginator.next_page_url}">&raquo;</a></li>`;
                    else nav += `<li class="page-item disabled"><span class="page-link">&raquo;</span></li>`;
                    nav += '</ul></nav>';
                    modalPaginationContainer.innerHTML = nav;
                }
            }

            // --- CSV Export Logic (Main Table) ---
            const exportCsvBtn = document.getElementById('exportCsvBtn');
            if (exportCsvBtn) {
                exportCsvBtn.addEventListener('click', function() {
                    const dataToExport = @json($allStatistics ?? []);
                    if (!dataToExport || dataToExport.length === 0) {
                        alert("Tidak ada data untuk diekspor.");
                        return;
                    }

                    // --- 1. AMBIL INFORMASI FILTER UNTUK JUDUL ---
                    const prodiSelect = document.getElementById('selected_prodi');
                    const selectedProdiText = prodiSelect.options[prodiSelect.selectedIndex].text;
                    const currentFilterType = document.getElementById('filter_type').value;

                    let periodText = '';
                    let periodSuffixForFile = '';

                    if (currentFilterType === 'daily') {
                        const sDate = document.getElementById('start_date').value;
                        const eDate = document.getElementById('end_date').value;
                        periodText = `Periode ${sDate} s.d. ${eDate}`;
                        periodSuffixForFile = `${sDate}_sd_${eDate}`;
                    } else {
                        const sYear = document.getElementById('start_year').value;
                        const eYear = document.getElementById('end_year').value;
                        periodText = `Tahun ${sYear} - ${eYear}`;
                        periodSuffixForFile = `Tahun_${sYear}-${eYear}`;
                    }

                    // --- 2. SUSUN ISI CSV ---
                    let csv = [];
                    const delimiter = ';';

                    // A. Tambahkan Judul di Dalam File
                    // Gunakan tanda kutip agar jika ada koma/titik koma di nama prodi tidak error
                    csv.push([`"Laporan Statistik Sirkulasi - ${selectedProdiText}"`]);
                    csv.push([`"${periodText}"`]);
                    csv.push([]); // Baris kosong (spacer)

                    // B. Header Tabel
                    csv.push(['No', 'Periode', 'Peminjaman', 'Perpanjangan', 'Pengembalian', 'Total Sirkulasi'].join(
                        delimiter));

                    // C. Data Tabel
                    dataToExport.forEach((row, index) => {
                        let periode = (currentFilterType === 'daily') ?
                            moment(row.periode).format('DD MMMM YYYY') :
                            moment(row.periode, 'YYYY-MM').format('MMMM YYYY');

                        csv.push([
                            index + 1,
                            `"${periode}"`,
                            row.jumlah_issue,
                            row.jumlah_renew,
                            row.jumlah_pengembalian,
                            row.total_sirkulasi
                        ].join(delimiter));
                    });

                    // --- 3. PROSES DOWNLOAD ---
                    const blob = new Blob(["\uFEFF" + csv.join('\n')], {
                        type: 'text/csv;charset=utf-8;'
                    });

                    // Nama File (Bersihkan karakter aneh untuk nama file)
                    const safeProdiName = selectedProdiText.replace(/[^a-z0-9]/gi, '_').replace(/_+/g, '_');
                    const fileName = `Statistik_Sirkulasi_${safeProdiName}_${periodSuffixForFile}.csv`;

                    const link = document.createElement("a");
                    link.href = URL.createObjectURL(blob);
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            }
        });
    </script>
@endpush

