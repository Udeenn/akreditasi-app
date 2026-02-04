@extends('layouts.app')

@section('title', 'Statistik Kunjungan Civitas')

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
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.25rem;
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
            color: #fff;
            border: 1px solid #2b2b40;
        }

        body.dark-mode .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* --- SELECT2 DARK MODE FIXES (YOUR CODE PRESERVED) --- */
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection {
            background-color: #1e293b !important;
            border-color: #2b2b40 !important;
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-dropdown {
            background-color: #1e293b !important;
            border-color: #2b2b40 !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            background-color: #1e293b !important;
            border-color: #2b2b40 !important;
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__rendered {
            color: #ffffff !important;
        }

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
                                <i class="fas fa-chart-line me-2"></i>Statistik Kunjungan Civitas
                            </h3>
                            <p class="mb-0 opacity-75">
                                Ringkasan data kunjungan berdasarkan program studi dan periode.
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
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold text-primary"><i class="fas fa-filter me-1"></i> Filter Data</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('kunjungan.prodiTable') }}" class="row g-3 align-items-end"
                            id="filterForm">

                            {{-- Tipe Filter --}}
                            <div class="col-12 col-md-2">
                                <label for="filter_type" class="form-label small text-muted fw-bold">Tampilkan Data</label>
                                <select name="filter_type" id="filter_type" class="form-select border-0  fw-semibold">
                                    <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>Per
                                        Hari</option>
                                    <option value="yearly" {{ ($filterType ?? '') == 'yearly' ? 'selected' : '' }}>Per Bulan
                                    </option>
                                </select>
                            </div>

                            {{-- Pilih Prodi (Select2) --}}
                            <div class="col-12 col-md-4">
                                <label for="prodi" class="form-label small text-muted fw-bold">Pilih Prodi/Tipe
                                    User</label>
                                <select name="prodi" id="prodi" class="form-select">
                                    @foreach ($listProdi as $kode => $nama)
                                        <option class="custom-option" value="{{ $kode }}"
                                            {{ request('prodi') == $kode ? 'selected' : '' }}>
                                            ({{ $kode }})
                                            - {{ $nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Filter Harian --}}
                            <div class="col-6 col-md-2" id="dailyFilterStart"
                                style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                                <label for="tanggal_awal" class="form-label small text-muted fw-bold">Tanggal Awal</label>
                                <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control border-0 "
                                    value="{{ $tanggalAwal ?? \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                            </div>
                            <div class="col-6 col-md-2" id="dailyFilterEnd"
                                style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                                <label for="tanggal_akhir" class="form-label small text-muted fw-bold">Tanggal Akhir</label>
                                <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control border-0 "
                                    value="{{ $tanggalAkhir ?? \Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>

                            {{-- Filter Tahunan --}}
                            <div class="col-6 col-md-2" id="yearlyFilter"
                                style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                                <label for="tahun_awal" class="form-label small text-muted fw-bold">Tahun Awal</label>
                                <input type="number" name="tahun_awal" id="tahun_awal" class="form-control border-0 "
                                    value="{{ $tahunAwal ?? \Carbon\Carbon::now()->year }}">
                            </div>
                            <div class="col-6 col-md-2" id="yearlyFilterEnd"
                                style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                                <label for="tahun_akhir" class="form-label small text-muted fw-bold">Tahun Akhir</label>
                                <input type="number" name="tahun_akhir" id="tahun_akhir" class="form-control border-0 "
                                    value="{{ $tahunAkhir ?? \Carbon\Carbon::now()->year }}">
                            </div>

                            {{-- Tombol --}}
                            <div class="col-12 col-md-2 ms-auto">
                                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                                    <i class="fas fa-search me-1"></i> Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if ($hasFilter)

            {{-- 3. CHART SECTION --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-4 px-4">
                            <h5 class="fw-bold mb-0 text-body">
                                Grafik Kunjungan: <span
                                    class="text-primary">{{ $listProdi[request('prodi')] ?? 'Seluruh Prodi/Tipe User' }}</span>
                            </h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div style="height: 350px; position: relative;">
                                <canvas id="kunjunganChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. TABLE SECTION --}}
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold m-0 text-primary">
                                <i class="fas fa-table me-2"></i>Tabel Statistik
                            </h5>
                            <div class="d-flex align-items-center gap-2">
                                {{-- Dropdown Show Entries --}}
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button"
                                        id="entriesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Show: {{ $data->perPage() }}
                                    </button>
                                    <ul class="dropdown-menu shadow-sm dropdown-menu-end"
                                        aria-labelledby="entriesDropdown">
                                        <li><a class="dropdown-item @if ($perPage == 10) active @endif"
                                                href="{{ request()->fullUrlWithQuery(['per_page' => 10]) }}">10</a></li>
                                        <li><a class="dropdown-item @if ($perPage == 100) active @endif"
                                                href="{{ request()->fullUrlWithQuery(['per_page' => 100]) }}">100</a></li>
                                        <li><a class="dropdown-item @if ($perPage == 1000) active @endif"
                                                href="{{ request()->fullUrlWithQuery(['per_page' => 1000]) }}">1000</a>
                                        </li>
                                    </ul>
                                </div>

                                {{-- Tombol Export CSV --}}
                                <button id="downloadFullCsvBtn" class="btn btn-success btn-sm fw-bold">
                                    <i class="fas fa-file-csv me-1"></i> Export CSV
                                </button>
                            </div>
                        </div>

                        {{-- Summary Alerts (Styled as soft banners) --}}
                        <div class="card-body border-bottom ">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <div class="d-flex align-items-center p-2 rounded  border shadow-sm">
                                        <div class="icon-box bg-primary-soft rounded me-3"
                                            style="width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block text-uppercase fw-bold"
                                                style="font-size: 0.7rem;">Total Keseluruhan</small>
                                            <span
                                                class="fw-bold  h6 mb-0">{{ number_format($totalKeseluruhanKunjungan, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="d-flex align-items-center p-2 rounded  border shadow-sm">
                                        <div class="icon-box bg-info-soft rounded me-3"
                                            style="width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="fas fa-list-ol"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block text-uppercase fw-bold"
                                                style="font-size: 0.7rem;">Entri Halaman Ini</small>
                                            <span
                                                class="fw-bold  h6 mb-0">{{ number_format($data->total(), 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="d-flex align-items-center p-2 rounded  border shadow-sm">
                                        <div class="icon-box bg-warning-soft rounded me-3"
                                            style="width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="text-truncate">
                                            <small class="text-muted d-block text-uppercase fw-bold"
                                                style="font-size: 0.7rem;">Periode Filter</small>
                                            <span class="fw-bold  small">
                                                @if (($filterType ?? 'daily') == 'daily')
                                                    @if ($tanggalAwal && $tanggalAkhir)
                                                        {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d M y') }}
                                                        -
                                                        {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d M y') }}
                                                    @else
                                                        -
                                                    @endif
                                                @elseif (($filterType ?? '') == 'yearly')
                                                    @if ($tahunAwal && $tahunAkhir)
                                                        {{ $tahunAwal }} - {{ $tahunAkhir }}
                                                    @else
                                                        Semua
                                                    @endif
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="min-width: 600px;">
                                    <thead class="">
                                        <tr>
                                            <th class="py-3 px-4 border-bottom-0" width="5%">No</th>
                                            <th class="py-3 px-4 border-bottom-0">Tanggal / Bulan</th>
                                            <th class="py-3 px-4 border-bottom-0">Prodi / Kategori</th>
                                            <th class="py-3 px-4 border-bottom-0 text-center">Jumlah</th>
                                            <th class="py-3 px-4 border-bottom-0 text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($data->isEmpty())
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <div class="text-muted">
                                                        <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                                                        <p>Tidak ada data untuk periode ini.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @else
                                            @foreach ($data as $row)
                                                <tr>
                                                    <td class="px-4 text-center text-muted fw-bold">{{ $loop->iteration }}
                                                    </td>
                                                    <td class="px-4 fw-medium text-body">
                                                        <i class="far fa-clock me-2 text-muted"></i>
                                                        @if (($filterType ?? 'daily') == 'yearly')
                                                            {{ \Carbon\Carbon::parse($row->tanggal_kunjungan)->locale('id')->isoFormat('MMMM Y') }}
                                                        @else
                                                            {{ \Carbon\Carbon::parse($row->tanggal_kunjungan)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4">
                                                        <span class="fw-bold ">{{ $row->nama_prodi }}</span>
                                                    </td>
                                                    <td class="px-4 text-center">
                                                        <span
                                                            class="badge bg-primary-soft text-primary px-3 py-1 rounded-pill">
                                                            {{ $row->jumlah_kunjungan_harian }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 text-end">
                                                        <button type="button"
                                                            class="btn btn-outline-primary btn-sm view-detail-btn rounded-pill px-3"
                                                            data-bs-toggle="modal" data-bs-target="#detailPengunjungModal"
                                                            data-tanggal="{{ $row->tanggal_kunjungan }}"
                                                            data-filter-type="{{ $filterType ?? 'daily' }}"
                                                            data-kode-identifikasi="{{ $row->kode_identifikasi }}"
                                                            data-total="{{ $row->jumlah_kunjungan_harian }}">
                                                            <i class="fas fa-eye me-1"></i> Detail
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Pagination --}}
                        @if ($data->hasPages())
                            <div class="card-footer  border-0 py-3">
                                <div class="d-flex justify-content-end">
                                    {{ $data->links() }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- EMPTY STATE --}}
                <div class="row justify-content-center mt-5">
                    <div class="col-12 col-md-6">
                        <div class="text-center p-5 border-0 rounded-4">
                            <i class="fas fa-search fa-3x text-muted mb-3 opacity-50"></i>
                            <h5 class="fw-bold text-body">Siap Menampilkan Data</h5>
                            <p class="text-muted">Silakan gunakan filter di atas untuk memulai analisis statistik
                                kunjungan.</p>
                        </div>
                    </div>
                </div>
        @endif

        {{-- MODAL DETAIL --}}
        <div class="modal fade" id="detailPengunjungModal" tabindex="-1" aria-labelledby="detailPengunjungModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 py-3">
                        <div>
                            <h5 class="modal-title fw-bold text-body" id="detailPengunjungModalLabel">Detail Pengunjung
                            </h5>
                            <small class="text-muted" id="modalPeriodeSpan"></small>
                        </div>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3  rounded">
                            <span class="fw-bold ">Kode: <span id="modalKodeTipeSpan" class="text-primary"></span></span>
                            <span class="fw-bold ">Total: <span id="modalTotalSpan"
                                    class="badge bg-success rounded-pill"></span></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class=" sticky-top">
                                    <tr>
                                        <th class="border-bottom text-muted small text-uppercase">No</th>
                                        <th class="border-bottom text-muted small text-uppercase">Nama</th>
                                        <th class="border-bottom text-muted small text-uppercase">ID Kartu</th>
                                        <th class="border-bottom text-muted small text-uppercase text-center">Frekuensi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyDetailPengunjung">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Memuat data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3" id="modalPagination"></div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-success btn-sm rounded-pill px-4 shadow-sm"
                            id="exportDetailPengunjungCsvBtn">
                            <i class="fas fa-file-csv me-1"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4"
                            data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    {{-- JS Libraries --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/locale/id.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Select2 Initialization (jQuery required) ---
            $(document).ready(function() {
                $('#prodi').select2({
                    theme: 'bootstrap-5',
                    width: '100%' // Ensure full width in responsive
                });
            });

            // --- Variables ---
            const filterForm = document.getElementById('filterForm');
            const filterTypeSelect = document.getElementById('filter_type');
            const dailyFilterStart = document.getElementById('dailyFilterStart');
            const dailyFilterEnd = document.getElementById('dailyFilterEnd');
            const yearlyFilter = document.getElementById('yearlyFilter');
            const yearlyFilterEnd = document.getElementById('yearlyFilterEnd');

            // Modal Elements
            const detailModalEl = new bootstrap.Modal(document.getElementById('detailPengunjungModal'));
            const modalPeriodeSpan = document.getElementById('modalPeriodeSpan');
            const modalKodeTipeSpan = document.getElementById('modalKodeTipeSpan');
            const tbodyDetailPengunjung = document.getElementById('tbodyDetailPengunjung');
            const modalPagination = document.getElementById('modalPagination');
            const modalTotalSpan = document.getElementById('modalTotalSpan');

            let currentDetailTanggal = '';
            let currentFilterType = '';
            let currentKodeIdentifikasi = '';

            // PHP Data
            const listProdi = @json($listProdi);
            const tanggalAwal = @json($tanggalAwal);
            const tanggalAkhir = @json($tanggalAkhir);
            const tahunAwal = @json($tahunAwal ?? null);
            const tahunAkhir = @json($tahunAkhir ?? null);
            const loadingMessage =
                `<tr><td colspan="4" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Memuat data...</td></tr>`;

            // --- Toggle Filter Inputs ---
            filterTypeSelect.addEventListener('change', function() {
                if (this.value === 'daily') {
                    $(dailyFilterStart).show();
                    $(dailyFilterEnd).show();
                    $(yearlyFilter).hide();
                    $(yearlyFilterEnd).hide();
                } else {
                    $(dailyFilterStart).hide();
                    $(dailyFilterEnd).hide();
                    $(yearlyFilter).show();
                    $(yearlyFilterEnd).show();
                }
            });

            // --- Chart Logic ---
            const hasFilter = {{ json_encode($hasFilter) }};
            if (hasFilter) {
                const chartCtx = document.getElementById('kunjunganChart');
                if (chartCtx) {
                    const ctx = chartCtx.getContext('2d');
                    const chartData = @json($chartData);
                    const filterType = '{{ $filterType }}';

                    const chartLabels = chartData.map(item => item.label);
                    const chartValues = chartData.map(item => item.total_kunjungan);
                    const formattedLabels = chartLabels.map(label => {
                        const date = moment(label);
                        return (filterType === 'yearly') ? date.format('MMM YYYY') : date.format(
                            'ddd, DD MMM');
                    });

                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: formattedLabels,
                            datasets: [{
                                label: 'Jumlah Kunjungan',
                                data: chartValues,
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.6)',
                                borderWidth: 1,
                                borderRadius: 4,
                                barPercentage: 0.6,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(33, 37, 41, 0.95)',
                                    padding: 10,
                                    titleFont: {
                                        size: 13
                                    },
                                    bodyFont: {
                                        size: 13
                                    },
                                    callbacks: {
                                        title: function(context) {
                                            const date = moment(chartLabels[context[0].dataIndex]);
                                            return (filterType === 'daily') ? date.format(
                                                'dddd, D MMMM YYYY') : date.format('MMMM YYYY');
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        },
                                        color: '#6c757d'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: '#f0f2f5'
                                    },
                                    ticks: {
                                        precision: 0,
                                        font: {
                                            size: 11
                                        },
                                        color: '#6c757d'
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // --- Modal & Detail Logic ---
            async function loadDetailData(page = 1) {
                tbodyDetailPengunjung.innerHTML = loadingMessage;
                modalPagination.innerHTML = '';
                modalTotalSpan.textContent = '...';

                let tanggalParam = (currentFilterType === 'yearly') ?
                    `bulan=${currentDetailTanggal.substring(0, 7)}` :
                    `tanggal=${currentDetailTanggal}`;

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
                                <td class="text-muted small">${(result.from || 1) + index}</td>
                                <td class="fw-bold ">${pengunjung.nama || 'Tidak Diketahui'}</td>
                                <td><span class="badge border rounded-pill text-body fw-normal px-3">${pengunjung.cardnumber}</span></td>
                                <td class="text-center"><span class="badge bg-primary-soft text-primary rounded-pill">${pengunjung.visit_count}x</span></td>
                            `;
                            tbodyDetailPengunjung.appendChild(tr);
                        });
                        renderPagination(result);
                    } else {
                        tbodyDetailPengunjung.innerHTML =
                            `<tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data detail.</td></tr>`;
                        modalTotalSpan.textContent = 0;
                    }
                } catch (error) {
                    console.error(error);
                    tbodyDetailPengunjung.innerHTML =
                        `<tr><td colspan="4" class="text-danger text-center py-4">Gagal memuat data.</td></tr>`;
                }
            }

            function renderPagination(data) {
                const ul = document.createElement('ul');
                ul.classList.add('pagination', 'pagination-sm', 'm-0');

                // Helper func
                const createItem = (text, page, isActive = false, isDisabled = false) => {
                    const li = document.createElement('li');
                    li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
                    const a = document.createElement('a');
                    a.className = 'page-link';
                    a.href = '#';
                    a.innerHTML = text;
                    if (!isDisabled && !isActive) {
                        a.addEventListener('click', (e) => {
                            e.preventDefault();
                            loadDetailData(page);
                        });
                    }
                    li.appendChild(a);
                    return li;
                };

                ul.appendChild(createItem('&laquo;', data.current_page - 1, false, !data.prev_page_url));

                // Simple pagination logic (show all or limit if needed, here basic)
                for (let i = 1; i <= data.last_page; i++) {
                    // Logic to hide too many pages can be added here if needed
                    if (i == 1 || i == data.last_page || (i >= data.current_page - 2 && i <= data.current_page +
                            2)) {
                        ul.appendChild(createItem(i, i, i === data.current_page));
                    } else if (i == data.current_page - 3 || i == data.current_page + 3) {
                        ul.appendChild(createItem('...', null, false, true));
                    }
                }

                ul.appendChild(createItem('&raquo;', data.current_page + 1, false, !data.next_page_url));
                modalPagination.appendChild(ul);
            }

            document.querySelectorAll('.view-detail-btn').forEach(button => {
                button.addEventListener('click', function() {
                    currentFilterType = this.dataset.filterType;
                    currentKodeIdentifikasi = this.dataset.kodeIdentifikasi;
                    currentDetailTanggal = (currentFilterType === 'yearly') ? this.dataset.tanggal :
                        this.dataset.tanggal;

                    const dateObj = new Date(currentDetailTanggal);
                    const options = (currentFilterType === 'yearly') ? {
                        month: 'long',
                        year: 'numeric'
                    } : {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    };

                    modalPeriodeSpan.textContent = dateObj.toLocaleDateString('id-ID', options);
                    modalKodeTipeSpan.textContent = listProdi[currentKodeIdentifikasi] ||
                        currentKodeIdentifikasi;

                    detailModalEl.show();
                    loadDetailData(1);
                });
            });

            // --- Export CSV Logic (Full & Detail) ---
            const downloadFullCsvBtn = document.getElementById('downloadFullCsvBtn');
            if (downloadFullCsvBtn) {
                downloadFullCsvBtn.addEventListener('click', async function() {
                    const params = new URLSearchParams(window.location.search);
                    const exportUrl =
                        `{{ route('kunjungan.get_prodi_export_data') }}?${params.toString()}`;
                    window.location.href = exportUrl; // Direct download is simpler
                });
            }

            const exportDetailBtn = document.getElementById('exportDetailPengunjungCsvBtn');
            if (exportDetailBtn) {
                exportDetailBtn.addEventListener('click', function() {
                    if (!currentDetailTanggal || !currentKodeIdentifikasi) return;
                    let tanggalParam = (currentFilterType === 'yearly') ?
                        `bulan=${currentDetailTanggal.substring(0, 7)}` :
                        `tanggal=${currentDetailTanggal}`;

                    const url =
                        `{{ route('kunjungan.get_detail_pengunjung') }}?${tanggalParam}&kode_identifikasi=${currentKodeIdentifikasi}&export=true`;
                    window.location.href = url;
                });
            }
        });
    </script>
@endpush
