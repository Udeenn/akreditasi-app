@extends('layouts.app')

@section('content')
@section('title', 'Statistik Peminjaman')
<div class="container">
    <div class="card bg-white shadow-sm mb-4">
        <div class="card-body">
            <h4 class="mb-0">Statistik Peminjaman</h4>
            <small class="text-muted">Ringkasan data peminjaman berdasarkan periode</small>
        </div>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h6 class="mb-0">Filter Data</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('peminjaman.peminjaman_rentang_tanggal') }}"
                class="row g-3 align-items-end" id="filterForm">
                <div class="col-md-auto">
                    <label for="filter_type" class="form-label">Tampilkan Data:</label>
                    <select name="filter_type" id="filter_type" class="form-select">
                        <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>Per Hari
                        </option>
                        <option value="monthly" {{ ($filterType ?? '') == 'monthly' ? 'selected' : '' }}>Per Bulan
                        </option>
                    </select>
                </div>
                <div class="col-md-4" id="dailyFilter"
                    style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                    <label for="start_date" class="form-label">Rentang Tanggal:</label>
                    <div class="input-group">
                        <input type="date" name="start_date" id="start_date" class="form-control"
                            value="{{ $startDate ?? \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') }}">
                        <span class="input-group-text">s/d</span>
                        <input type="date" name="end_date" id="end_date" class="form-control"
                            value="{{ $endDate ?? \Carbon\Carbon::now()->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="col-md-4" id="monthlyFilter"
                    style="{{ ($filterType ?? '') == 'monthly' ? '' : 'display: none;' }}">
                    <label for="start_year" class="form-label">Rentang Tahun:</label>
                    <div class="input-group">
                        @php
                            $currentYear = date('Y');
                            $loopStartYear = $currentYear - 10; // Mundur 10 tahun
                            $loopEndYear = $currentYear;
                        @endphp
                        <select name="start_year" id="start_year" class="form-select">
                            @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                <option value="{{ $year }}"
                                    {{ ($startYear ?? $currentYear) == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endfor
                        </select>
                        <span class="input-group-text">s/d</span>
                        <select name="end_year" id="end_year" class="form-select">
                            @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                <option value="{{ $year }}"
                                    {{ ($endYear ?? $currentYear) == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if (!empty($statistics) && !$statistics->isEmpty())
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Grafik Statistik Peminjaman</h5>
            </div>
            <div class="card-body">
                <canvas id="peminjamanChart"></canvas>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                Tabel Statistik Peminjaman
                <button type="button" id="exportCsvBtn" class="btn btn-success btn-sm"><i class="fas fa-file-csv"></i>
                    Export CSV</button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="alert alert-info py-2">
                            <i class="fas fa-book me-2"></i> Total Buku Terpinjam:
                            <span class="fw-bold">{{ number_format($totalBooks) }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-info py-2">
                            <i class="fas fa-users me-2"></i> Total Peminjam :
                            <span class="fw-bold">{{ number_format($totalBorrowers) }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-warning py-2">
                            <i class="fas fa-book-reader me-2"></i> Total Entri:
                            <span class="fw-bold">{{ $statistics->total() }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-success py-2">
                            <i class="fas fa-chart-line me-2"></i>
                            Rerata {{ ($filterType ?? 'daily') == 'daily' ? 'Hari' : 'Bulan' }}:
                            <span class="fw-bold">{{ number_format($rerataPeminjaman, 1) }}</span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode</th>
                                <th>Jumlah Peminjaman Buku</th>
                                <th>Jumlah Peminjam</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($statistics as $index => $stat)
                                <tr>
                                    <td>{{ $statistics->firstItem() + $index }}</td>
                                    <td>
                                        @if (($filterType ?? 'daily') == 'daily')
                                            @if ($stat->periode)
                                                {{ \Carbon\Carbon::parse($stat->periode)->format('d F Y') }}
                                            @else
                                                -
                                            @endif
                                        @else
                                            @if ($stat->periode)
                                                {{ \Carbon\Carbon::createFromFormat('Y-m', $stat->periode)->format('F Y') }}
                                            @else
                                                -
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ $stat->jumlah_peminjaman_buku }}</td>
                                    <td>{{ $stat->jumlah_peminjam_unik }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary text-white view-detail-btn"
                                            data-bs-toggle="modal" data-bs-target="#detailPeminjamanModal"
                                            data-periode="{{ $stat->periode }}">
                                            <i class="fas fa-eye me-1"></i> Lihat Detail
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center">
                    {{ $statistics->appends(request()->except('page'))->links() }}
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info text-center mt-4">
            Silakan gunakan filter di atas untuk menampilkan data statistik peminjaman.
        </div>
    @endif

    {{-- Modal untuk Detail Peminjaman --}}
    <div class="modal fade" id="detailPeminjamanModal" tabindex="-1" aria-labelledby="detailPeminjamanModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailPeminjamanModalLabel">Detail Peminjaman <span
                            id="modal-periode-display" class="badge bg-secondary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="detailTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 20%;">Nama Peminjam</th>
                                    <th style="width: 15%;">NIM</th>
                                    <th>Detail Buku</th>
                                </tr>
                            </thead>
                            <tbody id="detailTbody">
                                <tr>
                                    <td colspan="4" class="text-center">Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <div id="modalPagination"></div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/locale/id.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const filterTypeSelect = document.getElementById('filter_type');
        const dailyFilterDiv = document.getElementById('dailyFilter');
        const monthlyFilterDiv = document.getElementById('monthlyFilter');

        filterTypeSelect.addEventListener('change', function() {
            if (this.value === 'daily') {
                dailyFilterDiv.style.display = 'block';
                monthlyFilterDiv.style.display = 'none';
            } else {
                dailyFilterDiv.style.display = 'none';
                monthlyFilterDiv.style.display = 'block';
            }
        });

        const fullStatistics = @json($fullStatisticsForChart ?? []);
        const filterType = "{{ $filterType ?? 'daily' }}";

        if (fullStatistics.length > 0) {
            const chartLabels = fullStatistics.map(item => moment(item.periode).format(filterType === 'daily' ?
                'D MMM YYYY' : 'MMM YYYY'));
            const chartDataBooks = fullStatistics.map(item => item.jumlah_peminjaman_buku);
            const chartDataBorrowers = fullStatistics.map(item => item.jumlah_peminjam_unik);

            const ctx = document.getElementById('peminjamanChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Jumlah Peminjaman Buku',
                        data: chartDataBooks,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Jumlah Peminjam',
                        data: chartDataBorrowers,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: (filterType === 'daily') ? 'Tanggal' : 'Bulan'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah'
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
                                    const item = fullStatistics[context[0].dataIndex];
                                    return moment(item.periode).format(filterType === 'daily' ?
                                        'dddd, D MMMM YYYY' : 'MMMM YYYY');
                                }
                            }
                        }
                    }
                }
            });
        }

        const detailModalElement = document.getElementById('detailPeminjamanModal');
        const detailModal = new bootstrap.Modal(detailModalElement);
        const detailTbody = document.getElementById('detailTbody');
        const modalPeriodeDisplay = document.getElementById('modal-periode-display');
        const modalPaginationContainer = document.getElementById('modalPagination');
        let currentDetailUrl = '';

        document.querySelectorAll('.view-detail-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const periode = this.dataset.periode;
                const filterType = document.getElementById('filter_type').value;

                let periodeText = (filterType === 'daily') ?
                    moment(periode).format('D MMMM YYYY') :
                    moment(periode, 'YYYY-MM').format('MMMM YYYY');
                modalPeriodeDisplay.innerText = periodeText;
                currentDetailUrl =
                    `{{ route('peminjaman.get_detail') }}?periode=${periode}&filter_type=${filterType}`;
                fetchDetailData(currentDetailUrl);
                detailModal.show();
            });
        });

        modalPaginationContainer.addEventListener('click', function(event) {
            if (event.target.tagName === 'A' && event.target.classList.contains('page-link')) {
                event.preventDefault(); // Mencegah link me-reload halaman
                const url = event.target.href;
                if (url) {
                    fetchDetailData(url);
                }
            }
        });

        async function fetchDetailData(url) {
            detailTbody.innerHTML = `<tr><td colspan="4" class="text-center">Memuat data...</td></tr>`;
            modalPaginationContainer.innerHTML = '';

            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const result = await response.json();
                renderModalContent(result);
            } catch (error) {
                console.error('Error fetching detail data:', error);
                detailTbody.innerHTML =
                    `<tr><td colspan="4" class="text-center text-danger">Terjadi kesalahan saat memuat data.</td></tr>`;
            }
        }

        function renderModalContent(result) {
            if (result.data && result.data.length > 0) {
                let allRowsHtml = '';
                result.data.forEach((peminjam, index) => {
                    let detailBukuHtml = '<div class="list-group list-group-flush">';
                    peminjam.detail_buku.forEach(buku => {
                        let badgeClass = 'bg-secondary',
                            badgeText = buku.tipe_transaksi;
                        if (buku.tipe_transaksi === 'issue') {
                            badgeClass = 'bg-primary';
                            badgeText = 'Pinjam Awal';
                        } else if (buku.tipe_transaksi === 'renew') {
                            badgeClass = 'bg-success';
                            badgeText = 'Perpanjangan';
                        } else if (buku.tipe_transaksi === 'return') {
                            badgeClass = 'bg-warning text-dark';
                            badgeText = 'Pengembalian';
                        }

                        const formattedTime = moment(buku.waktu_transaksi).format(
                            'DD MMM YYYY HH:mm');
                        detailBukuHtml += `
                            <div class="d-flex justify-content-between align-items-start border-0 px-0 py-1">
                                <div class="ms-2 me-auto">
                                    <div class=""><i class="fas fa-book me-2"></i>${buku.judul_buku}<span class="badge text-dark ms-2 bg-white">${formattedTime}</span></div>
                                </div>
                                <span class="badge ${badgeClass} rounded-pill">${badgeText}</span>
                            </div>`;
                    });
                    detailBukuHtml += '</div>';

                    allRowsHtml += `
                        <tr>
                            <td class="text-center align-middle">${result.from + index}</td>
                            <td class="align-middle">${peminjam.nama_peminjam}</td>
                            <td class="align-middle">${peminjam.nim}</td>
                            <td>${detailBukuHtml}</td>
                        </tr>`;
                });
                detailTbody.innerHTML = allRowsHtml;

                let paginationHtml = '<ul class="pagination pagination-sm mb-0">';
                if (result.links) {
                    result.links.forEach(link => {
                        if (link.url && link.label.indexOf('...') === -1) {
                            paginationHtml += `
                                <li class="page-item ${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}">
                                    <a class="page-link" href="${link.url}">${link.label.replace(/&laquo;|&raquo;/g, '').trim()}</a>
                                </li>`;
                        }
                    });
                }
                paginationHtml += '</ul>';
                modalPaginationContainer.innerHTML = paginationHtml;

            } else {
                detailTbody.innerHTML =
                    `<tr><td colspan="4" class="text-center">Tidak ada detail transaksi.</td></tr>`;
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

                if (filterType === 'daily') {
                    const startDate = document.getElementById('start_date').value;
                    const endDate = document.getElementById('end_date').value;
                    title += ` Harian: ${startDate} sampai ${endDate}`;
                } else {
                    const startYear = document.getElementById('start_year').value;
                    const endYear = document.getElementById('end_year').value;
                    title += ` Bulanan Tahun ${startYear} s.d. ${endYear}`;
                }
                csv.push(title);
                csv.push('');

                // Header tabel
                let headers = ['No', 'Periode', 'Jumlah Buku Terpinjam', 'Jumlah Peminjam'];
                csv.push(headers.join(delimiter));

                // Tambahkan data baris per baris
                dataToExport.forEach((row, index) => {
                    let periode;
                    if (filterType === 'daily') {
                        periode = moment(row.periode).format('DD MMMM YYYY');
                    } else {
                        periode = moment(row.periode, 'YYYY-MM').format('MMMM YYYY');
                    }

                    let rowData = [
                        index + 1,
                        `"${periode}"`,
                        row.jumlah_peminjaman_buku,
                        row.jumlah_peminjam_unik
                    ];
                    csv.push(rowData.join(delimiter));
                });

                const csvString = csv.join('\n');
                const BOM = "\uFEFF";
                const blob = new Blob([BOM + csvString], {
                    type: 'text/csv;charset=utf-8;'
                });

                const link = document.createElement("a");
                let fileName = 'statistik_peminjaman';

                if (filterType === 'daily') {
                    const startDate = document.getElementById('start_date').value;
                    const endDate = document.getElementById('end_date').value;
                    fileName += `_harian_${startDate}_sampai_${endDate}`;
                } else {
                    const startYear = document.getElementById('start_year').value;
                    const endYear = document.getElementById('end_year').value;
                    fileName += `_bulanan_${startYear}-${endYear}`;
                }
                // --- AKHIR PERBAIKAN ---
                fileName += `_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.csv`;

                if (navigator.msSaveBlob) {
                    navigator.msSaveBlob(blob, fileName);
                } else {
                    link.href = URL.createObjectURL(blob);
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                }
            });
        }
    });
</script>
@endsection
