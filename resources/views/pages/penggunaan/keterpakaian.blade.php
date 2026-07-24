@extends('layouts.app')

@section('content')
@section('title', 'Statistik Keterpakaian Koleksi')

<div class="container-fluid px-3 px-md-4 pt-2 pb-4">

    <x-breadcrumb title="Statistik Keterpakaian Koleksi" icon="fas fa-chart-line">
            <x-slot name="subtitle">
                Jumlah penggunaan koleksi berdasarkan kategori (Referensi, Sirkulasi,
                            dll)
            </x-slot>
        </x-breadcrumb>

    {{-- 2. FILTER --}}
    <div class="card unified-card border-0 shadow-sm filter-card mb-4">
        <div class="card-header border-bottom-0 pt-4 pb-0 px-4">
            <a class="h5 mb-0 text-decoration-none fw-bold" data-bs-toggle="collapse"
                href="#collapseFilter" role="button" aria-expanded="true" aria-controls="collapseFilter">
                <i class="fas fa-filter text-primary me-2"></i>Filter Data
            </a>
        </div>
        <div class="collapse show" id="collapseFilter">
            <div class="card-body px-4 pb-4 pt-3">
                <form method="GET" action="{{ route('penggunaan.keterpakaian_koleksi') }}" class="row g-3 align-items-end">
                    <div class="col-md-auto">
                        <label for="filter_type" class="form-label small text-muted text-uppercase">Tampilkan per:</label>
                        <select name="filter_type" id="filter_type" class="form-select">
                            <option value="monthly" {{ $filterType == 'monthly' ? 'selected' : '' }}>Bulan</option>
                            <option value="daily" {{ $filterType == 'daily' ? 'selected' : '' }}>Hari</option>
                        </select>
                    </div>
                    <div class="col-md-5" id="monthlyFilter"
                        style="{{ $filterType == 'monthly' ? '' : 'display: none;' }}">
                        <div class="input-group">
                            <input type="month" name="start_month" class="form-control" value="{{ $startMonth }}">
                            <span class="input-group-text">s/d</span>
                            <input type="month" name="end_month" class="form-control" value="{{ $endMonth }}">
                        </div>
                    </div>
                    <div class="col-md-5" id="dailyFilter"
                        style="{{ $filterType == 'daily' ? '' : 'display: none;' }}">
                        <div class="input-group">
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                            <span class="input-group-text">s/d</span>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                        </div>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary"><i
                                class="fas fa-search me-1"></i>Terapkan</button>
                    </div>
                </form>
                
                <form id="pdfExportForm" method="POST" action="{{ route('penggunaan.keterpakaian_koleksi.export_pdf') }}" style="display:none;">
                    @csrf
                    <input type="hidden" name="filter_type" value="{{ $filterType ?? 'monthly' }}">
                    <input type="hidden" name="usage_type" value="{{ $usageType ?? 'all' }}">
                    <input type="hidden" name="start_month" value="{{ $startMonth ?? '' }}">
                    <input type="hidden" name="end_month" value="{{ $endMonth ?? '' }}">
                    <input type="hidden" name="start_date" value="{{ $startDate ?? '' }}">
                    <input type="hidden" name="end_date" value="{{ $endDate ?? '' }}">
                    <input type="hidden" name="chart_image_base64" id="chart_image_base64">
                </form>
            </div>
        </div>
    </div>

    @if (request()->has('filter_type'))
        @if (!empty($dataTabel) && !$dataTabel->isEmpty())
            {{-- 3. STAT CARDS --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-box bg-primary text-white me-3">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-bold text-uppercase">Total Penggunaan Koleksi</div>
                                <div class="fs-4 fw-bold">{{ number_format($totalPenggunaan) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-box bg-success text-white me-3">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-bold text-uppercase">
                                    Rerata Penggunaan / {{ $filterType == 'daily' ? 'Hari' : 'Bulan' }}
                                </div>
                                <div class="fs-4 fw-bold">{{ number_format($rerataPenggunaan, 1) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-box bg-warning text-white me-3">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-bold text-uppercase">Kategori Terpopuler</div>
                                <div class="fs-4 fw-bold">{{ $kategoriPopuler['nama'] }}</div>
                                <span
                                    class="badge text-bg-primary">{{ number_format($kategoriPopuler['jumlah']) }}
                                    kali</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. CHART --}}
            <div class="card unified-card border-0 shadow-sm mb-4">
                <div class="card-header py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-chart-area me-1 text-primary"></i> Grafik Keterpakaian
                        Koleksi</h6>
                </div>
                <div class="card-body">
                    <div id="koleksiChart" style="min-height: 400px; width: 100%;"></div>
                </div>
            </div>

            {{-- 5. TABEL --}}
            <div class="card unified-card border-0 shadow-sm">
                <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-table me-1 text-primary"></i> Hasil Analisis</h6>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <select id="usage_type" class="form-select " style="width: auto; min-width: 180px;">
                            <option value="all" {{ ($usageType ?? 'all') == 'all' ? 'selected' : '' }}>Semua Jenis</option>
                            <option value="issue" {{ ($usageType ?? 'all') == 'issue' ? 'selected' : '' }}>Pinjam</option>
                            <option value="renew" {{ ($usageType ?? 'all') == 'renew' ? 'selected' : '' }}>Perpanjangan</option>
                            <option value="return" {{ ($usageType ?? 'all') == 'return' ? 'selected' : '' }}>Kembali</option>
                            <option value="localuse" {{ ($usageType ?? 'all') == 'localuse' ? 'selected' : '' }}>Baca di Tempat</option>
                        </select>
                        <button type="button" id="exportPdfBtn" class="btn btn-danger "><i class="fas fa-file-pdf me-1"></i>Cetak PDF</button>
                        <button id="exportCsvBtn" class="btn btn-success "><i class="fas fa-file-csv me-1"></i>Export CSV</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 unified-table" id="main-table">
                            <thead>
                                <tr>
                                    <th>Periode</th>
                                    @foreach ($listKategori as $kategori)
                                        <th class="text-center" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="{{ $ccodeDescriptions[$kategori] ?? 'Keterangan tidak tersedia' }}">
                                            {{ $kategori }}</th>
                                    @endforeach
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataTabel as $row)
                                    <tr>
                                        <td>{{ $filterType == 'daily' ? \Carbon\Carbon::parse($row['periode'])->format('d M Y') : \Carbon\Carbon::parse($row['periode'])->format('M Y') }}
                                        </td>
                                        @php $totalPerRow = 0; @endphp
                                        @foreach ($listKategori as $kategori)
                                            @php
                                                $jumlah = $row[$kategori] ?? 0;
                                                $totalPerRow += $jumlah;
                                                $opacity = $maxJumlah > 0 ? ($jumlah / $maxJumlah) * 0.8 + 0.1 : 0;
                                            @endphp
                                            <td class="text-center"
                                                style="background-color: rgba(54, 162, 235, {{ $opacity }}); {{ $opacity > 0.6 ? 'color: #fff;' : '' }}">
                                                @if ($jumlah > 0)
                                                    <a href="#" class="detail-link" data-bs-toggle="modal"
                                                        data-bs-target="#detailBukuModal"
                                                        data-periode="{{ $row['periode'] }}"
                                                        data-kategori="{{ $kategori }}"
                                                        style="text-decoration: none;">{{ number_format($jumlah) }}</a>
                                                @else
                                                    0
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center fw-bold">{{ number_format($totalPerRow) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning text-center">Tidak ada data untuk ditampilkan pada rentang yang dipilih.
            </div>
        @endif
    @else
        <div class="alert alert-info text-center">Silakan pilih rentang waktu dan tekan "Terapkan" untuk menampilkan
            data.</div>
    @endif
</div>

{{-- Modal untuk Detail Buku --}}
<div class="modal fade" id="detailBukuModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 py-3">
                <div>
                    <h5 class="modal-title fw-bold text-body" id="detailBukuModalLabel">
                        <i class="fas fa-list-ul me-2"></i> Detail Penggunaan Koleksi
                    </h5>
                    <span class="text-muted small">Kategori <strong id="modal-kategori" class="text-primary"></strong> pada periode <strong id="modal-periode" class="text-primary"></strong></span>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 unified-table" id="detailTable">
                        <thead class="sticky-top" style="background-color: rgba(0, 0, 0, 0.02);">
                            <tr>
                                <th class="py-3 px-4 border-bottom-0" style="width: 45%;">Judul Buku</th>
                                <th class="py-3 px-4 border-bottom-0 text-center">Barcode</th>
                                <th class="py-3 px-4 border-bottom-0 text-center" style="width: 25%;">Waktu Transaksi</th>
                                <th class="py-3 px-4 border-bottom-0 text-center">Tipe Transaksi</th>
                            </tr>
                        </thead>
                        <tbody id="detailBukuTbody">
                            {{-- Row Loading State atau Kosong --}}
                        </tbody>
                    </table>
                </div>
                <div id="detailBukuPagination" class="d-flex justify-content-center py-3 border-light"></div>
            </div>
            <div class="modal-footer border-0 py-3 d-flex justify-content-end">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const exportCsvButton = document.getElementById('exportCsvBtn');
        if (exportCsvButton) {
            exportCsvButton.addEventListener('click', function() {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('export', 'csv');
                window.location.href = currentUrl.toString();
            });
        }

        const filterTypeSelect = document.getElementById('filter_type');
        const dailyFilterDiv = document.getElementById('dailyFilter');
        const monthlyFilterDiv = document.getElementById('monthlyFilter');

        function handleFilterChange() {
            const dailyInputs = dailyFilterDiv.querySelectorAll('input');
            const monthlyInputs = monthlyFilterDiv.querySelectorAll('input');

            if (filterTypeSelect.value === 'daily') {
                dailyFilterDiv.style.display = 'flex';
                monthlyFilterDiv.style.display = 'none';
                monthlyInputs.forEach(input => input.disabled = true);
                dailyInputs.forEach(input => input.disabled = false);
            } else {
                dailyFilterDiv.style.display = 'none';
                monthlyFilterDiv.style.display = 'flex';
                dailyInputs.forEach(input => input.disabled = true);
                monthlyInputs.forEach(input => input.disabled = false);
            }
        }

        if (filterTypeSelect) {
            filterTypeSelect.addEventListener('change', handleFilterChange);
            handleFilterChange();
        }

        // Auto-filter ketika jenis penggunaan berubah
        const usageTypeSelect = document.getElementById('usage_type');
        if (usageTypeSelect) {
            usageTypeSelect.addEventListener('change', function() {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('usage_type', this.value);
                window.location.href = currentUrl.toString();
            });
        }

        const detailBukuModal = new bootstrap.Modal(document.getElementById('detailBukuModal'));
        const detailBukuTbody = document.getElementById('detailBukuTbody');
        const detailBukuPaginationContainer = document.getElementById('detailBukuPagination');
        const mainTable = document.getElementById('main-table');

        if (mainTable) {
            mainTable.addEventListener('click', function(event) {
                const target = event.target.closest('.detail-link');
                if (target) {
                    event.preventDefault();

                    const periode = target.dataset.periode;
                    const kategori = target.dataset.kategori;
                    const filterType = document.getElementById('filter_type').value;
                    const usageType = document.getElementById('usage_type').value;

                    document.getElementById('modal-kategori').innerText = kategori;
                    document.getElementById('modal-periode').innerText = (filterType === 'daily') ?
                        moment(periode).format('D MMM YYYY') : moment(periode).format('MMM YYYY');

                    const url =
                        `{{ route('statistik.keterpakaian_koleksi.detail') }}?periode=${periode}&kategori=${kategori}&filter_type=${filterType}&usage_type=${usageType}`;
                    fetchDetailBuku(url);
                }
            });
        }

        detailBukuPaginationContainer.addEventListener('click', function(event) {
            const target = event.target.closest('.page-link');
            if (target) {
                event.preventDefault();
                const url = target.href;
                if (url) {
                    fetchDetailBuku(url);
                }
            }
        });

        async function fetchDetailBuku(url) {
            detailBukuTbody.innerHTML = '<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="text-muted mt-2 small fw-bold">Sedang mengambil data...</p></td></tr>';
            detailBukuPaginationContainer.innerHTML = '';

            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const result = await response.json();
                renderDetailBukuContent(result);
            } catch (error) {
                console.error('Error fetching book details:', error);
                detailBukuTbody.innerHTML =
                    '<tr><td colspan="4" class="text-center text-danger py-4 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Gagal memuat data.</td></tr>';
            }
        }

        function renderDetailBukuContent(result) {
            if (result.data && result.data.length > 0) {
                let allRowsHtml = '';
                result.data.forEach(item => {
                    let badgeTipe = item.tipe_transaksi === 'issue' ?
                                '<span class="badge bg-primary-soft text-primary px-3 py-2"><i class="fas fa-arrow-up me-1"></i>Pinjam</span>' :
                                (item.tipe_transaksi === 'renew' ?
                                    '<span class="badge bg-warning-soft text-warning px-3 py-2"><i class="fas fa-sync me-1"></i>Perpanjang</span>' :
                                    (item.tipe_transaksi === 'return' ? 
                                        '<span class="badge bg-success-soft text-success px-3 py-2"><i class="fas fa-arrow-down me-1"></i>Kembali</span>' :
                                        `<span class="badge bg-info-soft text-info px-3 py-2">${item.tipe_transaksi}</span>`
                                    )
                                );

                    allRowsHtml += `
                <tr>
                    <td class="px-4 fw-medium text-body"><i class="fas fa-book text-muted me-2"></i>${item.judul_buku}</td>
                    <td class="text-center"><span class="badge border text-body px-3 py-2">${item.barcode}</span></td>
                    <td class="text-center text-muted small"><i class="far fa-clock me-1"></i>${moment(item.waktu_transaksi).format('DD MMM YYYY, HH:mm')}</td>
                    <td class="text-center">${badgeTipe}</td>
                </tr>
            `;
                });
                detailBukuTbody.innerHTML = allRowsHtml;

                let paginationHtml = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
                if (result.links) {
                    result.links.forEach(link => {
                        if (link.url) {
                            let label = link.label.replace(/&laquo;|&raquo;/g, '').trim();
                            if (label === 'Previous') label = '<i class="fas fa-chevron-left small"></i>';
                            if (label === 'Next') label = '<i class="fas fa-chevron-right small"></i>';
                            paginationHtml += `
                        <li class="page-item ${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}">
                            <a class="page-link shadow-none" href="${link.url}">${label}</a>
                        </li>`;
                        }
                    });
                }
                paginationHtml += '</ul></nav>';
                detailBukuPaginationContainer.innerHTML = paginationHtml;
            } else {
                detailBukuTbody.innerHTML =
                    '<tr><td colspan="4" class="text-center py-5"><i class="fas fa-info-circle fa-2x text-muted mb-2 opacity-50"></i><p class="text-muted mb-0">Tidak ada data detail.</p></td></tr>';
            }
        }

        const dataTabel = @json($dataTabel ?? []);
        const listKategori = @json($listKategori ?? []);
        const filterType = "{{ $filterType }}";

        if (dataTabel.length > 0 && listKategori.length > 0) {
            const labels = dataTabel.map(row => {
                const format = (filterType === 'daily') ? 'D MMM YYYY' : 'MMM YYYY';
                return moment(row.periode).format(format);
            });

            const colorPalette = [
                '#36a2eb', '#ff6384', '#4bc0c0', '#ffce56', '#9966ff',
                '#ff9f40', '#c9cbcf', '#e75480', '#00cc99', '#66b2ff'
            ];

            const seriesData = listKategori.map((kategori, index) => {
                const data = dataTabel.map(row => row[kategori] || 0);
                return {
                    name: kategori,
                    data: data
                };
            });

            // FIX: Ensure the colors array exactly matches the number of series!
            // If series length > colors length, ApexCharts falls into an infinite loop on some versions.
            const dynamicColors = listKategori.map((kategori, index) => {
                return colorPalette[index % colorPalette.length];
            });

            var options = {
                series: seriesData,
                chart: {
                    height: 400,
                    type: 'area',
                    fontFamily: 'Inter, Helvetica, Arial, sans-serif',
                    toolbar: { show: false }, // Hide to prevent duplicate export options and freezing
                    zoom: { enabled: false }, // Prevent chart from intercepting page scroll
                    selection: { enabled: false }, // Prevent chart from intercepting drag events
                    animations: {
                        enabled: true, // Re-enable animations since the freeze was a color array bug
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                colors: dynamicColors,
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: 2 // Thinner line for better performance
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1, // slightly higher to avoid rendering bugs
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: labels,
                    title: { text: 'Periode', style: { fontWeight: 500 } },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    title: { text: 'Jumlah Penggunaan', style: { fontWeight: 500 } },
                    labels: {
                        formatter: function (val) { return Math.round(val); }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    shared: true,
                    intersect: false
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center'
                }
            };

            window.keterpakaianChartInstance = new ApexCharts(document.querySelector("#koleksiChart"), options);
            window.keterpakaianChartInstance.render();
        }

        // --- EXPORT PDF LOGIC ---
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        const pdfExportForm = document.getElementById('pdfExportForm');
        const chartImageInput = document.getElementById('chart_image_base64');
        
        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', async function() {
                if (typeof window.keterpakaianChartInstance !== 'undefined' && window.keterpakaianChartInstance) {
                    const uri = await window.keterpakaianChartInstance.dataURI();
                    chartImageInput.value = uri.imgURI;
                }
                pdfExportForm.submit();
            });
        }
    });
</script>
@endsection
