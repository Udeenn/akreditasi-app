@extends('layouts.app')

@section('title', 'Statistik Kunjungan Prodi')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
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
            color: var(--);
            overflow: hidden !important;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--);
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

        .bg-primary-soft { background-color: var(--primary-soft); color: #0d6efd; }
        .bg-success-soft { background-color: var(--success-soft); color: #198754; }
        .bg-warning-soft { background-color: var(--warning-soft); color: #ffc107; }
        .bg-info-soft    { background-color: var(--info-soft);    color: #0dcaf0; }

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

        /* Dark Mode Support */
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
        body.dark-mode .text-muted { color: #a1a5b7 !important; }
        body.dark-mode .table { color: #ffffff; border-color: #2b2b40; }
        body.dark-mode .table thead th {
            background-color: #2b2b40;
            color: #ffffff;
            border-bottom-color: #3f4254;
        }
        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #1b1b29;
            border-color: #2b2b40;
            color: #ffffff;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-4 py-4">

        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 bg-primary bg-gradient text-white  d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-university me-2"></i>Statistik Kunjungan Prodi
                            </h3>
                            <p class="mb-0 opacity-75">
                                Analisis data kunjungan berdasarkan Program Studi dan Unit Kerja.
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-chart-bar fa-4x"></i>
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
                        <form method="GET" action="{{ route('kunjungan.prodi') }}" class="row g-3 align-items-end" id="filterForm">
                            
                            {{-- Tipe --}}
                            <div class="col-md-2">
                                <label for="filter_type" class="form-label small text-muted fw-bold">Tipe Laporan</label>
                                <select name="filter_type" id="filter_type" class="form-select border-0  fw-semibold">
                                    <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>Harian</option>
                                    <option value="yearly" {{ ($filterType ?? '') == 'yearly' ? 'selected' : '' }}>Bulanan</option>
                                </select>
                            </div>

                            {{-- Tanggal (Harian) --}}
                            <div class="col-md-4 daily-filter" style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                                <label class="form-label small text-muted fw-bold">Rentang Tanggal</label>
                                <div class="input-group">
                                    <input type="date" name="tanggal_awal" class="form-control border-0 "
                                        value="{{ $tanggalAwal ?? date('Y-m-d') }}">
                                    <span class="input-group-text border-0  text-muted">s/d</span>
                                    <input type="date" name="tanggal_akhir" class="form-control border-0 "
                                        value="{{ $tanggalAkhir ?? date('Y-m-d') }}">
                                </div>
                            </div>

                            {{-- Tahun (Bulanan) --}}
                            <div class="col-md-4 yearly-filter" style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                                <label class="form-label small text-muted fw-bold">Rentang Tahun</label>
                                <div class="input-group">
                                    <select name="tahun_awal" class="form-select border-0">
                                        @for ($y = date('Y'); $y >= 2020; $y--)
                                            <option value="{{ $y }}" {{ ($tahunAwal ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                    <span class="input-group-text border-0 text-muted">s/d</span>
                                    <select name="tahun_akhir" class="form-select border-0">
                                        @for ($y = date('Y'); $y >= 2020; $y--)
                                            <option value="{{ $y }}" {{ ($tahunAkhir ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            {{-- Prodi --}}
                            <div class="col-md-4">
                                <label for="prodi" class="form-label small text-muted fw-bold">Program Studi / Unit</label>
                                <select name="prodi" id="prodi" class="form-select select2">
                                    <option value="">Semua Kategori</option>
                                    @foreach($listProdi as $code => $name)
                                        <option value="{{ $code }}" {{ request('prodi') == $code ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-auto ms-auto">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="fas fa-search me-1"></i> Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger shadow-sm border-0">{{ session('error') }}</div>
        @endif

        @if(isset($hasFilter) && $hasFilter)
            
            {{-- 3. STATISTIK CARDS --}}
            <div class="row g-4 mb-4">
                {{-- Total --}}
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-primary-soft me-3 rounded-circle" style="width: 60px; height: 60px;">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Kunjungan</h6>
                                <h2 class="fw-bold mb-0">{{ number_format($totalKeseluruhanKunjungan ?? 0, 0, ',', '.') }}</h2>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rerata --}}
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="icon-box bg-success-soft me-3 rounded-circle" style="width: 60px; height: 60px;">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Rerata / {{ ($filterType ?? 'daily') == 'daily' ? 'Hari' : 'Bulan' }}</h6>
                                <h2 class="fw-bold mb-0">{{ number_format($rerataKunjungan ?? 0, 1, ',', '.') }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. CHART SECTION --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-4 px-4 bg-transparent">
                            <h5 class="fw-bold mb-0">Tren Kunjungan</h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div style="height: 350px;">
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
                        <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center  border-bottom">
                            <h6 class="fw-bold m-0 text-primary">
                                <i class="fas fa-table me-2"></i>Rincian Data
                            </h6>
                            <a href="{{ route('kunjungan.get_prodi_export_data', request()->query()) }}" class="btn btn-success btn-sm fw-bold shadow-sm">
                                <i class="fas fa-file-csv me-2"></i>Export CSV
                            </a>
                        </div>
                        
                        <div class="card-body p-0">
                            {{-- Control Bar --}}
                            <div class="p-3 border-bottom bg-opacity-10">
                                <div class="d-flex justify-content-between align-items-center">
                                    {{-- Length Change (Reloads page with new per_page) --}}
                                    <form method="GET" class="d-flex align-items-center">
                                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endforeach
                                        <label class="me-2 text-muted fw-bold small">Tampilkan</label>
                                        <select name="per_page" class="form-select form-select-sm shadow-sm border-0" style="width: 70px;" onchange="this.form.submit()">
                                            <option value="12" {{ $data->perPage() == 12 ? 'selected' : '' }}>12</option>
                                            <option value="25" {{ $data->perPage() == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ $data->perPage() == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ $data->perPage() == 100 ? 'selected' : '' }}>100</option>
                                        </select>
                                        <label class="ms-2 text-muted fw-bold small">Entri</label>
                                    </form>
                                    
                                    <div class="text-muted small">
                                        Periode: <span class="fw-bold ">{{ $displayPeriod ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="">
                                        <tr>
                                            <th class="py-3 px-4 border-bottom-0" width="5%">No</th>
                                            <th class="py-3 px-4 border-bottom-0">Tanggal / Bulan</th>
                                            <th class="py-3 px-4 border-bottom-0 text-center">Kode</th>
                                            <th class="py-3 px-4 border-bottom-0">Program Studi / Unit</th>
                                            <th class="py-3 px-4 border-bottom-0 text-center">Jumlah</th>
                                            <th class="py-3 px-4 border-bottom-0 text-center" width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($data as $index => $item)
                                            <tr>
                                                <td class="px-4 text-center text-muted fw-bold">{{ $data->firstItem() + $index }}</td>
                                                <td class="px-4 fw-medium">
                                                    @if(($filterType ?? 'daily') == 'daily')
                                                        {{ \Carbon\Carbon::parse($item->tanggal_kunjungan)->translatedFormat('d F Y') }}
                                                    @else
                                                        {{ \Carbon\Carbon::parse($item->tanggal_kunjungan)->translatedFormat('F Y') }}
                                                    @endif
                                                </td>
                                                <td class="text-center"><span class="badge border text-body">{{ $item->kode_identifikasi }}</span></td>
                                                <td class="px-4">{{ $item->nama_prodi }}</td>
                                                <td class="px-4 text-center">
                                                    <span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill">{{ number_format($item->jumlah_kunjungan_harian, 0, ',', '.') }}</span>
                                                </td>
                                                <td class="px-4 text-center">
                                                    <button class="btn btn-sm btn-primary shadow-sm view-detail-btn"
                                                        data-tanggal="{{ $item->tanggal_kunjungan }}"
                                                        data-kode="{{ $item->kode_identifikasi }}"
                                                        data-nama="{{ $item->nama_prodi }}"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#detailModal">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <div class="opacity-50 mb-3">
                                                        <i class="fas fa-search fa-3x text-muted"></i>
                                                    </div>
                                                    <p class="text-muted mb-0">Tidak ada data kunjungan yang ditemukan.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                                <div class="small text-muted">
                                    Menampilkan {{ $data->firstItem() ?? 0 }} s/d {{ $data->lastItem() ?? 0 }} dari {{ $data->total() }} data
                                </div>
                                <div>
                                    {{ $data->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @else
            {{-- INITIAL STATE --}}
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm text-center p-5 rounded-4">
                        <div class="card-body">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-4 mb-3">
                                <i class="fas fa-chart-pie fa-3x text-primary"></i>
                            </div>
                            <h4 class="fw-bold text-body">Mulai Analisis Data</h4>
                            <p class="text-muted mb-0">Silakan pilih parameter filter di atas lalu klik tombol 
                                <strong>"Tampilkan"</strong> untuk memuat data statistik.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-list me-2"></i> Detail Pengunjung
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 border-bottom">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <small class="d-block text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Tanggal/Bulan</small>
                                <strong id="modalDate" class="fs-5 text-body">-</strong>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="d-block text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Prodi/Unit</small>
                                <strong id="modalProdi" class="text-primary">-</strong>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="detailTable">
                            <thead class="">
                                <tr>
                                    <th class="border-bottom-0 py-3 ps-4 text-body text-nowrap">No</th>
                                    <th class="border-bottom-0 py-3 text-body text-nowrap">ID Anggota</th>
                                    <th class="border-bottom-0 py-3 text-body text-nowrap">Nama Pengunjung</th>
                                    <th class="border-bottom-0 py-3 pe-4 text-center text-body text-nowrap">Kunjungan</th>
                                </tr>
                            </thead>
                            <tbody id="detailTableBody">
                                <tr><td colspan="4" class="text-center py-5">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        <button type="button" class="btn btn-sm btn-primary" id="modalPrevBtn" disabled>
                            <i class="fas fa-chevron-left me-1"></i> Prev
                        </button>
                        <span class="mx-2 small fw-bold text-muted" id="modalPageInfo"></span>
                        <button type="button" class="btn btn-sm btn-primary" id="modalNextBtn" disabled>
                            Next <i class="fas fa-chevron-right ms-1"></i>
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-success me-2" id="modalExportBtn">
                            <i class="fas fa-file-csv me-1"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://momentjs.com/downloads/moment-with-locales.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        moment.locale('id'); // Set Locale ID

        // Toggle Filters
        const filterType = document.getElementById('filter_type');
        
        function toggleFilters() {
            const isDaily = filterType.value === 'daily';
            document.querySelectorAll('.daily-filter').forEach(el => el.style.display = isDaily ? 'block' : 'none');
            document.querySelectorAll('.yearly-filter').forEach(el => el.style.display = isDaily ? 'none' : 'block');
        }

        if(filterType) {
            filterType.addEventListener('change', toggleFilters);
            toggleFilters(); // run on load
        }

        // Initialize Select2
        if($('.select2').length) {
            $('.select2').select2({ theme: 'bootstrap-5' });
        }

        // Detail Modal Logic
        const detailModal = document.getElementById('detailModal');
        if (detailModal) {
            detailModal.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                const tanggal = btn.getAttribute('data-tanggal');
                const kode = btn.getAttribute('data-kode');
                const nama = btn.getAttribute('data-nama');
                
                const type = document.getElementById('filter_type').value;
                if(type === 'yearly') {
                    document.getElementById('modalDate').innerText = moment(tanggal).format('MMMM YYYY');
                } else {
                    document.getElementById('modalDate').innerText = moment(tanggal).format('DD MMMM YYYY');
                }
                document.getElementById('modalProdi').innerText = nama;
                
                const tbody = document.getElementById('detailTableBody');
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted small">Memuat data detail...</div></td></tr>';

                // Pagination Controls
                const prevBtn = document.getElementById('modalPrevBtn');
                const nextBtn = document.getElementById('modalNextBtn');
                const pageInfo = document.getElementById('modalPageInfo');
                
                let currentPage = 1;

                function loadDetail(page = 1) {
                    const type = document.getElementById('filter_type').value;
                    let url = `{{ route('kunjungan.get_detail_pengunjung') }}?kode_identifikasi=${kode}&page=${page}`;
                    
                    if(type === 'yearly') {
                        url += `&bulan=${tanggal.substring(0, 7)}`;
                    } else {
                        url += `&tanggal=${tanggal}`;
                    }
                    
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted small">Memuat data detail...</div></td></tr>';

                    fetch(url)
                        .then(res => res.json())
                        .then(res => {
                            tbody.innerHTML = '';
                            if(res.data && res.data.length > 0) {
                                res.data.forEach((item, index) => {
                                    let row = `
                                    <tr>
                                        <td class="ps-4 text-center text-body fw-bold">${(res.from || 1) + index}</td>
                                        <td class="fw-medium text-body">${item.cardnumber}</td>
                                        <td class="text-body">${item.nama}</td>
                                        <td class="pe-4 text-center">
                                            <span class="badge border bg-light text-dark fw-normal px-3">${item.visit_count}</span>
                                        </td>
                                    </tr>
                                `;
                                    tbody.innerHTML += row;
                                });

                                // Update Pagination UI
                                currentPage = res.current_page;
                                pageInfo.innerText = `Halaman ${res.current_page} dari ${res.last_page}`;
                                prevBtn.disabled = !res.prev_page_url;
                                nextBtn.disabled = !res.next_page_url;
                            } else {
                                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted">Tidak ada detail pengunjung.</td></tr>';
                                pageInfo.innerText = '';
                                prevBtn.disabled = true;
                                nextBtn.disabled = true;
                            }
                        })
                        .catch(e => {
                            console.error(e);
                            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-danger">Gagal memuat data. Silakan coba lagi.</td></tr>';
                        });
                }

                // Initial Load
                loadDetail(1);

                // Event Listeners for Pagination
                prevBtn.onclick = function() {
                    if(currentPage > 1) loadDetail(currentPage - 1);
                };

                nextBtn.onclick = function() {
                    loadDetail(currentPage + 1);
                };

                // Export Button Logic
                document.getElementById('modalExportBtn').onclick = function() {
                    const type = document.getElementById('filter_type').value;
                    let url = `{{ route('kunjungan.get_detail_pengunjung') }}?kode_identifikasi=${kode}&export=csv`;
                    
                    if(type === 'yearly') {
                        url += `&bulan=${tanggal.substring(0, 7)}`;
                    } else {
                        url += `&tanggal=${tanggal}`;
                    }
                    
                    window.location.href = url;
                };
            });
        }

        // ChartJS Logic
        @if(isset($hasFilter) && $hasFilter && isset($chartData))
            const ctx = document.getElementById('kunjunganChart');
            if(ctx) {
                // 1. Ambil Data & Sortir Explicit di JS (Safety)
                const rawData = @json($chartData);
                rawData.sort((a, b) => new Date(a.raw_date) - new Date(b.raw_date));

                // 2. Format Data untuk Time Scale ({x: date, y: value})
                const chartDataset = rawData.map(item => ({
                    x: item.raw_date,
                    y: item.total_kunjungan,
                    label_formatted: item.label // Simpan label asli untuk tooltip
                }));
                
                const isDaily = "{{ $filterType }}" === 'daily';

                // Gradient Fill
                const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(13, 110, 253, 0.2)');
                gradient.addColorStop(1, 'rgba(13, 110, 253, 0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        // Hapus Labels Array (Biarkan Time Scale generate dari x)
                        datasets: [{
                            label: 'Jumlah Kunjungan',
                            data: chartDataset,
                            borderColor: '#0d6efd',
                            backgroundColor: gradient,
                            borderWidth: 2,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#0d6efd',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(33, 37, 41, 0.9)',
                                padding: 12,
                                titleFont: { size: 13 },
                                bodyFont: { size: 13 },
                                cornerRadius: 8,
                                displayColors: false
                            }
                        },
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: isDaily ? 'day' : 'month',
                                    tooltipFormat: isDaily ? 'dddd, DD MMM YYYY' : 'MMM YYYY',
                                    displayFormats: {
                                        day: 'DD MMM',
                                        month: 'MMM YYYY'
                                    }
                                },
                                grid: { display: false },
                                ticks: { font: { size: 11 }, color: '#6c757d' }
                            },
                            y: {
                                beginAtZero: true,
                                border: { display: false },
                                grid: { color: '#f0f2f5' },
                                ticks: { font: { size: 11 }, color: '#6c757d' }
                            }
                        }
                    }
                });
            }
        @endif
    });
</script>
@endpush
