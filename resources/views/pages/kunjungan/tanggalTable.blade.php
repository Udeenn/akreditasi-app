@extends('layouts.app')

@section('title', 'Statistik Kunjungan Keseluruhan')
@section('content')
    <div class="container">
        <div class="card bg-white shadow-sm mb-4">
            <div class="card-body">
                <h4 class="mb-0">Statistik Kunjungan Keseluruhan</h4>
                <small class="text-muted">Ringkasan data kunjungan berdasarkan periode</small>
            </div>
        </div>
        <hr>
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('kunjungan.tanggalTable') }}" class="row g-3 align-items-end"
                    id="filterForm">
                    <div class="col-md-auto">
                        <label for="filter_type" class="form-label">Tampilkan Data:</label>
                        <select name="filter_type" id="filter_type" class="form-select">
                            <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>Per Hari
                            </option>
                            <option value="yearly" {{ ($filterType ?? '') == 'yearly' ? 'selected' : '' }}>Per Bulan
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3" id="dailyFilterStart"
                        style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                        <label for="tanggal_awal" class="form-label">Tanggal Awal:</label>
                        <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control"
                            value="{{ $tanggalAwal ?? \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3" id="dailyFilterEnd"
                        style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                        <label for="tanggal_akhir" class="form-label">Tanggal Akhir:</label>
                        <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control"
                            value="{{ $tanggalAkhir ?? \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d') }}">
                    </div>

                    {{-- Dua dropdown untuk rentang tahun --}}
                    <div class="col-md-2" id="yearlyFilterStart"
                        style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                        <label for="tahun_awal" class="form-label">Tahun Awal:</label>
                        <select name="tahun_awal" id="tahun_awal" class="form-select">
                            @php
                                $currentYear = \Carbon\Carbon::now()->year;
                                for ($year = $currentYear; $year >= 2020; $year--) {
                                    $selected = ($tahunAwal ?? $currentYear) == $year ? 'selected' : '';
                                    echo "<option value='{$year}' {$selected}>{$year}</option>";
                                }
                            @endphp
                        </select>
                    </div>

                    <div class="col-md-2" id="yearlyFilterEnd"
                        style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                        <label for="tahun_akhir" class="form-label">Tahun Akhir:</label>
                        <select name="tahun_akhir" id="tahun_akhir" class="form-select">
                            @php
                                $currentYear = \Carbon\Carbon::now()->year;
                                for ($year = $currentYear; $year >= 2020; $year--) {
                                    $selected = ($tahunAkhir ?? $currentYear) == $year ? 'selected' : '';
                                    echo "<option value='{$year}' {$selected}>{$year}</option>";
                                }
                            @endphp
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if ($hasFilter)
            {{-- Chart Section --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Grafik Kunjungan</h5>
                </div>
                <div class="card-body">
                    <canvas id="kunjunganChart" style="max-height: 400px; height: 400px;"></canvas>
                </div>
            </div>

            {{-- Table Section --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 me-3">Statistik Data Kunjungan</h5>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="entriesDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Tampilkan {{ $data->perPage() }} entri
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="entriesDropdown">
                                <li><a class="dropdown-item @if ($perPage == 10) active @endif"
                                        href="{{ request()->fullUrlWithQuery(['per_page' => 10]) }}">10</a></li>
                                <li><a class="dropdown-item @if ($perPage == 100) active @endif"
                                        href="{{ request()->fullUrlWithQuery(['per_page' => 100]) }}">100</a></li>
                                <li><a class="dropdown-item @if ($perPage == 1000) active @endif"
                                        href="{{ request()->fullUrlWithQuery(['per_page' => 1000]) }}">1000</a></li>
                            </ul>
                        </div>
                        <button type="button" id="downloadFullCsv" class="btn btn-success">
                            <i class="fas fa-file-csv me-2"></i> Export ke CSV
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="alert alert-primary py-2 m-0">
                                <i class="fas fa-book me-2"></i> Total Keseluruhan:
                                <span class="fw-bold">{{ number_format($totalKeseluruhanKunjungan, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-info py-2 m-0">
                                <i class="fas fa-list-ol me-2"></i> Total Entri Data:
                                <span class="fw-bold">{{ number_format($data->total(), 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="alert alert-secondary py-2 m-0">
                                <i class="fas fa-filter me-2"></i>
                                <span class="fw-bold">
                                    @if (($filterType ?? 'daily') == 'daily')
                                        @if ($tanggalAwal && $tanggalAkhir)
                                            {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }} s/d
                                            {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}
                                        @else
                                            Tidak Ada
                                        @endif
                                    @elseif (($filterType ?? '') == 'yearly')
                                        Tahun {{ $tahunAwal }} s/d {{ $tahunAkhir }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive" id="tabelLaporan">
                        <table class="table table-bordered table-striped" id="myTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal Kunjungan</th>
                                    <th>Total Kunjungan</th>
                                    <th>Detail Pengunjung</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $index => $row)
                                    <tr>
                                        <td>{{ $data->firstItem() + $index }}</td>
                                        <td>
                                            @if (($filterType ?? 'daily') == 'daily')
                                                {{ \Carbon\Carbon::parse($row->tanggal_kunjungan)->translatedFormat('d F Y') }}
                                            @else
                                                {{ \Carbon\Carbon::parse($row->tanggal_kunjungan)->translatedFormat('F Y') }}
                                            @endif
                                        </td>
                                        <td>{{ number_format($row->jumlah_kunjungan_harian, 0, ',', '.') }}</td>
                                        <td>
                                            <button class="btn btn-primary btn-sm view-detail-btn" data-bs-toggle="modal"
                                                data-bs-target="#detailPengunjungModal"
                                                data-tanggal="{{ $row->tanggal_kunjungan }}"
                                                data-filter-type="{{ $filterType ?? 'daily' }}"
                                                data-total="{{ $row->jumlah_kunjungan_harian }}">
                                                <i class="fas fa-eye"></i> Lihat Detail
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada data kunjungan ditemukan untuk
                                            filter ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $data->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info text-center">
                <i class="fas fa-filter me-2"></i> Silakan gunakan filter di atas untuk menampilkan data.
            </div>
        @endif
    </div>

    {{-- Modal untuk Detail Pengunjung --}}
    <div class="modal fade" id="detailPengunjungModal" tabindex="-1" aria-labelledby="detailPengunjungModalLabel"
        >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailPengunjungModalLabel">Detail Pengunjung Harian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Tanggal:</strong> <span id="modalTanggal"></span></p>
                    <p><strong>Total Kunjungan:</strong> <span id="modalTotalPengunjungDetail"></span></p>
                    <hr>
                    <h6>Daftar Nama Pengunjung:</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-striped" id="tabelDetailPengunjung">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Cardnumber</th>
                                    <th>Jumlah Kunjungan</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyDetailPengunjung">
                                <tr id="loadingMessage">
                                    <td colspan="4" class="text-center">Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <nav aria-label="Page navigation for visitors">
                        <ul class="pagination justify-content-center" id="paginationVisitors">
                        </ul>
                    </nav>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="exportDetailPengunjungCsv">
                        <i class="fas fa-file-csv me-2"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.0/dist/chartjs-adapter-moment.min.js"></script>
    <script>
        // Gunakan variabel hasFilter dari PHP untuk JavaScript
        const hasFilter = @json($hasFilter);
        let chartData = @json($chartData);
        const filterType = "{{ $filterType ?? 'daily' }}";
        let currentFilterType = '';

        document.addEventListener("DOMContentLoaded", function() {
            const exportDetailPengunjungCsvBtn = document.getElementById('exportDetailPengunjungCsv');
            const filterTypeSelect = document.getElementById('filter_type');
            const dailyFilterStart = document.getElementById('dailyFilterStart');
            const dailyFilterEnd = document.getElementById('dailyFilterEnd');
            const yearlyFilterStart = document.getElementById('yearlyFilterStart');
            const yearlyFilterEnd = document.getElementById('yearlyFilterEnd');
            const tanggalAwalInput = document.getElementById('tanggal_awal');
            const tanggalAkhirInput = document.getElementById('tanggal_akhir');
            const tahunAwalSelect = document.getElementById('tahun_awal');
            const tahunAkhirSelect = document.getElementById('tahun_akhir');
            const filterForm = document.getElementById('filterForm');
            const downloadFullCsvButton = document.getElementById('downloadFullCsv');

            function toggleFilterInputs() {
                const selectedValue = filterTypeSelect.value;
                if (selectedValue === 'daily') {
                    dailyFilterStart.style.display = 'block';
                    dailyFilterEnd.style.display = 'block';
                    yearlyFilterStart.style.display = 'none';
                    yearlyFilterEnd.style.display = 'none';

                    tanggalAwalInput.disabled = false;
                    tanggalAkhirInput.disabled = false;
                } else { // 'yearly'
                    dailyFilterStart.style.display = 'none';
                    dailyFilterEnd.style.display = 'none';
                    yearlyFilterStart.style.display = 'block';
                    yearlyFilterEnd.style.display = 'block';

                    tanggalAwalInput.disabled = true;
                    tanggalAkhirInput.disabled = true;
                }
            }

            toggleFilterInputs();

            filterTypeSelect.addEventListener('change', function() {
                toggleFilterInputs();
            });

            if (downloadFullCsvButton) {
                downloadFullCsvButton.addEventListener('click', async function() {
                    const params = new URLSearchParams(new FormData(filterForm));
                    const currentFilterType = filterTypeSelect.value;

                    let fileName = 'laporan_kunjungan';
                    if (currentFilterType === 'daily') {
                        const tanggalAwal = tanggalAwalInput.value;
                        const tanggalAkhir = tanggalAkhirInput.value;
                        if (!tanggalAwal || !tanggalAkhir) {
                            alert("Mohon pilih tanggal awal dan tanggal akhir terlebih dahulu.");
                            return;
                        }
                        fileName += `_harian_${tanggalAwal}_${tanggalAkhir}`;
                    } else { // yearly
                        const tahunAwal = tahunAwalSelect.value;
                        const tahunAkhir = tahunAkhirSelect.value;
                        if (!tahunAwal || !tahunAkhir) {
                            alert("Mohon pilih tahun awal dan tahun akhir terlebih dahulu.");
                            return;
                        }
                        fileName += `_tahunan_${tahunAwal}_${tahunAkhir}`;
                    }

                    try {
                        const response = await fetch(
                            `{{ route('kunjungan.get_harian_export_data') }}?${params.toString()}`
                        );
                        const result = await response.json();

                        if (response.ok) {
                            if (result.data.length === 0) {
                                alert("Tidak ada data untuk diekspor dalam rentang filter ini.");
                                return;
                            }

                            let csv = [];
                            const delimiter = ';';

                            const headers = (currentFilterType === 'daily') ? ['No',
                                'Tanggal Kunjungan', 'Total Kunjungan Harian'
                            ] : ['No', 'Bulan Kunjungan', 'Total Kunjungan Bulanan'];

                            csv.push(headers.map(h => `"${h}"`).join(delimiter));

                            let counter = 1;
                            result.data.forEach(row => {
                                let displayDate;
                                if (currentFilterType === 'daily') {
                                    displayDate = moment(row.tanggal_kunjungan).format(
                                        'DD-MM-YYYY');
                                } else {
                                    displayDate = moment(row.tanggal_kunjungan).format(
                                        'MMMM YYYY');
                                }

                                const rowData = [
                                    `"${counter++}"`,
                                    `"${displayDate.replace(/"/g, '""')}"`,
                                    `"${row.jumlah_kunjungan_harian}"`
                                ];
                                csv.push(rowData.join(delimiter));
                            });

                            const BOM = "\uFEFF";
                            const csvString = csv.join('\n');
                            const blob = new Blob([BOM + csvString], {
                                type: 'text/csv;charset=utf-8;'
                            });

                            const link = document.createElement("a");
                            link.href = URL.createObjectURL(blob);
                            link.download = `${fileName}.csv`;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } else {
                            alert(result.error || "Terjadi kesalahan saat mengambil data export.");
                        }
                    } catch (error) {
                        console.error('Error fetching export data:', error);
                        alert("Terjadi kesalahan teknis saat mencoba mengekspor data.");
                    }
                });
            }

            if (hasFilter) {
                // --- Skrip untuk Chart.js ---
                const ctx = document.getElementById('kunjunganChart').getContext('2d');
                const labels = chartData.map(item => item.tanggal_kunjungan);
                const dataValues = chartData.map(item => item.jumlah_kunjungan_harian);
                let chartUnit = (filterType === 'daily') ? 'day' : 'month';
                let tooltipFormat = (filterType === 'daily') ? 'DD MMMM YYYY' : 'MMMM YYYY';
                let displayFormat = (filterType === 'daily') ? 'DD MMM' : 'MMM YYYY';

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Jumlah Kunjungan',
                            data: dataValues,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: chartUnit,
                                    tooltipFormat: tooltipFormat,
                                    displayFormats: {
                                        day: 'DD MMM',
                                        month: 'MMM YYYY'
                                    }
                                },
                                title: {
                                    display: true,
                                    text: (filterType === 'daily') ? 'Tanggal' : 'Bulan'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Jumlah Kunjungan'
                                },
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        const date = moment(context[0].label);
                                        if (filterType === 'daily') {
                                            return date.format('dddd, DD MMMM YYYY');
                                        } else {
                                            return date.format('MMMM YYYY');
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }


            // --- Skrip untuk Pop-up Modal Detail Pengunjung ---
            const detailModalEl = document.getElementById('detailPengunjungModal');
            const detailModal = new bootstrap.Modal(detailModalEl);
            const tbodyDetailPengunjung = document.getElementById('tbodyDetailPengunjung');
            const modalTanggalSpan = document.getElementById('modalTanggal');
            const modalTotalPengunjungDetailSpan = document.getElementById('modalTotalPengunjungDetail');
            const paginationVisitorsUl = document.getElementById('paginationVisitors');

            let currentDetailTanggal = '';
            const loadingMessage = `<tr><td colspan="4" class="text-center">Memuat data...</td></tr>`;

            detailModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const tanggalKunjungan = button.getAttribute('data-tanggal');
                const filterType = button.getAttribute('data-filter-type');
                currentFilterType = button.getAttribute('data-filter-type');
                loadDetailPengunjung(tanggalKunjungan, currentFilterType);
            });

            async function loadDetailPengunjung(tanggal, filterType, page = 1) {
                currentDetailTanggal = tanggal;
                tbodyDetailPengunjung.innerHTML = loadingMessage;
                paginationVisitorsUl.innerHTML = '';

                const params = new URLSearchParams();
                if (filterType === 'yearly') {
                    // Untuk filter tahunan, kirim parameter 'bulan'
                    params.append('bulan', tanggal.substring(0, 7)); // Ambil format YYYY-MM
                } else {
                    // Untuk filter harian, kirim parameter 'tanggal'
                    params.append('tanggal', tanggal);
                }
                params.append('page', page);

                try {
                    const response = await fetch(
                        `{{ route('kunjungan.get_detail_pengunjung_harian') }}?${params.toString()}`
                    );
                    const result = await response.json();

                    if (response.ok) {
                        // Tempatkan perbaikan di sini
                        modalTanggalSpan.textContent = result.modal_display_date;

                        // Pastikan data yang dikirim dari controller sudah benar-benar angka
                        // Controller harus mengirim `result.total` sebagai angka (tanpa number_format)
                        modalTotalPengunjungDetailSpan.textContent = result.total;

                        tbodyDetailPengunjung.innerHTML = '';
                        if (result.data && result.data.length > 0) {
                            result.data.forEach((pengunjung, index) => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                        <td>${(result.from || 0) + index}</td>
                        <td>${pengunjung.nama}</td>
                        <td>${pengunjung.cardnumber}</td>
                        <td><span class="badge bg-secondary rounded-pill">${pengunjung.visit_count}x</span></td>
                    `;
                                tbodyDetailPengunjung.appendChild(tr);
                            });
                            renderPagination(result);
                        } else {
                            const tr = document.createElement('tr');
                            tr.innerHTML =
                                `<td colspan="4" class="text-center">Tidak ada detail nama pengunjung ditemukan.</td>`;
                            tbodyDetailPengunjung.appendChild(tr);
                        }
                    } else {
                        tbodyDetailPengunjung.innerHTML =
                            `<tr><td colspan="4" class="text-danger text-center">Error: ${result.error || 'Gagal memuat detail pengunjung.'}</td></tr>`;
                        console.error('Error fetching detail:', result.error);
                    }
                } catch (error) {
                    tbodyDetailPengunjung.innerHTML =
                        `<tr><td colspan="4" class="text-danger text-center">Terjadi kesalahan jaringan atau server.</td></tr>`;
                    console.error('Network or server error:', error);
                }
            }

            function renderPagination(paginationData) {
                const {
                    current_page,
                    last_page
                } = paginationData;
                paginationVisitorsUl.innerHTML = '';

                paginationVisitorsUl.innerHTML += `
        <li class="page-item ${current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${current_page - 1}">Previous</a>
        </li>
    `;

                let startPage = Math.max(1, current_page - 2);
                let endPage = Math.min(last_page, current_page + 2);

                if (startPage > 1) {
                    paginationVisitorsUl.innerHTML +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                    if (startPage > 2) {
                        paginationVisitorsUl.innerHTML +=
                            `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    paginationVisitorsUl.innerHTML += `
            <li class="page-item ${i === current_page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
                }

                if (endPage < last_page) {
                    if (endPage < last_page - 1) {
                        paginationVisitorsUl.innerHTML +=
                            `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    paginationVisitorsUl.innerHTML +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${last_page}">${last_page}</a></li>`;
                }

                paginationVisitorsUl.innerHTML += `
        <li class="page-item ${current_page === last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${current_page + 1}">Next</a>
        </li>
    `;

                paginationVisitorsUl.querySelectorAll('.page-link').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = parseInt(this.getAttribute('data-page'));
                        if (page && page !== current_page && page >= 1 && page <= last_page) {
                            loadDetailPengunjung(currentDetailTanggal, currentFilterType, page);
                        }
                    });
                });
            }

            // --- Fungsionalitas Export Detail Pengunjung (tambahan) ---
            exportDetailPengunjungCsvBtn.addEventListener('click', async function() {
                if (!currentDetailTanggal) {
                    alert("Tidak ada data detail untuk diekspor.");
                    return;
                }

                try {
                    const response = await fetch(
                        `{{ route('kunjungan.get_detail_pengunjung_harian_export') }}?tanggal=${currentDetailTanggal}&filter_type=${currentFilterType}`
                    );
                    const result = await response.json();

                    if (response.ok) {
                        if (result.data.length === 0) {
                            alert("Tidak ada data detail pengunjung untuk diekspor pada tanggal ini.");
                            return;
                        }

                        let csv = [];
                        const delimiter = ';';
                        const headers = ['No', 'Nama', 'Cardnumber', 'Jumlah Kunjungan'];
                        csv.push(headers.map(h => `"${h}"`).join(delimiter));

                        let counter = 1;
                        result.data.forEach(pengunjung => {
                            const rowData = [
                                `"${counter++}"`,
                                `"${pengunjung.nama.replace(/"/g, '""')}"`,
                                `"${pengunjung.cardnumber.replace(/"/g, '""')}"`,
                                `"${pengunjung.visit_count}"`
                            ];
                            csv.push(rowData.join(delimiter));
                        });

                        const BOM = "\uFEFF";
                        const csvString = csv.join('\n');
                        const blob = new Blob([BOM + csvString], {
                            type: 'text/csv;charset=utf-8;'
                        });

                        const link = document.createElement("a");
                        link.href = URL.createObjectURL(blob);
                        link.download = `detail_kunjungan_${currentDetailTanggal}.csv`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert(result.error || "Terjadi kesalahan saat mengambil data export detail.");
                    }
                } catch (error) {
                    console.error('Error fetching export data:', error);
                    alert("Terjadi kesalahan teknis saat mencoba mengekspor data detail.");
                }
            });
        });
    </script>
@endpush
