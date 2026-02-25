@extends('layouts.app')

@section('title', 'Statistik Peminjaman per Fakultas')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-soft: rgba(13, 110, 253, 0.1);
            --success-soft: rgba(25, 135, 84, 0.1);
            --warning-soft: rgba(255, 193, 7, 0.1);
            --info-soft: rgba(13, 202, 240, 0.1);
        }

        .card {
            border: none;
            border-radius: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            overflow: hidden;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--bs-body-color);
            padding-top: 1.25rem;
            padding-bottom: 1.25rem;
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }

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

        .bg-primary-soft { background-color: var(--primary-soft); color: #0d6efd; }
        .bg-success-soft { background-color: var(--success-soft); color: #198754; }
        .bg-warning-soft { background-color: var(--warning-soft); color: #ffc107; }
        .bg-info-soft { background-color: var(--info-soft); color: #0dcaf0; }

        .table thead th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
        }

        /* DataTables Styling */
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
            margin: 0 0.5rem;
        }
        div.dataTables_wrapper div.dataTables_info {
            padding-top: 0;
        }

        body.dark-mode .card { background-color: #1e1e2d; border: 1px solid #2b2b40; color: #ffffff; }
        body.dark-mode .card-header { background-color: #1e293b !important; border-bottom-color: #2b2b40; color: #ffffff; }
        body.dark-mode .text-muted { color: #a1a5b7 !important; }
        body.dark-mode .table { color: #ffffff; border-color: #2b2b40; }
        body.dark-mode .table thead th { background-color: #2b2b40; color: #ffffff; border-bottom-color: #3f4254; }
        body.dark-mode .form-control, body.dark-mode .form-select, body.dark-mode .input-group-text {
            background-color: #1b1b29; border-color: #2b2b40; color: #ffffff;
        }
        body.dark-mode .text-body { color: #ffffff !important; }
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #ffffff !important;
        }
        body.dark-mode .dataTables_wrapper .dataTables_info {
            color: #a1a5b7 !important;
        }
        /* Child Rows (Accordion) Styling */
        td.details-control {
            text-align: center;
            color: #0d6efd;
            cursor: pointer;
            width: 40px;
        }
        tr.shown td.details-control i {
            transform: rotate(90deg);
            transition: transform 0.2s ease;
        }
        td.details-control i {
            transition: transform 0.2s ease;
        }
        .child-table-wrapper {
            background-color: var(--bs-body-bg);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
            margin: 0.5rem 0;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .table-child {
            margin-bottom: 0;
            background-color: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.03);
        }
        .table-child th {
            background-color: var(--primary-soft) !important;
            color: #0d6efd !important;
            font-size: 0.7rem;
            padding: 0.75rem 1rem;
            border-bottom: none;
        }
        .table-child td {
            font-size: 0.85rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }

        body.dark-mode .child-table-wrapper {
            background-color: #1b1b29;
            border-color: #2b2b40;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }
        body.dark-mode .table-child { background-color: #1e1e2d; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.2); }
        body.dark-mode .table-child th { background-color: rgba(13, 110, 253, 0.15) !important; color: #4e73df !important; }
        body.dark-mode .table-child td { border-bottom-color: #2b2b40; color: #a1a5b7; }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">

        {{-- 1. HEADER --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0 text-center text-md-start">
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-university me-2"></i>Statistik Sirkulasi Per Fakultas
                            </h3>
                            <p class="mb-0 opacity-75">
                                Analisis data peminjaman dan pengembalian berdasarkan fakultas.
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-building fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FILTER --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-bottom-0">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-filter me-2"></i> Filter Data</h6>
                    </div>
                    <div class="card-body pt-0">
                        <form method="GET" action="{{ route('peminjaman.peminjaman_fakultas') }}" class="row g-3 align-items-end" id="filterForm">
                            <div class="col-md-2">
                                <label for="filter_type" class="form-label small text-muted fw-bold">Mode Tampilan</label>
                                <select name="filter_type" id="filter_type" class="form-select border-0 fw-bold">
                                    <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>Harian</option>
                                    <option value="monthly" {{ ($filterType ?? '') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                </select>
                            </div>

                            <div class="col-md-3" id="dailyFilter" style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                                <label class="form-label small text-muted fw-bold">Rentang Tanggal</label>
                                <div class="input-group">
                                    <input type="date" name="start_date" id="start_date" class="form-control border-0" value="{{ $startDate }}">
                                    <span class="input-group-text border-0 text-muted">s/d</span>
                                    <input type="date" name="end_date" id="end_date" class="form-control border-0" value="{{ $endDate }}">
                                </div>
                            </div>

                            <div class="col-md-3" id="monthlyFilter" style="{{ ($filterType ?? '') == 'monthly' ? '' : 'display: none;' }}">
                                <label class="form-label small text-muted fw-bold">Rentang Tahun</label>
                                <div class="input-group">
                                    <select name="start_year" id="start_year" class="form-select border-0">
                                        @for ($y = date('Y'); $y >= date('Y') - 10; $y--)
                                            <option value="{{ $y }}" {{ $startYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                    <span class="input-group-text border-0 text-muted">s.d.</span>
                                    <select name="end_year" id="end_year" class="form-select border-0">
                                        @for ($y = date('Y'); $y >= date('Y') - 10; $y--)
                                            <option value="{{ $y }}" {{ $endYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="fakultas" class="form-label small text-muted fw-bold">Fakultas</label>
                                <select name="fakultas" id="fakultas" class="form-select border-0">
                                    <option value="semua" {{ ($selectedFakultas ?? 'semua') == 'semua' ? 'selected' : '' }}>Semua Fakultas</option>
                                    @foreach ($listFakultas as $fak)
                                        <option value="{{ $fak }}" {{ ($selectedFakultas ?? '') == $fak ? 'selected' : '' }}>{{ $fak }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-1 ms-auto">
                                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                                    <i class="fas fa-search me-1"></i> Cari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if ($dataExists)
            {{-- 3. STATISTIK CARDS --}}
            <div class="row g-3 g-md-4 mb-4">
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-primary-soft me-3 rounded-circle"><i class="fas fa-book"></i></div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Peminjaman</h6>
                                <h2 class="fw-bold mb-0 text-body">{{ number_format($totalIssues) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-6 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-info-soft me-3 rounded-circle"><i class="fas fa-sync-alt"></i></div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Perpanjangan</h6>
                                <h2 class="fw-bold mb-0 text-body">{{ number_format($totalRenews) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-6 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-warning-soft me-3 rounded-circle"><i class="fas fa-undo-alt"></i></div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Pengembalian</h6>
                                <h2 class="fw-bold mb-0 text-body">{{ number_format($totalReturns) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-6 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-success-soft me-3 rounded-circle"><i class="fas fa-users"></i></div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Peminjam</h6>
                                <h2 class="fw-bold mb-0 text-body">{{ number_format($totalBorrowers) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-success-soft me-3 rounded-circle"><i class="fas fa-chart-line"></i></div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Rerata {{ ($filterType ?? 'daily') == 'daily' ? 'Harian' : 'Bulanan' }}</h6>
                                <h2 class="fw-bold mb-0 text-body">{{ number_format($rerataPeminjaman, 1) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. CHART --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-4 px-4">
                            <h5 class="fw-bold mb-0 text-body">
                                <i class="fas fa-chart-bar me-2 text-primary"></i>Tren Sirkulasi Per Fakultas
                            </h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div style="height: 350px; position: relative;">
                                <canvas id="peminjamanFakultasChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. TABEL DATA SECTION --}}
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                            <h6 class="fw-bold m-0 text-primary">
                                <i class="fas fa-table me-2"></i>Rincian Data Sirkulasi
                            </h6>
                            <button type="button" id="exportCsvBtn" class="btn btn-success btn-sm fw-bold shadow-sm px-3"><i class="fas fa-file-csv me-2"></i> Export CSV
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
                                <table id="peminjamanFakultasTable" class="table table-hover align-middle mb-0" style="min-width: 700px;">
                                    <thead>
                                        <tr>
                                            <th class="text-center py-3 border-bottom-0" width="3%"></th>
                                            <th class="text-center py-3 px-2 border-bottom-0" width="5%">No</th>
                                            <th class="py-3 px-4 border-bottom-0">Periode</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Peminjaman</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Perpanjangan</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Pengembalian</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Total Sirkulasi</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Peminjam</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tableData as $index => $stat)
                                            <tr data-child="{{ json_encode($stat->prodi_details) }}">
                                                <td class="details-control"><i class="fas fa-chevron-right"></i></td>
                                                <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                                                <td class="px-4 fw-medium text-body">
                                                    @if (($filterType ?? 'daily') == 'daily')
                                                        <i class="far fa-calendar-alt me-2 text-muted"></i>{{ \Carbon\Carbon::parse($stat->periode)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                                    @else
                                                        <i class="far fa-calendar me-2 text-muted"></i>{{ \Carbon\Carbon::createFromFormat('Y-m', $stat->periode)->locale('id')->isoFormat('MMMM Y') }}
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary-soft text-primary rounded-pill px-3">{{ number_format($stat->jumlah_issue) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info-soft text-info rounded-pill px-3">{{ number_format($stat->jumlah_renew) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning-soft text-warning rounded-pill px-3">{{ number_format($stat->jumlah_buku_kembali) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge border text-body rounded-pill px-3 fw-bold">{{ number_format($stat->total_sirkulasi) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info-soft text-info rounded-pill px-3">{{ number_format($stat->jumlah_peminjam_unik) }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Pagination handled by DataTables -->
                    </div>
                </div>
            </div>
        @else
            <div class="row justify-content-center mt-5">
                <div class="col-12 col-md-6">
                    <div class="text-center p-5 border-0 rounded-4">
                        <i class="fas fa-search fa-3x text-muted mb-3 opacity-50"></i>
                        <h5 class="fw-bold text-body">Data Tidak Ditemukan</h5>
                        <p class="text-muted">Silakan sesuaikan filter Fakultas atau Rentang Waktu di atas, lalu klik Cari.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <!-- jQuery & DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- Chart.js & Moment.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/locale/id.js"></script>

    <script>
        $(document).ready(function() {
            // --- Format DataTables Child Row ---
            function formatChildRow(dataString) {
                if (!dataString) return '<div class="text-muted small p-2">Tidak ada detail prodi.</div>';
                
                try {
                    let details = JSON.parse(dataString);
                    if (details.length === 0) return '<div class="text-muted small p-2">Tidak ada detail prodi.</div>';

                    let html = '<div class="child-table-wrapper"><h6 class="fw-bold text-primary mb-3"><i class="fas fa-layer-group me-2"></i>Rincian Program Studi</h6><table class="table table-child w-100">';
                    html += `<thead>
                                <tr>
                                    <th>Program Studi</th>
                                    <th class="text-center">Peminjaman</th>
                                    <th class="text-center">Perpanjangan</th>
                                    <th class="text-center">Pengembalian</th>
                                    <th class="text-center">Total Sirkulasi</th>
                                    <th class="text-center">Peminjam</th>
                                </tr>
                             </thead><tbody>`;
                             
                    details.forEach(item => {
                        html += `<tr>
                                    <td class="fw-medium">${item.prodi}</td>
                                    
                                    <td class="text-center">${item.jumlah_issue.toLocaleString('id-ID')}</td>
                                    <td class="text-center">${item.jumlah_renew.toLocaleString('id-ID')}</td>
                                    <td class="text-center">${item.jumlah_buku_kembali.toLocaleString('id-ID')}</td>
                                    <td class="text-center fw-bold">${item.total_sirkulasi.toLocaleString('id-ID')}</td>
                                    <td class="text-center text-info">${item.jumlah_peminjam_unik.toLocaleString('id-ID')}</td>
                                 </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    return html;
                } catch (e) {
                    return '<div class="text-danger small p-2">Gagal memuat detail prodi.</div>';
                }
            }

            // --- DATATABLES INIT ---
            if ($('#peminjamanFakultasTable').length) {
                var table = $('#peminjamanFakultasTable').DataTable({
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
                    "dom": 'rt<"d-flex justify-content-between align-items-center px-4 py-3"ip>',
                    "columnDefs": [{
                        "searchable": false,
                        "orderable": false,
                        "targets": [0, 1] // Kolom Tombol (+) & No
                    }],
                    "order": [],
                });

                // Add event listener for opening and closing details
                $('#peminjamanFakultasTable tbody').on('click', 'td.details-control', function () {
                    var tr = $(this).closest('tr');
                    var row = table.row(tr);
                    
                    if (row.child.isShown()) {
                        // This row is already open - close it
                        row.child.hide();
                        tr.removeClass('shown');
                    } else {
                        // Open this row
                        var childData = tr.attr('data-child');
                        row.child(formatChildRow(childData)).show();
                        tr.addClass('shown');
                    }
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

            // --- FILTER TOGGLE LOGIC ---
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

            // --- CHART LOGIC ---
            const chartData = @json($chartData ?? []);

            if (chartData.length > 0) {
                const ctx = document.getElementById('peminjamanFakultasChart').getContext('2d');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(item => item.label),
                        datasets: [
                            {
                                label: 'Peminjaman',
                                data: chartData.map(item => item.issue),
                                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                                borderColor: '#4e73df',
                                borderWidth: 2, tension: 0.4, fill: true, pointRadius: 3
                            },
                            {
                                label: 'Perpanjangan',
                                data: chartData.map(item => item.renew),
                                backgroundColor: 'rgba(54, 185, 204, 0.1)',
                                borderColor: '#36b9cc',
                                borderWidth: 2, tension: 0.4, fill: true, pointRadius: 3
                            },
                            {
                                label: 'Pengembalian',
                                data: chartData.map(item => item.pengembalian),
                                backgroundColor: 'rgba(246, 194, 62, 0.1)',
                                borderColor: '#f6c23e',
                                borderWidth: 2, tension: 0.4, fill: true, pointRadius: 3
                            },
                            {
                                label: 'Total Sirkulasi',
                                data: chartData.map(item => item.sirkulasi),
                                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                                borderColor: '#1cc88a',
                                borderWidth: 2, tension: 0.4, fill: true, pointRadius: 3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: "#858796", font: { size: 11 } } },
                            y: { beginAtZero: true, grid: { color: "#f0f2f5" }, ticks: { color: "#858796", font: { size: 11 } } }
                        },
                        plugins: {
                            legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 20 } },
                            tooltip: {
                                backgroundColor: "rgba(255,255,255,0.95)",
                                bodyColor: "#858796", titleColor: '#6e707e',
                                borderColor: '#dddfeb', borderWidth: 1, padding: 12, displayColors: true,
                            }
                        }
                    }
                });
            }

            // --- CSV EXPORT ---
            const exportCsvBtn = document.getElementById('exportCsvBtn');
            if (exportCsvBtn) {
                exportCsvBtn.addEventListener('click', function() {
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengekspor...';
                    const params = new URLSearchParams(window.location.search);
                    window.location.href = "{{ route('peminjaman.export_fakultas') }}?" + params.toString();
                    setTimeout(() => {
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-file-csv me-1"></i> Export CSV';
                    }, 3000);
                });
            }
        });
    </script>
@endpush

