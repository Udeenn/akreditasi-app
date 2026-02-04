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
                                        class="form-select form-select-sm shadow-none">
                                        <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>
                                            Harian</option>
                                        <option value="yearly" {{ ($filterType ?? '') == 'yearly' ? 'selected' : '' }}>
                                            Tahunan (Bulanan)</option>
                                    </select>
                                </div>

                                {{-- Filter Fakultas --}}
                                <div class="col-md-3 col-12">
                                    <label class="form-label small text-muted fw-bold mb-1">Fakultas</label>
                                    <select name="fakultas" id="fakultas" class="form-select form-select-sm shadow-none">
                                        <option value="semua" {{ request('fakultas') == 'semua' ? 'selected' : '' }}>--
                                            Semua Fakultas --</option>
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
                                        class="form-control form-control-sm shadow-none" value="{{ $tanggalAwal }}">
                                </div>
                                <div class="col-md-2 col-6 daily-filter"
                                    style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                                    <label class="form-label small text-muted fw-bold mb-1">Sampai Tanggal</label>
                                    <input type="date" name="tanggal_akhir"
                                        class="form-control form-control-sm shadow-none" value="{{ $tanggalAkhir }}">
                                </div>

                                {{-- Filter Tahun (Yearly) --}}
                                <div class="col-md-2 col-6 yearly-filter"
                                    style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                                    <label class="form-label small text-muted fw-bold mb-1">Dari Tahun</label>
                                    <input type="number" name="tahun_awal" class="form-control form-control-sm shadow-none"
                                        value="{{ $tahunAwal }}" placeholder="2020">
                                </div>
                                <div class="col-md-2 col-6 yearly-filter"
                                    style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                                    <label class="form-label small text-muted fw-bold mb-1">Sampai Tahun</label>
                                    <input type="number" name="tahun_akhir"
                                        class="form-control form-control-sm shadow-none" value="{{ $tahunAkhir }}"
                                        placeholder="{{ date('Y') }}">
                                </div>

                                {{-- Tombol Action --}}
                                <div class="col-md-3 col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm flex-fill fw-bold shadow-sm">
                                        <i class="fas fa-search me-1"></i> Cari
                                    </button>
                                    <button type="button" id="btnExportCsv"
                                        class="btn btn-success btn-sm flex-fill fw-bold shadow-sm">
                                        <i class="fas fa-file-csv me-1"></i> CSV
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

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
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="yajraTable" class="table table-dark-custom align-middle w-100 mb-0 my-3"
                                    style="border-collapse: collapse;">
                                    <thead class="">
                                        <tr>
                                            <th class="px-4 py-3 text-uppercase small fw-bold" width="5%">No</th>
                                            <th class="px-4 py-3 text-uppercase small fw-bold" width="25%">Waktu</th>
                                            <th class="px-4 py-3 text-uppercase small fw-bold" width="45%">Prodi /
                                                Kategori</th>
                                            <th class="px-4 py-3 text-uppercase small fw-bold text-end" width="25%">
                                                Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
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
            color: var(--bs-body-color) !important;
        }

        /* Memperbaiki baris tabel (TD) agar teksnya terlihat */
        #yajraTable tbody td {
            color: var(--bs-body-color) !important;
        }

        /* Memperbaiki teks di dalam kotak 'Empty State' */
        .bg-tertiary h4,
        .bg-tertiary p {
            color: var(--bs-body-color) !important;
        }

        /* Memperbaiki Dropdown & Input agar tidak ada background putih yang menabrak teks */
        .dataTables_length select,
        .dataTables_filter input {
            background-color: var(--bs-tertiary-bg) !important;
            color: var(--bs-body-color) !important;
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
            color: var(--bs-body-color) !important;
        }

        /* Perbaiki input search agar background tidak putih terang */
        .dataTables_filter input,
        .dataTables_length select {
            background-color: var(--bs-body-bg) !important;
            color: var(--bs-body-color) !important;
            border: 1px solid var(--bs-border-color) !important;
        }

        .card-body.bg-primary h3,
        .card-body.bg-primary p {
            color: #ffffff !important;
        }

        /* Memperbaiki Search box DataTable agar tidak tenggelam di dark mode */
        .dataTables_filter input {
            background-color: var(--bs-body-bg) !important;
            color: var(--bs-body-color) !important;
            border: 1px solid var(--bs-border-color) !important;
        }

        /* Memperbaiki Dropdown 'Show entries' */
        .dataTables_length select {
            background-color: var(--bs-body-bg) !important;
            color: var(--bs-body-color) !important;
            border: 1px solid var(--bs-border-color) !important;
        }

        /* Memastikan chart mengikuti warna teks tema */
        #kunjunganChart {
            color: var(--bs-body-color);
        }

        #yajraTable {
            --bs-table-bg: transparent;
            --bs-table-accent-bg: transparent;
            --bs-table-striped-bg: transparent;
            --bs-table-hover-bg: transparent;
            color: var(--bs-body-color) !important;
        }

        #yajraTable thead th {
            background-color: transparent !important;
            /* KUNCI PERBAIKAN: Transparan */
            color: var(--bs-body-color) !important;
            /* Teks otomatis putih di darkmode */
            border-bottom: 2px solid var(--bs-border-color);
            letter-spacing: 0.5px;
        }

        /* 3. Body (TD) Transparan */
        #yajraTable tbody td {
            background-color: transparent !important;
            /* KUNCI PERBAIKAN: Transparan */
            color: var(--bs-body-color) !important;
            /* Teks otomatis putih di darkmode */
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--bs-border-color);
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

        /* Pagination Styling */
        .page-link {
            padding: 0.6rem 1.2rem !important;
            font-weight: 600;
            border-radius: 8px !important;
            margin: 0 3px;
            font-size: 0.9rem;
            background-color: var(--bs-body-bg);
            /* Background tombol ikut tema */
            border-color: var(--bs-border-color);
            color: var(--bs-body-color);
        }

        .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white !important;
        }

        .page-item.disabled .page-link {
            background-color: var(--bs-tertiary-bg);
            color: var(--bs-secondary-color);
        }

        .fw-extrabold {
            font-weight: 800;
        }

        .btn {
            border-radius: 8px;
            padding: 0.6rem 1rem;
            transition: all 0.2s ease;
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

            // --- 3. DATATABLES ---
            var table = $('#yajraTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                ordering: false,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                ajax: {
                    url: "{{ route('kunjungan.fakultasTable') }}",
                    data: function(d) {
                        d.filter_type = $('#filter_type').val();
                        d.fakultas = $('#fakultas').val();
                        d.tanggal_awal = $('input[name="tanggal_awal"]').val();
                        d.tanggal_akhir = $('input[name="tanggal_akhir"]').val();
                        d.tahun_awal = $('input[name="tahun_awal"]').val();
                        d.tahun_akhir = $('input[name="tahun_akhir"]').val();
                        d.search_manual = $('#hiddenSearchInput').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center opacity-75'
                    },
                    {
                        data: 'tanggal_kunjungan',
                        name: 'tanggal_kunjungan'
                    },
                    {
                        data: 'nama_prodi',
                        name: 'nama_prodi'
                    },
                    {
                        data: 'jumlah_kunjungan_harian',
                        name: 'jumlah_kunjungan_harian',
                        className: 'text-end fw-bold'
                    }
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json",
                    searchPlaceholder: "Cari prodi..."
                },
                drawCallback: function(settings) {
                    var api = this.api();
                    var json = api.ajax.json();
                    if (json && typeof json.recordsTotalFiltered !== 'undefined') {
                        let formattedTotal = new Intl.NumberFormat('id-ID').format(json
                            .recordsTotalFiltered);
                        $('#totalBadge').text(formattedTotal);
                    }
                }
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
