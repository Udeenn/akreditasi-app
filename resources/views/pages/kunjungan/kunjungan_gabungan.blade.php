@extends('layouts.app')

@section('content')
@section('title', 'Laporan Kunjungan Perpustakaan')
<div class="container">
    <div class="card bg-white shadow-sm mb-4 border-0">
        <div class="card-body d-flex align-items-center">
            <div>
                <h4 class="mb-0">Laporan Kunjungan Perpustakaan</h4>
                <small class="text-muted">Aktivitas kunjungan anggota di semua titik layanan perpustakaan.</small>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header">
            <a class="h6 mb-0 text-decoration-none" data-bs-toggle="collapse" href="#collapseFilter" role="button"
                aria-expanded="true">
                <i class="fas fa-filter me-2"></i> Filter Data
            </a>
        </div>
        <div class="collapse show" id="collapseFilter">
            <div class="card-body">
                <form method="GET" action="{{ route('kunjungan.kunjungan_gabungan') }}"
                    class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filter_type" class="form-label">Tampilkan per:</label>
                        <select name="filter_type" id="filter_type" class="form-select">
                            <option value="yearly" {{ $filterType == 'yearly' ? 'selected' : '' }}>Tahun</option>
                            <option value="monthly" {{ $filterType == 'monthly' ? 'selected' : '' }}>Rentang Bulan
                            </option>
                            <option value="date_range" {{ $filterType == 'date_range' ? 'selected' : '' }}>Rentang Hari
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4 filter-input" id="yearlyFilter" style="display: none;">
                        <label class="form-label">Rentang Tahun:</label>
                        <div class="input-group">
                            <select name="start_year" class="form-select">
                                @for ($y = date('Y'); $y >= date('Y') - 10; $y--)
                                    <option value="{{ $y }}" {{ $startYear == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                            <span class="input-group-text">s/d</span>
                            <select name="end_year" class="form-select">
                                @for ($y = date('Y'); $y >= date('Y') - 10; $y--)
                                    <option value="{{ $y }}" {{ $endYear == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    {{-- INPUT FILTER BULANAN (RANGE) --}}
                    <div class="col-md-4 filter-input" id="monthlyFilter" style="display: none;">
                        <label class="form-label">Rentang Bulan:</label>
                        <div class="input-group">
                            <input type="month" name="start_month" class="form-control" value="{{ $startMonth }}">
                            <span class="input-group-text">s/d</span>
                            <input type="month" name="end_month" class="form-control" value="{{ $endMonth }}">
                        </div>
                    </div>

                    <div class="col-md-4 filter-input" id="date_rangeFilter" style="display: none;">
                        <label class="form-label">Rentang Tanggal:</label>
                        <div class="input-group">
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                            <span class="input-group-text">s/d</span>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="lokasi" class="form-label">Lokasi Kunjungan:</label>
                        <select name="lokasi" id="lokasi" class="form-select">
                            <option value="">Semua Lokasi</option>
                            @foreach ($lokasiMapping as $lokasi)
                                <option value="{{ $lokasi }}"
                                    {{ $selectedLokasi == $lokasi ? 'selected' : '' }}>{{ $lokasi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>
                            Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (request()->has('filter_type'))
        @if (!$dataHasil->isEmpty())
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>

                            <h6 class="text-muted mb-1">Total Kunjungan Tercatat</h6>
                            <h2 class="fw-bold mb-0">{{ number_format($totalKunjungan) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 60px; height: 60px;">
                                    <i class="fas fa-trophy fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-2">Lokasi Terpopuler</h6>
                                @if ($topLokasi->isEmpty())
                                    <span class="text-muted fst-italic">Data lokasi tidak tersedia</span>
                                @else
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($topLokasi as $lokasi => $jumlah)
                                            <li class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-medium">
                                                    @if ($loop->first)
                                                        <i class="fas fa-medal me-2" style="color: #FFD700;"></i>
                                                        {{-- Emas --}}
                                                    @elseif($loop->iteration == 2)
                                                        <i class="fas fa-medal me-2" style="color: #C0C0C0;"></i>
                                                        {{-- Perak --}}
                                                    @else
                                                        <i class="fas fa-medal me-2" style="color: #CD7F32;"></i>
                                                        {{-- Perunggu --}}
                                                    @endif
                                                    {{ $lokasiMapping[$lokasi] ?? $lokasi }}
                                                </span>
                                                <span
                                                    class="badge bg-primary rounded-pill">{{ number_format($jumlah) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header">
                    <h6 class="mb-0">Grafik Total Kunjungan</h6>
                </div>
                <div class="card-body"><canvas id="kunjunganChart"></canvas></div>
            </div>

            @if ($filterType == 'yearly')
                @include('pages.kunjungan._kunjungan_tahunan_summary', [
                    'dataHasil' => $dataHasil,
                    'maxKunjunganBulanan' => $maxKunjunganBulanan,
                ])
            @else
                <div class="card shadow-sm border-0" id="results-container">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Hasil Kunjungan (Total: <span
                                id="total-count">{{ number_format($totalKunjungan) }}</span>)</h6>
                        <button id="exportCsvBtn" class="btn btn-success btn-sm"><i
                                class="fas fa-file-csv me-2"></i>Export CSV</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">No</th>
                                        <th>Waktu Kunjungan</th>
                                        <th>Nomor Kartu & Nama</th>
                                        <th>Lokasi Kunjungan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @include('pages.kunjungan._kunjungan_gabungan_table_body', [
                                        'semuaKunjungan' => $dataHasil,
                                    ])
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if ($dataHasil->hasPages())
                        <div class="card-footer">
                            {!! $dataHasil->links() !!}
                        </div>
                    @endif
                </div>
            @endif
        @else
            <div class="alert alert-warning text-center">Tidak ada data kunjungan yang cocok dengan filter.</div>
        @endif
    @else
        <div class="alert alert-info text-center">Silakan pilih filter untuk menampilkan data.</div>
    @endif
</div>


<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const filterTypeSelect = document.getElementById('filter_type');
        const filterInputs = document.querySelectorAll('.filter-input');

        function handleFilterChange() {
            // Sembunyikan semua input dulu
            filterInputs.forEach(div => div.style.display = 'none');
            // Nonaktifkan semua input di dalamnya
            filterInputs.forEach(div => div.querySelectorAll('input, select').forEach(input => input.disabled =
                true));

            // Tampilkan yang dipilih dan aktifkan inputnya
            const selectedFilterId = filterTypeSelect.value + 'Filter';
            const activeFilterDiv = document.getElementById(selectedFilterId);
            if (activeFilterDiv) {
                activeFilterDiv.style.display = 'block';
                activeFilterDiv.querySelectorAll('input, select').forEach(input => input.disabled = false);
            }
        }
        if (filterTypeSelect) {
            filterTypeSelect.addEventListener('change', handleFilterChange);
            handleFilterChange(); // Panggil saat halaman dimuat
        }

        // 2. Logika untuk Export CSV
        const exportCsvButton = document.getElementById('exportCsvBtn');
        if (exportCsvButton) {
            exportCsvButton.addEventListener('click', function() {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('export', 'csv');
                window.location.href = currentUrl.toString();
            });
        }

        // 3. Logika untuk Chart
        const chartData = @json($chartData ?? []);
        const filterType = '{{ $filterType }}';
        if (Object.keys(chartData).length > 0) {
            const ctx = document.getElementById('kunjunganChart').getContext('2d');
            const labels = Object.keys(chartData).map(periode => {
                let format = 'MMM YYYY'; // Default untuk bulanan
                // Jika filter adalah rentang tanggal, ubah format label menjadi harian
                if (filterType === 'date_range') {
                    format = 'D MMM YYYY';
                }
                // Khusus untuk tahunan, format hanya nama bulan
                if (filterType === 'yearly') {
                    return moment(periode).format('MMMM');
                }

                return moment(periode).format(format);
            });
            const data = Object.values(chartData);
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Kunjungan',
                        data: data,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // 4. Logika untuk Paginasi AJAX
        const resultsContainer = document.getElementById('results-container');
        if (resultsContainer) {
            const tableBody = resultsContainer.querySelector('tbody');
            const paginationContainer = resultsContainer.querySelector('.card-footer');
            const totalCountSpan = document.getElementById('total-count');

            resultsContainer.addEventListener('click', function(event) {
                if (event.target.tagName === 'A' && event.target.closest('.pagination')) {
                    event.preventDefault();
                    const url = event.target.href;
                    if (url) {
                        fetchPage(url, tableBody, paginationContainer, totalCountSpan);
                    }
                }
            });
        }

        async function fetchPage(url, tableBody, paginationContainer, totalCountSpan) {
            tableBody.style.opacity = '0.5';
            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const result = await response.json();
                tableBody.innerHTML = result.table_body;
                paginationContainer.innerHTML = result.pagination;
                if (totalCountSpan) {
                    totalCountSpan.innerText = result.total;
                }
            } catch (error) {
                console.error('Gagal memuat halaman:', error);
                tableBody.innerHTML =
                    `<tr><td colspan="4" class="text-center text-danger">Gagal memuat data. Periksa koneksi atau coba lagi.</td></tr>`;
            } finally {
                tableBody.style.opacity = '1';
            }
        }
    });
</script>
@endsection
