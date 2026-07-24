@extends('layouts.app')

@section('title', 'Statistik Peminjaman Keseluruhan')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    {{-- Shared styles loaded from unified-components.css --}}
@endpush

@section('content')
    <div class="container-fluid px-3 px-md-4 pt-2 pb-4">

        <x-breadcrumb title="Statistik Peminjaman" icon="fas fa-calendar-alt">
            <x-slot name="subtitle">
                Analisis data sirkulasi peminjaman buku perpustakaan berdasarkan rentang waktu.
            </x-slot>
        </x-breadcrumb>

        {{-- 2. FILTER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card unified-card border-0 shadow-sm">
                    <div class="card-header border-bottom-0 pt-4 pb-0 px-4">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-filter text-primary me-2"></i> Filter Data</h5>
                    </div>
                    <div class="card-body px-4 pb-4 pt-3">
                        <form method="GET" action="{{ route('peminjaman.keseluruhan') }}" class="row g-3 align-items-end" id="filterForm">
                            <div class="col-md-4">
                                <label for="filter_type" class="form-label small text-muted text-uppercase">Mode Tampilan</label>
                                <select name="filter_type" id="filter_type" class="form-select">
                                    <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>Harian</option>
                                    <option value="monthly" {{ ($filterType ?? '') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="dailyFilter" style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                                <label class="form-label small text-muted text-uppercase">Rentang Tanggal</label>
                                <div class="input-group">
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate ?? \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') }}">
                                    <span class="input-group-text text-muted">s/d</span>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate ?? \Carbon\Carbon::now()->format('Y-m-d') }}">
                                </div>
                            </div>

                            <div class="col-md-6" id="monthlyFilter" style="{{ ($filterType ?? '') == 'monthly' ? '' : 'display: none;' }}">
                                <label class="form-label small text-muted text-uppercase">Rentang Tahun</label>
                                <div class="input-group">
                                    @php
                                        $currentYear = date('Y');
                                        $loopStartYear = $currentYear - 10;
                                        $loopEndYear = $currentYear;
                                    @endphp
                                    <select name="start_year" id="start_year" class="form-select">
                                        @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                            <option value="{{ $year }}" {{ ($startYear ?? $currentYear) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                    <span class="input-group-text text-muted">s.d.</span>
                                    <select name="end_year" id="end_year" class="form-select">
                                        @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                            <option value="{{ $year }}" {{ ($endYear ?? $currentYear) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                    <i class="fas fa-search me-1"></i> Cari
                                </button>
                            </div>
                        </form>
                        
                        <form id="pdfExportForm" method="POST" action="{{ route('peminjaman.export_pdf_keseluruhan') }}" style="display:none;">
                            @csrf
                            <input type="hidden" name="filter_type" value="{{ $filterType ?? 'daily' }}">
                            <input type="hidden" name="start_date" value="{{ $startDate ?? '' }}">
                            <input type="hidden" name="end_date" value="{{ $endDate ?? '' }}">
                            <input type="hidden" name="start_year" value="{{ $startYear ?? '' }}">
                            <input type="hidden" name="end_year" value="{{ $endYear ?? '' }}">
                            <input type="hidden" name="chart_image_base64" id="chart_image_base64">
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
                                  <div id="peminjamanChart" style="min-height: 350px; width: 100%;"></div>
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
                            <div>
                                <button type="button" id="exportPdfBtn" class="btn btn-danger  fw-bold shadow-sm px-3 me-2"><i class="fas fa-file-pdf me-2"></i> Cetak PDF</button>
                                <button type="button" id="exportCsvBtn" class="btn btn-success  fw-bold shadow-sm px-3"><i class="fas fa-file-csv me-2"></i> Export CSV
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 unified-table" style="min-width: 600px;">
                                    <thead class="">
                                        <tr>
                                            <th class="text-center py-3 px-4 border-bottom-0" width="5%">No</th>
                                            <th class="py-3 px-4 border-bottom-0">Periode</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Peminjaman</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Perpanjangan</th>
                                            <th class="text-center py-3 px-4 border-bottom-0">Pengembalian</th>
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
                                                        class="badge bg-primary-soft text-primary rounded-pill px-3">{{ $stat->jumlah_issue }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-info-soft text-info rounded-pill px-3">{{ $stat->jumlah_renew }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-success-soft text-success rounded-pill px-3">{{ $stat->jumlah_pengembalian }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge border text-body rounded-pill px-3">{{ number_format($stat->total_sirkulasi) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary view-detail-btn px-3 shadow-sm"
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
                        <div class="card-footer px-4 border-0 py-3 bg-transparent">
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
                    <div class="card border-0 shadow-sm text-center p-5 rounded-4">
                        <div class="card-body">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-4 mb-3">
                                <i class="fas fa-search fa-3x text-primary"></i>
                            </div>
                            <h5 class="fw-bold text-body">Data Tidak Ditemukan</h5>
                            <p class="text-muted mb-0">Silakan gunakan filter di atas untuk menampilkan statistik peminjaman.</p>
                        </div>
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
                                <table class="table table-hover align-middle mb-0 unified-table" id="detailTable">
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
                            class="btn btn-success  fw-bold shadow-sm px-3"><i class="fas fa-file-csv me-2"></i> Export CSV
                        </a>
                        <button type="button" class="btn btn-secondary btn-sm px-4"
                            data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPT ASLI (TIDAK DIUBAH) --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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
                const chartDataBorrowers = fullStatistics.map(item => item.jumlah_peminjam_unik);

                var options = {
                    series: [
                        { name: 'Buku Terpinjam', data: chartDataBooks },
                        { name: 'Total Pengembalian', data: chartDataReturns },
                        { name: 'Total Peminjam', data: chartDataBorrowers }
                    ],
                    chart: {
                        height: 350,
                        type: 'area',
                        fontFamily: 'Inter, Helvetica, Arial, sans-serif',
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        selection: { enabled: false },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    colors: ['#0d6efd', '#ffc107', '#0dcaf0'],
                    dataLabels: { enabled: false },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.05,
                            stops: [0, 90, 100]
                        }
                    },
                    xaxis: {
                        categories: chartLabels,
                        labels: { style: { colors: '#6c757d', fontSize: '11px' } },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: {
                        labels: { 
                            style: { colors: '#6c757d', fontSize: '11px' },
                            formatter: function (val) { return Math.round(val); }
                        }
                    },
                    grid: {
                        borderColor: '#f0f2f5',
                        strokeDashArray: 4,
                        yaxis: { lines: { show: true } }
                    },
                    legend: {
                        position: 'top',
                        fontWeight: 'bold'
                    },
                    tooltip: {
                        theme: 'dark'
                    }
                };

                window.peminjamanChartInstance = new ApexCharts(document.querySelector("#peminjamanChart"), options);
                window.peminjamanChartInstance.render();
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

                    let periodSuffixForFile = '';
                    if (filterType === 'daily') {
                        const startDate = document.getElementById('start_date').value;
                        const endDate = document.getElementById('end_date').value;
                        title += ` (Harian: ${startDate} s/d ${endDate})`;
                        periodSuffixForFile = `Harian_${startDate}_sd_${endDate}`;
                    } else {
                        const startYear = document.getElementById('start_year').value;
                        const endYear = document.getElementById('end_year').value;
                        title += ` (Tahunan: ${startYear} s/d ${endYear})`;
                        periodSuffixForFile = `Tahunan_${startYear}_sd_${endYear}`;
                    }

                    csv.push([title]);
                    csv.push([]);
                    const headers = ['No', 'Periode', 'Peminjaman', 'Perpanjangan', 'Pengembalian',
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
                        const issue = row.jumlah_issue || 0;
                        const renew = row.jumlah_renew || 0;
                        const kembali = row.jumlah_pengembalian || 0;
                        const sirkulasi = row.total_sirkulasi || 0;
                        const peminjam = row.jumlah_peminjam_unik || 0;

                        let rowData = [
                            index + 1,
                            `"${periode}"`,
                            issue,
                            renew,
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
                    let fileName = `Laporan_Peminjaman_Keseluruhan_${periodSuffixForFile}.csv`;

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
            
            // --- EXPORT PDF LOGIC ---
            const exportPdfBtn = document.getElementById('exportPdfBtn');
            const pdfExportForm = document.getElementById('pdfExportForm');
            const chartImageInput = document.getElementById('chart_image_base64');
            
            if (exportPdfBtn) {
                exportPdfBtn.addEventListener('click', async function() {
                    // Capture Chart as Base64 Image if chartInstance exists
                    if (typeof window.peminjamanChartInstance !== 'undefined' && window.peminjamanChartInstance) {
                        const uri = await window.peminjamanChartInstance.dataURI();
                        chartImageInput.value = uri.imgURI;
                    }
                    
                    // Submit the hidden form
                    pdfExportForm.submit();
                });
            }
        });
    </script>
@endsection

