@extends('layouts.app')

@section('title', 'Pengadaan Koleksi')

@section('content')
<div class="container-fluid px-4 py-4">

    <x-breadcrumb title="Pengadaan Koleksi" icon="fas fa-chart-line">
        <x-slot name="subtitle">
            Analisis data historis penambahan koleksi (Judul & Eksemplar) dari tahun ke tahun.
        </x-slot>
    </x-breadcrumb>

    {{-- Filter Data --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card unified-card border-0 shadow-sm">
                <div class="card-header border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-filter text-primary me-2"></i> Filter Rentang Tahun</h5>
                </div>
                <div class="card-body px-4 pb-4 pt-3">
                    <form method="GET" action="{{ route('koleksi.tren_pertambahan') }}" id="filterForm" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small text-muted text-uppercase">Dari Tahun</label>
                            <select name="start_year" class="form-select">
                                @for ($y = date('Y'); $y >= ($minYear ?? 2000); $y--)
                                    <option value="{{ $y }}" {{ $startYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small text-muted text-uppercase">Sampai Tahun</label>
                            <select name="end_year" class="form-select">
                                @for ($y = date('Y'); $y >= ($minYear ?? 2000); $y--)
                                    <option value="{{ $y }}" {{ $endYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold">
                                <i class="fas fa-search me-1"></i> Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(isset($yearSwapped) && $yearSwapped)
    <div class="alert alert-info border-0 shadow-sm rounded-3 d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-info-circle me-3 fs-5 flex-shrink-0"></i>
        <div>
            Rentang tahun yang kamu pilih terbalik — otomatis dikoreksi menjadi <strong>{{ $startYear }}</strong> sampai <strong>{{ $endYear }}</strong>.
        </div>
    </div>
    @endif

    @if(count($data) > 0)
    <div class="row mb-4">
        {{-- Chart Section --}}
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card unified-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-chart-bar text-primary me-2"></i> Grafik Penambahan</h5>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; width:100%">
                        <div id="trenChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="col-lg-4">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card unified-card border-0 shadow-sm overflow-hidden bg-primary text-white">
                        <div class="card-body p-4 position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Penambahan Judul</h6>
                                    <h2 class="mb-0 fw-bold display-6">{{ number_format($data->sum('total_titles'), 0, ',', '.') }}</h2>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="fas fa-book-open fs-2"></i>
                                </div>
                            </div>
                            <small class="text-white-50">Dalam rentang tahun terpilih</small>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card unified-card border-0 shadow-sm overflow-hidden bg-info text-white">
                        <div class="card-body p-4 position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Penambahan Eksemplar</h6>
                                    <h2 class="mb-0 fw-bold display-6">{{ number_format($data->sum('total_items'), 0, ',', '.') }}</h2>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="fas fa-layer-group fs-2"></i>
                                </div>
                            </div>
                            <small class="text-white-50">Dalam rentang tahun terpilih</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card unified-card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-table text-primary me-2"></i> Detail Penambahan Per Tahun</h5>
                    <div class="d-flex gap-2">
                        {{-- Form POST terpisah untuk export PDF --}}
                        <form method="POST" action="{{ route('koleksi.tren_pertambahan_export_pdf') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="start_year" value="{{ $startYear }}">
                            <input type="hidden" name="end_year" value="{{ $endYear }}">
                            <button type="submit" class="btn btn-danger btn-sm shadow-sm fw-bold">
                                <i class="fas fa-file-pdf me-1"></i> Export PDF
                            </button>
                        </form>
                        <button type="submit" form="filterForm" name="export_csv" value="1" class="btn btn-success btn-sm shadow-sm fw-bold">
                            <i class="fas fa-file-csv me-1"></i> Export CSV Rekap
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle ">
                            <thead>
                                <tr>
                                    <th class="text-center" width="7%">No</th>
                                    <th class="text-center" width="20%">Tahun Masuk (Accessioned)</th>
                                    <th class="text-center" width="20%">Judul Baru</th>
                                    <th class="text-center" width="20%">Eksemplar Baru</th>
                                    <th class="text-center" width="15%">Tren Pengadaan</th>
                                    <th class="text-center" width="18%">Rincian Buku</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $index => $row)
                                @php
                                    $prevRow = $index > 0 ? $data[$index - 1] : null;
                                    $growthTitles = null;
                                    if ($prevRow && $prevRow->total_titles > 0) {
                                        $growthTitles = round((($row->total_titles - $prevRow->total_titles) / $prevRow->total_titles) * 100, 1);
                                    }
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="text-center fw-bold ">{{ $row->year }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill ">
                                            {{ number_format($row->total_titles, 0, ',', '.') }} Judul
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill ">
                                            {{ number_format($row->total_items, 0, ',', '.') }} Eks
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($growthTitles === null)
                                            <span class="text-muted small">—</span>
                                        @elseif($growthTitles > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill fw-bold">
                                                <i class="fas fa-arrow-up me-1"></i>+{{ $growthTitles }}%
                                            </span>
                                        @elseif($growthTitles < 0)
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 rounded-pill fw-bold">
                                                <i class="fas fa-arrow-down me-1"></i>{{ $growthTitles }}%
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1 rounded-pill fw-bold">0%</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3 btn-detail-pertambahan" data-year="{{ $row->year }}">
                                            <i class="fas fa-list me-1"></i> Lihat Daftar Buku
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="fw-bold border-top">
                                <tr>
                                    <td colspan="2" class="text-end">TOTAL</td>
                                    <td class="text-center">{{ number_format($data->sum('total_titles'), 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($data->sum('total_items'), 0, ',', '.') }}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    {{-- Keterangan Tren Judul --}}
                    <div class="mt-3 px-1 d-flex flex-wrap gap-3 align-items-start">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle text-primary me-1"></i>
                            <strong>Tren Pengadaan</strong> menunjukkan <strong>persentase kenaikan atau penurunan</strong> jumlah judul baru
                            dibanding tahun sebelumnya dalam rentang yang dipilih.
                            Rumus: <code class="text-primary">((Judul Tahun Ini &minus; Judul Tahun Lalu) &divide; Judul Tahun Lalu) &times; 100%</code>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center small">
                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill fw-bold">
                                <i class="fas fa-arrow-up me-1"></i>+X%
                            </span>
                            <span class="text-muted">Judul bertambah dibanding tahun lalu</span>
                            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 rounded-pill fw-bold ms-2">
                                <i class="fas fa-arrow-down me-1"></i>-X%
                            </span>
                            <span class="text-muted">Judul berkurang dibanding tahun lalu</span>
                            <span class="text-muted ms-2">— = tahun pertama (tidak ada pembanding)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
            <div>Tidak ada data pertambahan koleksi pada rentang tahun tersebut.</div>
        </div>
    @endif

</div>

{{-- Modal Detail Pengadaan Koleksi --}}
<div class="modal fade" id="modalDetailPertambahan" tabindex="-1" aria-labelledby="modalDetailPertambahanLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold" id="modalDetailPertambahanLabel">
                    <i class="fas fa-book-open text-primary me-2"></i> Rincian Buku Pertambahan Tahun <span id="modalDetailYearText" class="text-primary">-</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="input-group" style="max-width: 320px;">
                            <span class="input-group-text border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="modalSearchInput" class="form-control border-start-0" placeholder="Cari judul, pengarang...">
                        </div>
                        {{-- Filter Jenis Koleksi --}}
                        <select id="modalItypeFilter" class="form-select" style="max-width: 180px;">
                            <option value="">Semua Jenis</option>
                            <option value="textbook">Textbook</option>
                            <option value="jurnal">Jurnal</option>
                            <option value="ebook">E-Book</option>
                            <option value="prosiding">Prosiding</option>
                            <option value="referensi">Referensi</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="#" id="btnExportModalCsv" class="btn btn-success btn-sm shadow-sm fw-bold" target="_blank">
                            <i class="fas fa-file-csv me-1"></i> Export CSV Tahun Ini
                        </a>
                        <a href="#" id="btnFullPageDetail" class="btn btn-outline-primary btn-sm fw-bold" target="_blank">
                            <i class="fas fa-external-link-alt me-1"></i> Buka Halaman Penuh
                        </a>
                    </div>
                </div>
                <div id="modalDetailContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Memuat daftar rincian buku...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        @if(count($data) > 0)
        const labels = {!! json_encode($data->pluck('year')) !!};
        const dataTitles = {!! json_encode($data->pluck('total_titles')) !!};
        const dataItems = {!! json_encode($data->pluck('total_items')) !!};

        if (typeof ApexCharts !== 'undefined') {
            const options = {
                series: [
                    {
                        name: 'Judul Baru',
                        data: dataTitles
                    },
                    {
                        name: 'Eksemplar Baru',
                        data: dataItems
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 400,
                    toolbar: { show: false },
                    fontFamily: 'inherit'
                },
                colors: ['#0d6efd', '#0dcaf0'],
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '55%',
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        if (val >= 1000) return (val / 1000).toFixed(1).replace('.0', '') + 'k';
                        return val;
                    },
                    style: {
                        fontSize: '11px',
                        fontWeight: '600',
                    },
                    dropShadow: { enabled: false }
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: labels,
                },
                yaxis: {
                    title: {
                        text: 'Jumlah Penambahan'
                    }
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center'
                }
            };

            const chart = new ApexCharts(document.querySelector("#trenChart"), options);
            chart.render();
        }
        @endif

        // Modal Detail Logic
        let activeYear = null;
        let searchTimer = null;
        const modalElement = document.getElementById('modalDetailPertambahan');
        const modalInstance = new bootstrap.Modal(modalElement);
        const detailContainer = document.getElementById('modalDetailContainer');
        const searchInput = document.getElementById('modalSearchInput');
        const itypeFilter = document.getElementById('modalItypeFilter');

        function loadModalContent(year, page = 1, search = '', itype = '') {
            activeYear = year;
            document.getElementById('modalDetailYearText').innerText = year;

            const itypeParam = itype ? `&itype=${encodeURIComponent(itype)}` : '';
            const detailUrl = `{{ route('koleksi.tren_pertambahan_detail') }}?year=${year}&page=${page}&search=${encodeURIComponent(search)}${itypeParam}`;
            const csvUrl = `{{ route('koleksi.tren_pertambahan_detail') }}?year=${year}&export_csv=1&search=${encodeURIComponent(search)}${itypeParam}`;

            document.getElementById('btnExportModalCsv').href = csvUrl;
            document.getElementById('btnFullPageDetail').href = detailUrl;

            detailContainer.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Memuat daftar buku tahun ${year}...</p>
                </div>
            `;

            fetch(detailUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    detailContainer.innerHTML = data.html;
                    attachPaginationEvents();
                } else {
                    detailContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat data detail.</div>`;
                }
            })
            .catch(err => {
                detailContainer.innerHTML = `<div class="alert alert-danger">Terjadi kesalahan koneksi server.</div>`;
            });
        }

        function attachPaginationEvents() {
            detailContainer.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = new URL(this.href);
                    const page = url.searchParams.get('page') || 1;
                    loadModalContent(activeYear, page, searchInput.value, itypeFilter.value);
                });
            });
        }

        document.querySelectorAll('.btn-detail-pertambahan').forEach(btn => {
            btn.addEventListener('click', function() {
                const year = this.getAttribute('data-year');
                searchInput.value = '';
                itypeFilter.value = '';
                modalInstance.show();
                loadModalContent(year, 1, '', '');
            });
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                if (activeYear) {
                    loadModalContent(activeYear, 1, this.value, itypeFilter.value);
                }
            }, 400);
        });

        itypeFilter.addEventListener('change', function() {
            if (activeYear) {
                loadModalContent(activeYear, 1, searchInput.value, this.value);
            }
        });
    });
</script>
@endpush
