@extends('layouts.app')

@section('content')
@section('title', 'Laporan Kunjungan Gabungan')
<div class="container">
    {{-- Header --}}
    <div class="card bg-white shadow-sm mb-4 border-0">
        <div class="card-body d-flex align-items-center">
            <i class="fas fa-users fa-3x text-primary me-4"></i>
            <div>
                <h4 class="mb-0">Laporan Kunjungan Gabungan</h4>
                <small class="text-muted">Aktivitas kunjungan anggota di semua titik layanan perpustakaan.</small>
            </div>
        </div>
    </div>

    {{-- Filter Collapsible --}}
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
                            <option value="monthly" {{ $filterType == 'monthly' ? 'selected' : '' }}>Bulan</option>
                            <option value="daily" {{ $filterType == 'daily' ? 'selected' : '' }}>Hari</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="monthlyFilter"
                        style="{{ $filterType == 'monthly' ? '' : 'display: none;' }}">
                        <label class="form-label">Rentang Bulan:</label>
                        <div class="input-group">
                            <input type="month" name="start_month" class="form-control" value="{{ $startMonth }}">
                            <span class="input-group-text">s/d</span>
                            <input type="month" name="end_month" class="form-control" value="{{ $endMonth }}">
                        </div>
                    </div>
                    <div class="col-md-4" id="dailyFilter" style="{{ $filterType == 'daily' ? '' : 'display: none;' }}">
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
                            @foreach ($lokasiOptions as $lokasi)
                                <option value="{{ $lokasi }}" {{ $selectedLokasi == $lokasi ? 'selected' : '' }}>
                                    {{ $lokasiMapping[$lokasi] ?? $lokasi }}
                                </option>
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

    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px;">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Total Kunjungan Tercatat</h6>
                        {{-- Diberi nilai default '0' jika variabel belum ada --}}
                        <h2 class="fw-bold mb-0">{{ number_format($semuaKunjungan->total() ?? 0) }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px;">
                            <i class="fas fa-map-marker-alt fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Lokasi</h6>
                        @php
                            // Logika ini hanya berjalan jika ada data kunjungan
                            if (isset($semuaKunjungan) && !$semuaKunjungan->isEmpty()) {
                                $lokasiPopuler = collect($semuaKunjungan->items())
                                    ->countBy('lokasi_kunjungan')
                                    ->sortDesc()
                                    ->keys()
                                    ->first();
                            } else {
                                $lokasiPopuler = 'N/A';
                            }
                        @endphp
                        {{-- Diberi nilai default jika tidak ada --}}
                        <h2 class="fw-bold mb-0">{{ $lokasiMapping[$lokasiPopuler] ?? $lokasiPopuler }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if (request()->has('filter_type'))
        @if (!$semuaKunjungan->isEmpty())
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Grafik Total Kunjungan</h6>
                </div>
                <div class="card-body"><canvas id="kunjunganChart"></canvas></div>
            </div>

            <div class="card shadow-sm border-0" id="results-container">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-table me-2"></i>Hasil Kunjungan (Total: <span
                            id="total-count">{{ number_format($semuaKunjungan->total()) }}</span>)</h6>
                    <button id="exportCsvBtn" class="btn btn-success btn-sm"><i
                            class="fas fa-file-csv me-2"></i>Export
                        CSV</button>
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
                                @include('pages.kunjungan._kunjungan_gabungan_table_body')
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {!! $semuaKunjungan->links() !!}
                </div>
            </div>
        @else
            <div class="alert alert-warning text-center">Tidak ada data kunjungan yang cocok dengan filter yang
                dipilih.
            </div>
        @endif
    @else
        <div class="alert alert-info text-center">Silakan pilih filter dan tekan "Tampilkan" untuk melihat data.</div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Logika untuk Filter Toggle
        const filterTypeSelect = document.getElementById('filter_type');
        const dailyFilterDiv = document.getElementById('dailyFilter');
        const monthlyFilterDiv = document.getElementById('monthlyFilter');

        function handleFilterChange() {
            const dailyInputs = dailyFilterDiv.querySelectorAll('input');
            const monthlyInputs = monthlyFilterDiv.querySelectorAll('input');
            if (filterTypeSelect.value === 'daily') {
                dailyFilterDiv.style.display = 'block';
                monthlyFilterDiv.style.display = 'none';
                monthlyInputs.forEach(input => input.disabled = true);
                dailyInputs.forEach(input => input.disabled = false);
            } else {
                dailyFilterDiv.style.display = 'none';
                monthlyFilterDiv.style.display = 'block';
                dailyInputs.forEach(input => input.disabled = true);
                monthlyInputs.forEach(input => input.disabled = false);
            }
        }
        if (filterTypeSelect) {
            filterTypeSelect.addEventListener('change', handleFilterChange);
            handleFilterChange();
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
        if (Object.keys(chartData).length > 0) {
            const ctx = document.getElementById('kunjunganChart').getContext('2d');
            const labels = Object.keys(chartData).map(periode => {
                const format = (filterTypeSelect.value === 'daily') ? 'D MMM YYYY' : 'MMM YYYY';
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
