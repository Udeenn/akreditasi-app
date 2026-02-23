@extends('layouts.app')
@section('title', 'Statistik Kunjungan Per Fakultas')

@section('content')
    <div class="container-fluid px-4">
        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden rounded-4">
                    <div class="card-body p-4 bg-primary bg-gradient  d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-chart-pie me-2"></i>Statistik Kunjungan Fakultas
                            </h3>
                            <p class="mb-0 opacity-75">
                                Laporan analisis data pengunjung perpustakaan berdasarkan Fakultas dan Program Studi.
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
                    <div class="card-header border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold text-primary"><i class="fas fa-filter me-1"></i> Filter Data</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="GET" action="{{ route('kunjungan.fakultasTable') }}" id="filterForm">
                            <input type="hidden" name="search" id="hiddenSearchInput" value="{{ request('search') }}">

                            <div class="row g-3 align-items-end">
                                {{-- Filter Tipe --}}
                                <div class="col-md-2 col-6">
                                    <label class="form-label small text-muted fw-bold mb-1">Periode</label>
                                    <select name="filter_type" id="filter_type"
                                        class="form-select form-select-sm shadow-none border-0">
                                        <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>
                                            Harian</option>
                                        <option value="yearly" {{ ($filterType ?? '') == 'yearly' ? 'selected' : '' }}>
                                            Tahunan (Bulanan)</option>
                                    </select>
                                </div>

                                {{-- Filter Lokasi --}}
                                <div class="col-md-2 col-12">
                                    <label class="form-label small text-muted fw-bold mb-1">Lokasi</label>
                                    <select name="lokasi" id="lokasi" class="form-select form-select-sm shadow-none border-0">
                                        <option value="">Semua Lokasi </option>
                                        @foreach ($lokasiMapping as $key => $val)
                                            <option value="{{ $key }}"
                                                {{ request('lokasi') == $key ? 'selected' : '' }}>
                                                {{ $val }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Filter Fakultas --}}
                                <div class="col-md-2 col-12">
                                    <label class="form-label small text-muted fw-bold mb-1">Fakultas</label>
                                    <select name="fakultas" id="fakultas" class="form-select form-select-sm shadow-none border-0">
                                        <option value="semua" {{ request('fakultas') == 'semua' ? 'selected' : '' }}>
                                            Semua </option>
                                        @foreach ($listFakultas as $namaFakultas)
                                            <option value="{{ $namaFakultas }}"
                                                {{ request('fakultas') == $namaFakultas ? 'selected' : '' }}>
                                                {{ $namaFakultas }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Filter Tanggal (Daily) --}}
                                <div class="col-md-2 col-6 daily-filter"
                                    style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                                    <label class="form-label small text-muted fw-bold mb-1">Dari Tanggal</label>
                                    <input type="date" name="tanggal_awal"
                                        class="form-control form-control-sm shadow-none border-0" value="{{ $tanggalAwal }}">
                                </div>
                                <div class="col-md-2 col-6 daily-filter"
                                    style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                                    <label class="form-label small text-muted fw-bold mb-1">Sampai Tanggal</label>
                                    <input type="date" name="tanggal_akhir"
                                        class="form-control form-control-sm shadow-none border-0" value="{{ $tanggalAkhir }}">
                                </div>

                                {{-- Filter Tahun (Yearly) --}}
                                <div class="col-md-2 col-6 yearly-filter"
                                    style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                                    <label class="form-label small text-muted fw-bold mb-1">Dari Tahun</label>
                                    <input type="number" name="tahun_awal" class="form-control form-control-sm shadow-none border-0"
                                        value="{{ $tahunAwal }}" placeholder="2020">
                                </div>
                                <div class="col-md-2 col-6 yearly-filter"
                                    style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                                    <label class="form-label small text-muted fw-bold mb-1">Sampai Tahun</label>
                                    <input type="number" name="tahun_akhir"
                                        class="form-control form-control-sm shadow-none border-0" value="{{ $tahunAkhir }}"
                                        placeholder="{{ date('Y') }}">
                                </div>

                                {{-- Tombol Action --}}
                                <div class="col-md-auto col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                        <i class="fas fa-search me-1"></i> Cari
                                    </button>
                                    <button type="button" id="btnExportCsv"
                                        class="btn btn-success px-4 fw-bold shadow-sm">
                                        <i class="fas fa-file-csv me-1"></i> CSV
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger shadow-sm border-0 rounded-4">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            </div>
        @endif

        @if ($hasFilter)
            <div class="row">
                {{-- 3. CHART SECTION --}}
                <div class="col-lg-12 mb-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-header border-bottom-0 pt-4 px-4">
                            <h5 class="fw-bold mb-0">
                                <i class="fas fa-chart-line text-info me-2"></i>Tren Kunjungan
                            </h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div style="height: 350px; position: relative;">
                                <canvas id="kunjunganChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. TABLE SECTION --}}
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div
                            class="card-header  border-bottom pt-4 px-4 pb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="fw-bold mb-0">
                                <i class="fas fa-table text-success me-2"></i>Detail Data
                            </h5>
                            <div
                                class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fs-6 border border-primary border-opacity-25">
                                <i class="fas fa-users me-1"></i> Total:
                                <span id="totalBadge"
                                    class="fw-extrabold">{{ number_format($totalKeseluruhanKunjungan ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <!-- Custom Search Input -->
                            <div class="mb-3">
                                <input type="text" class="form-control" id="searchInput"
                                    placeholder="Cari periode...">
                            </div>
                            
                            <div class="table-responsive">
                                <table id="yajraTable" class="table table-hover align-middle mb-0 unified-table"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="text-center py-3 border-bottom-0" width="3%"></th>
                                            <th class="text-center py-3 px-2 border-bottom-0" width="5%">No</th>
                                            <th class="py-3 px-4 border-bottom-0">Periode</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Jumlah Kunjungan</th>
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
                                                    <span class="badge bg-primary-soft text-primary rounded-pill px-3 fw-bold">{{ number_format($stat->total_kunjungan) }}</span>
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
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="text-center p-5 border rounded-4 shadow-sm">
                        <div class="mb-3 text-primary opacity-50">
                            <i class="fas fa-search fa-4x"></i>
                        </div>
                        <h4 class="fw-bold">Data Belum Ditampilkan</h4>
                        <p class="">Silakan atur filter di atas lalu klik tombol <strong>"Cari"</strong>
                            untuk menampilkan statistik.</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="mb-5"></div>
    </div>
@endsection


@push('styles')
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

        /* Memperbaiki teks di dalam kotak 'Empty State' */
        .bg-tertiary h4,
        .bg-tertiary p {
            color: var(--text-dark) !important;
        }

        /* Memperbaiki Dropdown & Input agar tidak ada background putih yang menabrak teks */
        .dataTables_length select,
        .dataTables_filter input {
            background-color: var(--bs-tertiary-bg) !important;
            color: var(--text-dark) !important;
            border: 1px solid var(--bs-border-color) !important;
        }

        .table-dark-custom {
            --bs-table-color: #ffffff;
            /* Memaksa warna teks tabel jadi putih */
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

        .card-body.bg-primary h3,
        .card-body.bg-primary p {
            color: #ffffff !important;
        }

        .dataTables_filter input {
            background-color: var(--sidebar-bg) !important;
            color: var(--text-dark) !important;
            border: 1px solid var(--bs-border-color) !important;
        }

        /* Memperbaiki Dropdown 'Show entries' */
        .dataTables_length select {
            background-color: var(--sidebar-bg) !important;
            color: var(--text-dark) !important;
            border: 1px solid var(--bs-border-color) !important;
        }

        /* Memastikan chart mengikuti warna teks tema */
        #kunjunganChart {
            color: var(--text-dark);
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

        /* 4. Hapus Efek Hover (Sesuai Request) */
        #yajraTable tbody tr:hover {
            background-color: transparent !important;
            cursor: default;
        }

        /* --- 2. LAYOUT & PADDING FIXES --- */

        /* Card mengikuti warna body/tema */
        .card {
            background-color: var(--bs-body-bg);
            border-color: var(--bs-border-color);
        }

        .card-header {
            background-color: var(--bs-body-bg);
            border-bottom-color: var(--bs-border-color);
        }

        /* Input & Select mengikuti tema */
        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 0.6rem 0.85rem;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            border-color: var(--bs-border-color);
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

        .fw-extrabold {
            font-weight: 800;
        }

        .btn {
            border-radius: 8px;
            transition: all 0.2s ease;
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
        body.dark-mode .table-child {
            background-color: var(--sidebar-bg) !important;
            color: #e2e8f0;
        }
        body.dark-mode .table-child th {
            background-color: rgba(13, 110, 253, 0.15) !important;
            color: #6ea8fe !important;
        }
        body.dark-mode .table-child td {
            border-bottom-color: rgba(255,255,255,0.05);
        }
        body.dark-mode .child-table-wrapper {
            border-color: rgba(255,255,255,0.08);
            background-color: var(--sidebar-bg) !important;
        }
        body.dark-mode .child-table-wrapper h6 {
            color: #6ea8fe !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        $(document).ready(function() {

            // --- 1. EXPORT CSV ---
            $('#btnExportCsv').on('click', function(e) {
                e.preventDefault();
                var baseUrl = "{{ route('kunjungan.fakultasExport') }}";
                var formData = $('#filterForm').serialize();
                window.location.href = baseUrl + "?" + formData;
            });

            // --- 2. TOGGLE FILTER UI ---
            function toggleFilters() {
                const val = $('#filter_type').val();
                if (val === 'yearly') {
                    $('.daily-filter').hide();
                    $('.yearly-filter').fadeIn(300);
                } else {
                    $('.daily-filter').fadeIn(300);
                    $('.yearly-filter').hide();
                }
            }
            $('#filter_type').on('change', toggleFilters);
            toggleFilters();

            // --- 3. CLIENT-SIDE DATATABLES ---
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
                                    <th class="text-center">Jumlah Kunjungan</th>
                                </tr>
                             </thead><tbody>`;
                             
                    details.forEach(item => {
                        html += `<tr>
                                    <td class="fw-medium">${item.prodi}</td>
                                    <td class="text-center fw-bold">${item.jumlah.toLocaleString('id-ID')}</td>
                                 </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    return html;
                } catch (e) {
                    return '<div class="text-danger small p-2">Gagal memuat detail prodi.</div>';
                }
            }

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
                    "targets": [0, 1]
                }],
                "order": [],
                "drawCallback": function(settings) {
                    var api = this.api();
                    api.column(1, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }
            });

            // Add event listener for opening and closing details
            $('#yajraTable tbody').on('click', 'td.details-control', function () {
                var tr = $(this).closest('tr');
                var row = table.row(tr);
                
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                } else {
                    var childData = tr.attr('data-child');
                    row.child(formatChildRow(childData)).show();
                    tr.addClass('shown');
                }
            });

            // Bind Custom Search Input
            $('#searchInput').on('keyup change', function() {
                table.search(this.value).draw();
            });


            // --- 4. CHART ---
            @if (isset($chartData) && count($chartData) > 0)
                const ctx = document.getElementById('kunjunganChart');
                if (ctx) {
                    const chartData = @json($chartData);
                    const labels = chartData.map(item => item.label);
                    const dataValues = chartData.map(item => item.total_kunjungan);

                    var gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
                    gradient.addColorStop(0, 'rgba(13, 110, 253, 0.5)');
                    gradient.addColorStop(1, 'rgba(13, 110, 253, 0.0)');

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Total Kunjungan',
                                data: dataValues,
                                borderColor: '#0d6efd',
                                backgroundColor: gradient,
                                borderWidth: 3,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#0d6efd',
                                pointHoverBackgroundColor: '#0d6efd',
                                pointHoverBorderColor: '#fff',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(30, 41, 59, 0.95)', // Tooltip dark
                                    padding: 10,
                                    displayColors: false,
                                    callbacks: {
                                        label: function(context) {
                                            return ' Jumlah: ' + context.parsed.y;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0,0,0,0.05)',
                                        drawBorder: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            @endif
        });
    </script>
@endpush
