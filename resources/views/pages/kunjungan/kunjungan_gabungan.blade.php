@extends('layouts.app')

@section('title', 'Statistik Kunjungan Perpustakaan')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        /* --- MODERN DASHBOARD STYLING --- */
        :root {
            --primary-soft: rgba(13, 110, 253, 0.1);
            --success-soft: rgba(25, 135, 84, 0.1);
            --warning-soft: rgba(255, 193, 7, 0.1);
            --info-soft: rgba(13, 202, 240, 0.1);
        }

        }

        /* --- DATA TABLES --- */
        /* Copy overrides from FakultasTable to ensure visibility */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 6px;
            padding: 4px 8px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #0d6efd !important;
            color: white !important;
            border: 1px solid #0d6efd !important;
            border-radius: 50%;
        }
        div.dataTables_wrapper div.dataTables_length select {
            width: auto;
            display: inline-block;
            margin: 0 0.5rem; /* Add spacing around the dropdown */
        }
        div.dataTables_wrapper div.dataTables_info {
            padding-top: 0; /* Align info text */
        }
        /* Card Styling */
        .card {
            border: none;
            border-radius: 12px !important;
            /* Paksa radius */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background-color: var(--bs-body-bg);
            color: var(--text-dark);
            overflow: hidden !important;
            /* KUNCI PERBAIKAN: Memotong sudut header yang siku-siku */
        }

        /* Header Putih di Light Mode */
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--text-dark);
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }

        /* Icon Box */
        .icon-box {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.25rem;
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

        /* Progress Bar */
        .progress {
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }

        /* --- DARK MODE ADAPTATION --- */
        body.dark-mode .card {
            background-color: #1e1e2d;
            border: 1px solid #2b2b40;
            color: #ffffff;
            /* Pastikan border radius tetap ada di dark mode */
            border-radius: 12px !important;
            overflow: hidden !important;
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

        body.dark-mode .progress {
            background-color: #ffffff;
        }

        body.dark-mode .text-body {
            color: #ffffff !important;
        }

        /* --- DATA TABLES OVERRIDES (COPIED FROM FAKULTAS TABLE) --- */
        #myTable,
        .dataTables_info,
        .dataTables_length label,
        .dataTables_filter label {
            color: var(--text-dark) !important;
        }

        /* Memperbaiki baris tabel (TD) agar teksnya terlihat */
        #myTable tbody td {
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
        
        /* Pagination Buttons */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--text-dark) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: var(--text-light) !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-4 py-4">

        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div
                        class="card-body p-4 bg-primary bg-gradient text-white d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-clipboard-list me-2"></i>Statistik Kunjungan Perpustakaan
                            </h3>
                            <p class="mb-0 opacity-75">
                                Rekapitulasi aktivitas kunjungan anggota di semua titik layanan.
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-chart-pie fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FILTER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header  border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold text-primary"><i class="fas fa-filter me-1"></i> Filter Data</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('kunjungan.keseluruhan') }}"
                            class="row g-3 align-items-end">
                            {{-- Tipe Filter --}}
                            <div class="col-md-2">
                                <label for="filter_type" class="form-label small text-muted fw-bold">Tipe Laporan</label>
                                <select name="filter_type" id="filter_type" class="form-select border-0  fw-semibold">
                                    <option value="yearly" {{ $filterType == 'yearly' ? 'selected' : '' }}>Rekap Bulanan
                                    </option>
                                    <option value="date_range" {{ $filterType == 'date_range' ? 'selected' : '' }}>Rekap
                                        Harian</option>
                                </select>
                            </div>

                            {{-- Filter Tahunan --}}
                            <div class="col-md-4 filter-input" id="yearlyFilter" style="display: none;">
                                <label class="form-label small text-muted fw-bold">Rentang Tahun</label>
                                <div class="input-group">
                                    <select name="start_year" class="form-select border-0 ">
                                        @for ($y = date('Y'); $y >= date('Y') - 10; $y--)
                                            <option value="{{ $y }}" {{ $startYear == $y ? 'selected' : '' }}>
                                                {{ $y }}</option>
                                        @endfor
                                    </select>
                                    <span class="input-group-text  border-0 text-muted">s/d</span>
                                    <select name="end_year" class="form-select border-0 ">
                                        @for ($y = date('Y'); $y >= date('Y') - 10; $y--)
                                            <option value="{{ $y }}" {{ $endYear == $y ? 'selected' : '' }}>
                                                {{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            {{-- Filter Harian --}}
                            <div class="col-md-4 filter-input" id="date_rangeFilter" style="display: none;">
                                <label class="form-label small text-muted fw-bold">Rentang Tanggal</label>
                                <div class="input-group">
                                    <input type="date" name="start_date" class="form-control border-0 "
                                        value="{{ $startDate }}">
                                    <span class="input-group-text  border-0 text-muted">s/d</span>
                                    <input type="date" name="end_date" class="form-control border-0 "
                                        value="{{ $endDate }}">
                                </div>
                            </div>

                            {{-- Filter Lokasi --}}
                            <div class="col-md-3">
                                <label for="lokasi" class="form-label small text-muted fw-bold">Lokasi</label>
                                <select name="lokasi" id="lokasi" class="form-select border-0 ">
                                    <option value="">Semua Lokasi</option>
                                    @foreach ($lokasiMapping as $lokasi)
                                        <option value="{{ $lokasi }}"
                                            {{ $selectedLokasi == $lokasi ? 'selected' : '' }}>{{ $lokasi }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-auto ms-auto">
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                    <i class="fas fa-search me-1"></i> Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if (request()->has('filter_type'))
            @if (!$dataHasil->isEmpty())

                {{-- 3. STATISTIK CARDS --}}
                <div class="row g-4 mb-4">
                    {{-- Card Total --}}
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100 hover-lift">
                            <div class="card-body p-4 d-flex align-items-center">
                                <div class="icon-box bg-primary-soft me-3 rounded-circle"
                                    style="width: 60px; height: 60px;">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Kunjungan</h6>
                                    <h2 class="fw-bold mb-0">{{ number_format($totalKunjungan) }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card Rerata --}}
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100 hover-lift">
                            <div class="card-body p-4 d-flex align-items-center">
                                <div class="icon-box bg-success-soft me-3 rounded-circle"
                                    style="width: 60px; height: 60px;">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted text-uppercase small fw-bold mb-1">Rerata /
                                        {{ $filterType == 'yearly' ? 'Bulan' : 'Hari' }}</h6>
                                    <h2 class="fw-bold mb-0">{{ number_format($rerataKunjungan, 1) }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card Top Lokasi --}}
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header pt-3 pb-2 border-0">
                                <h6 class="fw-bold small text-uppercase mb-0">
                                    <i class="fas fa-map-marker-alt me-1 text-danger"></i> Lokasi
                                </h6>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-column gap-3 mt-2">
                                    @forelse ($topLokasi as $lokasi => $jumlah)
                                        @php
                                            $iconColor = $loop->first
                                                ? '#FFD700'
                                                : ($loop->iteration == 2
                                                    ? '#C0C0C0'
                                                    : '#CD7F32');
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small fw-medium">
                                                @if ($loop->iteration <= 3)
                                                    <i class="fas fa-crown me-2" style="color: {{ $iconColor }}"></i>
                                                @else
                                                    <span class="me-4"></span>
                                                @endif
                                                {{ \Illuminate\Support\Str::limit($lokasiMapping[$lokasi] ?? $lokasi, 25) }}
                                            </span>
                                            <span
                                                class="badge border rounded-pill text-body fw-normal px-3">{{ number_format($jumlah) }}</span>
                                        </div>
                                    @empty
                                        <span class="text-muted fst-italic small">Data lokasi tidak tersedia</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. GRAFIK SECTION --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-0 pt-4 px-4">
                                <h5 class="fw-bold mb-0">Tren Kunjungan</h5>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div style="height: 350px; width: 100%;">
                                    <canvas id="kunjunganChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. TABEL DATA SECTION --}}
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center bg-white border-bottom">
                                <h6 class="fw-bold m-0 text-primary">
                                    <i class="fas fa-table me-2"></i>Rincian Data
                                </h6>
                                <button id="exportCsvBtn" class="btn btn-success btn-sm fw-bold shadow-sm">
                                    <i class="fas fa-file-csv me-2"></i>Export CSV
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <!-- Custom Control Bar -->
                                <div class="p-4 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <!-- Length Change -->
                                        <div class="d-flex align-items-center">
                                            <label class="me-2 text-muted fw-bold small">Tampilkan</label>
                                            <select id="lengthMenu" class="form-select form-select-sm shadow-sm border-secondary-subtle" style="width: 80px; border-radius: 6px;">
                                                <option value="10">10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                                <option value="-1">Semua</option>
                                            </select>
                                            <label class="ms-2 text-muted fw-bold small">Entri</label>
                                        </div>

                                        <!-- Search -->
                                        <div class="input-group" style="width: 300px;">
                                            <span class="input-group-text border-end-0 border-secondary-subtle"><i class="fas fa-search text-muted"></i></span>
                                            <input type="text" id="searchInput" class="form-control border-start-0 ps-0 border-secondary-subtle shadow-sm" placeholder="Cari data...">
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <!-- Table starts here -->
                                    <table id="myTable" class="table table-hover align-middle mb-0 unified-table">
                                        <thead class="">
                                            <tr>
                                                <th class="py-3 px-4 border-bottom-0" width="5%">No</th>
                                                <th class="py-3 px-4 border-bottom-0">
                                                    {{ $filterType == 'yearly' ? 'Bulan' : 'Tanggal' }}</th>
                                                <th class="py-3 px-4 border-bottom-0 text-center">Jumlah</th>
                                                <th class="py-3 px-4 border-bottom-0" width="40%">Visualisasi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php($max = $maxKunjungan > 0 ? $maxKunjungan : 1)
                                            @forelse ($dataHasil as $rekap)
                                                @php($persentase = ($rekap->jumlah / $max) * 100)
                                                <tr>
                                                    <td class="px-4 text-center text-muted fw-bold">
                                                        {{ $loop->iteration }}</td>
                                                    <td class="px-4 fw-medium text-body">
                                                        @if ($filterType == 'yearly')
                                                            {{ \Carbon\Carbon::parse($rekap->periode)->locale('id')->isoFormat('MMMM YYYY') }}
                                                        @else
                                                            {{ \Carbon\Carbon::parse($rekap->periode)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4 text-center">
                                                        <span
                                                            class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill">{{ number_format($rekap->jumlah) }}</span>
                                                    </td>
                                                    <td class="px-4">
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                                <div class="progress-bar bg-primary" role="progressbar"
                                                                    style="width: {{ $persentase }}%;"
                                                                    aria-valuenow="{{ $persentase }}" aria-valuemin="0"
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <span class="ms-2 small text-muted fw-bold"
                                                                style="width: 40px; text-align: right;">{{ round($persentase) }}%</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-5">
                                                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png"
                                                            width="80" class="mb-3 opacity-50" alt="No Data">
                                                        <p class="text-muted mb-0">Tidak ada data kunjungan yang cocok.</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if (!$dataHasil->isEmpty())
                                            <tfoot class="">
                                                <tr>
                                                    <td colspan="2" class="px-4 py-3 fw-bold text-end text-body">Total
                                                        Keseluruhan</td>
                                                    <td class="px-4 py-3 fw-bold text-center text-primary fs-5">
                                                        {{ number_format($dataHasil->sum('jumlah')) }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                            <!-- Pagination handled by DataTables -->
                        </div>
                    </div>
                </div>
            @else
                {{-- EMPTY STATE --}}
                <div class="row justify-content-center mt-5">
                    <div class="col-md-6">
                        <div class="text-center p-5 border-0  rounded-4">
                            <i class="fas fa-search fa-3x text-muted mb-3 opacity-50"></i>
                            <h5 class="fw-bold text-body">Data Tidak Ditemukan</h5>
                            <p class="text-muted">Coba sesuaikan filter tanggal atau lokasi Anda.</p>
                        </div>
                    </div>
                </div>
            @endif
        @else
            {{-- INITIAL STATE --}}
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm text-center p-5 rounded-4">
                        <div class="card-body">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-4 mb-3">
                                <i class="fas fa-chart-bar fa-3x text-primary"></i>
                            </div>
                            <h4 class="fw-bold text-body">Mulai Analisis Data</h4>
                            <p class="text-muted mb-0">Silakan pilih parameter filter di atas lalu klik tombol
                                <strong>"Tampilkan"</strong>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    </div>
@endsection

@push('scripts')
    <!-- jQuery & DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js & Moment.js -->
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            // --- DATATABLES INIT ---
            if ($('#myTable').length) {
                var table = $('#myTable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json",
                        "thousands": ".",
                        "decimal": ","
                    },
                    "paging": true,
                    "lengthChange": true,
                    "searching": true,
                    "ordering": true,
                    "info": true,
                    "autoWidth": false,
                    "responsive": true,
                // DOM Layout: Table (rt), Info (i) & Pagination (p) at bottom
                // Removed 'l' and 'f' because we use custom controls
                // Added px-4 py-3 for padding so it doesn't touch the edges
                "dom": 'rt<"d-flex justify-content-between align-items-center px-4 py-3"ip>', 
                "columnDefs": [{
                    "searchable": false,
                    "orderable": false,
                    "targets": 0 // Kolom No
                }, {
                    "searchable": false,
                    "orderable": false,
                    "targets": 3 // Kolom Visualisasi
                }],
                "order": [], 
            });

            // Custom Search Input Binding
            $('#searchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Custom Length Change Binding
            $('#lengthMenu').on('change', function() {
                table.page.len(this.value).draw();
            });
        }

            // --- FILTER LOGIC ---
            const filterTypeSelect = document.getElementById('filter_type');
            const filterInputs = document.querySelectorAll('.filter-input');

            function handleFilterChange() {
                filterInputs.forEach(div => div.style.display = 'none');
                filterInputs.forEach(div => div.querySelectorAll('input, select').forEach(input => input.disabled = true));
                
                if(filterTypeSelect) {
                    const selectedFilterId = filterTypeSelect.value + 'Filter';
                    const activeFilterDiv = document.getElementById(selectedFilterId);
                    if (activeFilterDiv) {
                        activeFilterDiv.style.display = 'block';
                        activeFilterDiv.querySelectorAll('input, select').forEach(input => input.disabled = false);
                    }
                }
            }
            if (filterTypeSelect) {
                filterTypeSelect.addEventListener('change', handleFilterChange);
                handleFilterChange();
            }

            // --- CSV EXPORT LOGIC ---
            const exportCsvButton = document.getElementById('exportCsvBtn');
            if (exportCsvButton) {
                exportCsvButton.addEventListener('click', function() {
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengekspor...';
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('export', 'csv');
                    window.location.href = currentUrl.toString();

                    setTimeout(() => {
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-file-csv me-2"></i>Export CSV';
                    }, 3000);
                });
            }

            // --- CHART LOGIC ---
            const chartData = @json($chartData ?? []);
            const filterType = '{{ $filterType }}';
            if (Object.keys(chartData).length > 0) {
                const ctx = document.getElementById('kunjunganChart');
                if(ctx) {
                    const ctx2d = ctx.getContext('2d');
                    const labels = Object.keys(chartData).map(periode => {
                        let format = filterType === 'yearly' ? 'MMMM YYYY' : 'D MMM YYYY';
                        return moment(periode).format(format);
                    });
                    const data = Object.values(chartData);

                    new Chart(ctx2d, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Total Kunjungan',
                                data: data,
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                borderWidth: 3,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#0d6efd',
                                pointHoverBackgroundColor: '#0d6efd',
                                pointHoverBorderColor: '#ffffff',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: 'rgba(33, 37, 41, 0.95)',
                                    padding: 12,
                                    titleFont: { size: 13 },
                                    bodyFont: { size: 13 },
                                    displayColors: false,
                                    callbacks: {
                                        label: function(context) {
                                            return context.parsed.y + ' Kunjungan';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#f0f2f5', drawBorder: false },
                                    ticks: { font: { size: 11 }, color: '#6c757d' }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 11 }, color: '#6c757d' }
                                }
                            }
                        }
                    });
                }
            }
        });
    </script>
@endpush
