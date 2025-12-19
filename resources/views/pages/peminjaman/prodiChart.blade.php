@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }

        /* Dark Mode Select2 Styles */
        body.dark-mode .select2-container--bootstrap-5 .select2-selection {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__rendered {
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__arrow b {
            border-color: #adb5bd transparent transparent transparent !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-dropdown {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            background-color: #334155 !important;
            border-color: var(--border-color) !important;
            color: var(--text-dark) !important;
        }

        body.dark-mode .select2-results__option {
            color: #ffffff !important;
        }

        body.dark-mode .select2-results__option--highlighted {
            background-color: var(--primary-color) !important;
            color: white !important;
        }

        body.dark-mode .select2-results__option[aria-selected=true] {
            background-color: rgba(var(--bs-primary-rgb), 0.2) !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@extends('layouts.app')
@section('title', 'Statistik Peminjaman per Program Studi')
@section('content')
    <div class="container px-4">
        <div class="d-flex align-items-center mb-4">
            <i class="fas fa-calendar fa-2x text-primary me-3"></i>
            <div>
                <h4 class="mb-0">Statistik Peminjaman Prodi / Dosen Tendik</h4>
                <small class="text-muted">
                    Menampilkan statistik peminjaman buku perpustakaan berdasarkan program studi atau dosen tendik
                </small>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold"><i class="fas fa-filter me-1"></i> Filter Data</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('peminjaman.peminjaman_prodi_chart') }}" method="GET"
                    class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label small text-uppercase fw-bold text-muted">Tampilkan
                            Data</label>
                        <select name="filter_type" id="filter_type" class="form-select form-select-sm shadow-sm">
                            <option value="yearly" {{ $filterType == 'yearly' ? 'selected' : '' }}>Per Bulan (Bulanan)
                            </option>
                            <option value="daily" {{ $filterType == 'daily' ? 'selected' : '' }}>Per Hari (Harian)</option>
                        </select>
                    </div>

                    <div class="col-md-3" id="yearlyFilter" style="{{ $filterType == 'yearly' ? '' : 'display: none;' }}">
                        <label class="form-label small text-uppercase fw-bold text-muted">Rentang Tahun</label>
                        <div class="input-group input-group-sm shadow-sm">
                            @php
                                $currentYear = \Carbon\Carbon::now()->year;
                                $loopStartYear = $currentYear - 10;
                                $loopEndYear = $currentYear;
                            @endphp
                            <select name="start_year" id="start_year" class="form-select">
                                @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                    <option value="{{ $year }}" {{ $startYear == $year ? 'selected' : '' }}>
                                        {{ $year }}</option>
                                @endfor
                            </select>
                            <span class="input-group-text">s.d.</span>
                            <select name="end_year" id="end_year" class="form-select">
                                @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                    <option value="{{ $year }}" {{ $endYear == $year ? 'selected' : '' }}>
                                        {{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4" id="dailyFilter" style="{{ $filterType == 'daily' ? '' : 'display: none;' }}">
                        <label class="form-label small text-uppercase fw-bold text-muted">Rentang Tanggal</label>
                        <div class="input-group input-group-sm shadow-sm">
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="{{ $startDate ?? '' }}">
                            <span class="input-group-text">s.d.</span>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ $endDate ?? '' }}">
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label for="selected_prodi" class="form-label small text-uppercase fw-bold text-muted">Program
                            Studi</label>
                        <select name="selected_prodi" id="selected_prodi" class="form-select form-select-sm shadow-sm"
                            style="width: 100%;">
                            @foreach ($prodiOptions as $prodi)
                                <option value="{{ $prodi->authorised_value }}"
                                    {{ $selectedProdiCode == $prodi->authorised_value ? 'selected' : '' }}>
                                    {{ $prodi->lib }} ({{ $prodi->authorised_value }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 shadow-sm">
                            <i class="fas fa-search me-1"></i> Terapkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger border-left-danger shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            </div>
        @endif

        @if (!$dataExists)
            <div class="text-center py-5">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" alt="No Data"
                    style="width: 150px; opacity: 0.5;">
                <h4 class="mt-3 text-gray-800">Data Tidak Ditemukan</h4>
                <p class="text-muted">Silakan gunakan filter di atas untuk menampilkan statistik peminjaman.</p>
            </div>
        @else
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2 stat-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-uppercase mb-1">Total Buku
                                        Terpinjam</div>
                                    <div class="h4 mb-0 fw-bold text-gray-800">{{ number_format($totalBooks) }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-book fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2 stat-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">Total Peminjam
                                        (User)</div>
                                    <div class="h4 mb-0 fw-bold text-gray-800">
                                        {{ number_format($totalBorrowers) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2 stat-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Transaksi
                                    </div>
                                    <div class="h4 mb-0 fw-bold text-gray-800">{{ $statistics->total() }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold">
                        <i class="fas fa-chart-line me-1"></i> Grafik Statistik
                        <span class="ms-1">{{ $filterType == 'daily' ? 'Harian' : 'Bulanan' }}</span>
                    </h6>
                    <div class="small text-muted">
                        {{ $prodiOptions->firstWhere('authorised_value', $selectedProdiCode)->lib ?? 'Prodi' }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="position: relative; height: 40vh;">
                        <canvas id="peminjamanProdiChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold"><i class="fas fa-table me-1"></i> Rincian Data
                        Peminjaman</h6>
                    <button type="button" id="exportCsvBtn" class="btn btn-success btn-sm shadow-sm">
                        <i class="fas fa-file-csv me-1"></i> Export CSV
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle" id="prodiPeminjamanTable">
                            <thead>
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th class="text-center">Periode</th>
                                    <th class="text-center">Buku Terpinjam</th>
                                    <th class="text-center">Jumlah Transaksi</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($statistics as $index => $stat)
                                    <tr>
                                        <td class="text-center text-muted">
                                            {{ $statistics->firstItem() + $index }}
                                        </td>
                                        <td class="ps-4 text-gray-800">
                                            @if ($filterType == 'daily')
                                                <i
                                                    class="far fa-calendar-alt me-2 text-muted"></i>{{ \Carbon\Carbon::parse($stat->periode)->format('d M Y') }}
                                            @else
                                                <i
                                                    class="far fa-calendar me-2 text-muted"></i>{{ \Carbon\Carbon::createFromFormat('Y-m', $stat->periode)->format('F Y') }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="">{{ $stat->jumlah_buku_terpinjam }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="">{{ $stat->jumlah_peminjam_unik }}</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary view-borrowers-btn px-3"
                                                data-bs-toggle="modal" data-bs-target="#borrowersModal"
                                                data-periode="{{ $stat->periode }}"
                                                data-prodi-code="{{ $selectedProdiCode }}" data-page="1">
                                                <i class="fas fa-eye me-1"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer  py-3">
                    <div class="d-flex justify-content-end">
                        {{ $statistics->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal Detail (Clean Design) --}}
    <div class="modal fade" id="borrowersModal" tabindex="-1" aria-labelledby="borrowersModalLabel">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="borrowersModalLabel">
                        <i class="fas fa-address-book me-2"></i> Detail Peminjam
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted text-uppercase fw-bold">Program Studi</small>
                                <div class="fs-5 fw-bold" id="modalProdiName">-</div>
                            </div>
                            <div class="text-end">
                                <small class="text-muted text-uppercase fw-bold">Periode</small>
                                <div class="fs-5 fw-bold" id="modalPeriod">-</div>
                            </div>
                        </div>
                    </div>

                    <div id="loadingSpinner" class="text-center py-5" style="display:none;">
                        <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2 fw-bold">Mengambil data...</p>
                    </div>

                    <div id="noDataMessage" class="alert alert-warning text-center mt-3 shadow-sm border-0"
                        style="display:none;">
                        <i class="fas fa-info-circle me-2"></i> Tidak ada data peminjam.
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="borrowersTable">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">#</th>
                                        <th width="25%">Nama</th>
                                        <th width="15%">NIM</th>
                                        <th>Buku yang Dipinjam</th>
                                    </tr>
                                </thead>
                                <tbody id="borrowersTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="modalPagination" class="d-flex justify-content-center mt-4"></div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="btnExportDetailProdiCsv" class="btn btn-success btn-sm me-2 shadow-sm">
                        <i class="fas fa-file-csv me-1"></i> Export CSV
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth Select2 Initialization
            $('#selected_prodi').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('body'), // Fix z-index issues in some templates
                width: '100%'
            });

            const filterTypeSelect = document.getElementById('filter_type');
            const dailyFilter = document.getElementById('dailyFilter');
            const yearlyFilter = document.getElementById('yearlyFilter');
            const exportCsvBtn = document.getElementById('exportCsvBtn');

            function toggleFilterInputs() {
                const selectedValue = filterTypeSelect.value;
                if (selectedValue === 'daily') {
                    dailyFilter.style.display = 'block';
                    yearlyFilter.style.display = 'none';
                } else {
                    dailyFilter.style.display = 'none';
                    yearlyFilter.style.display = 'block';
                }
            }
            toggleFilterInputs();
            filterTypeSelect.addEventListener('change', toggleFilterInputs);

            @if ($dataExists)
                const ctx = document.getElementById('peminjamanProdiChart').getContext('2d');
                const labels = @json($chartLabels);
                const datasets = @json($chartDatasets);

                // Modern Colors
                const color1 = 'rgba(78, 115, 223, 0.85)'; // Blue
                const color2 = 'rgba(28, 200, 138, 0.85)'; // Green
                const color3 = 'rgba(54, 185, 204, 0.85)'; // Cyan

                datasets[0].backgroundColor = color1;
                datasets[0].borderColor = '#4e73df';
                datasets[0].borderWidth = 1;
                datasets[0].borderRadius = 4; // Rounded bars

                datasets[1].backgroundColor = color2;
                datasets[1].borderColor = '#1cc88a';
                datasets[1].borderWidth = 1;
                datasets[1].borderRadius = 4;

                datasets[2].backgroundColor = color3;
                datasets[2].borderColor = '#36b9cc';
                datasets[2].borderWidth = 1;
                datasets[2].borderRadius = 4;

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Penting untuk layout responsif
                        layout: {
                            padding: {
                                left: 10,
                                right: 25,
                                top: 25,
                                bottom: 0
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: {
                                        family: "'Nunito', sans-serif"
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: "rgb(255,255,255)",
                                bodyColor: "#858796",
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                titleColor: '#6e707e',
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                padding: 15,
                                displayColors: true,
                                intersect: false,
                                mode: 'index',
                                caretPadding: 10,
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 12,
                                    color: "#858796"
                                }
                            },
                            y: {
                                grid: {
                                    color: "rgb(234, 236, 244)",
                                    zeroLineColor: "rgb(234, 236, 244)",
                                    drawBorder: false,
                                    borderDash: [2]
                                },
                                ticks: {
                                    padding: 10,
                                    color: "#858796",
                                    beginAtZero: true
                                }
                            }
                        }
                    }
                });
            @endif

            // --- CSV Export Logic (Sama seperti sebelumnya, hanya merapikan code) ---
            if (exportCsvBtn) {
                exportCsvBtn.addEventListener('click', function() {
                    const dataToExport = @json($allStatistics);
                    if (!dataToExport || dataToExport.length === 0) {
                        alert("Tidak ada data untuk diekspor.");
                        return;
                    }

                    const prodiName = document.getElementById('selected_prodi').options[document
                        .getElementById('selected_prodi').selectedIndex].text.trim();
                    const filterType = filterTypeSelect.value;
                    let periodText = (filterType === 'daily') ?
                        `Periode ${document.getElementById('start_date').value} s.d. ${document.getElementById('end_date').value}` :
                        `Tahun ${document.getElementById('start_year').value} s.d. ${document.getElementById('end_year').value}`;

                    const title = `Statistik Peminjaman: ${prodiName}, ${periodText}`;
                    let csv = [title, '', ['Periode', 'Jumlah Buku Terpinjam', 'Jumlah Peminjam'].join(
                        ';')];

                    dataToExport.forEach(row => {
                        let periode = (filterType === 'daily') ?
                            new Date(row.periode).toLocaleDateString('id-ID') :
                            new Date(new Date(row.periode).getFullYear(), new Date(row.periode)
                                .getMonth(), 1).toLocaleDateString('id-ID', {
                                month: 'long',
                                year: 'numeric'
                            });
                        csv.push([periode, row.jumlah_buku_terpinjam, row.jumlah_peminjam_unik]
                            .join(';'));
                    });

                    const blob = new Blob(["\uFEFF" + csv.join('\n')], {
                        type: 'text/csv;charset=utf-8;'
                    });
                    const link = document.createElement("a");
                    link.href = URL.createObjectURL(blob);
                    link.download = `Statistik_${prodiName.replace(/[^a-z0-9]/gi, '_')}_${Date.now()}.csv`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            }

            // --- Modal Logic ---
            document.querySelectorAll('.view-borrowers-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Reset UI
                    document.getElementById('borrowersTableBody').innerHTML = '';
                    document.getElementById('modalPagination').innerHTML = '';

                    const periode = this.dataset.periode;
                    const prodiCode = this.dataset.prodiCode;
                    const filterType = filterTypeSelect.value;

                    const btnExport = document.getElementById('btnExportDetailProdiCsv');
                    const baseUrlExport = "{{ route('peminjaman.export_detail_prodi') }}";

                    // Set href dinamis
                    btnExport.href =
                        `${baseUrlExport}?periode=${periode}&prodi_code=${prodiCode}&filter_type=${filterType}`;

                    document.getElementById('modalProdiName').innerText = document.getElementById(
                        'selected_prodi').options[document.getElementById('selected_prodi')
                        .selectedIndex].text.trim();
                    document.getElementById('modalPeriod').innerText = this.closest('tr')
                        .querySelector('td:nth-child(2)').innerText.trim();

                    fetchBorrowers(periode, prodiCode, filterType, 1);
                });
            });

            function fetchBorrowers(periode, prodiCode, filterType, page) {
                const tbody = document.getElementById('borrowersTableBody');
                const spinner = document.getElementById('loadingSpinner');
                const noData = document.getElementById('noDataMessage');

                spinner.style.display = 'block';
                noData.style.display = 'none';
                tbody.innerHTML = '';

                fetch(
                        `{{ route('peminjaman.peminjamDetail') }}?periode=${periode}&filter_type=${filterType}&prodi_code=${prodiCode}&page=${page}`
                    )
                    .then(res => res.json())
                    .then(result => {
                        spinner.style.display = 'none';
                        const paginator = result.data;

                        if (result.success && paginator?.data?.length > 0) {
                            let rowHtml = '';
                            let num = paginator.from;

                            paginator.data.forEach(item => {
                                const books = item.buku.map(b => {
                                    let badge = b.transaksi === 'issue' ?
                                        '<span class="badge bg-primary ms-1"><i class="fas fa-arrow-up me-1"></i>Pinjam</span>' :
                                        (b.transaksi === 'renew' ?
                                            '<span class="badge bg-warning text-dark ms-1"><i class="fas fa-sync me-1"></i>Perpanjang</span>' :
                                            '<span class="badge bg-success ms-1"><i class="fas fa-arrow-down me-1"></i>Kembali</span>'
                                        );
                                    return `<div class="mb-1  pb-1"><i class="fas fa-book text-muted me-1"></i> ${b.title} ${badge} <small class="text-muted ms-1">(${b.waktu_transaksi})</small></div>`;
                                }).join('');

                                rowHtml += `
                            <tr>
                                <td class="text-center ">${num++}</td>
                                <td class="">${item.nama_peminjam}</td>
                                <td><span class="">${item.cardnumber}</span></td>
                                <td><div class="small">${books || '-'}</div></td>
                            </tr>`;
                            });
                            tbody.innerHTML = rowHtml;
                            createModalPagination(paginator.current_page, paginator.last_page, periode,
                                prodiCode, filterType);
                        } else {
                            noData.style.display = 'block';
                        }
                    })
                    .catch(err => {
                        spinner.style.display = 'none';
                        tbody.innerHTML =
                            '<tr><td colspan="4" class="text-center text-danger fw-bold">Gagal memuat data.</td></tr>';
                        console.error(err);
                    });
            }

            function createModalPagination(current, total, periode, prodiCode, filterType) {
                const container = document.getElementById('modalPagination');
                if (total <= 1) {
                    container.innerHTML = '';
                    return;
                }

                let html = '<nav><ul class="pagination pagination-sm mb-0">';
                // Prev
                html +=
                    `<li class="page-item ${current === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${current - 1}">&laquo;</a></li>`;
                // Numbers
                for (let i = 1; i <= total; i++) {
                    if (i === 1 || i === total || (i >= current - 2 && i <= current + 2)) {
                        html +=
                            `<li class="page-item ${current === i ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                    }
                }
                // Next
                html +=
                    `<li class="page-item ${current === total ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${current + 1}">&raquo;</a></li>`;
                html += '</ul></nav>';

                container.innerHTML = html;
                container.querySelectorAll('.page-link').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = parseInt(this.dataset.page);
                        if (page > 0 && page <= total) fetchBorrowers(periode, prodiCode,
                            filterType, page);
                    });
                });
            }
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush
