@extends('layouts.app')

@section('content')
@section('title', 'Statistik Keterpakaian Koleksi')
<div class="container">
    {{-- Header --}}
    <div class="card bg-white shadow-sm mb-4">
        <div class="card-body">
            <h4 class="mb-0">Statistik Keterpakaian Koleksi</h4>
            <small class="text-muted">Jumlah penggunaan koleksi berdasarkan kategori (Referensi, Sirkulasi, dll)</small>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <a class="h6 mb-0 text-decoration-none" data-bs-toggle="collapse" href="#collapseFilter" role="button"
                aria-expanded="true" aria-controls="collapseFilter">
                <i class="fas fa-filter me-2"></i> Filter Data
            </a>
        </div>
        <div class="collapse show" id="collapseFilter">
            <div class="card-body">
                <form method="GET" action="{{ route('peminjaman.keterpakaian_koleksi') }}"
                    class="row g-3 align-items-end">
                    <div class="col-md-auto">
                        <label for="filter_type" class="form-label">Tampilkan per:</label>
                        <select name="filter_type" id="filter_type" class="form-select">
                            <option value="monthly" {{ $filterType == 'monthly' ? 'selected' : '' }}>Bulan</option>
                            <option value="daily" {{ $filterType == 'daily' ? 'selected' : '' }}>Hari</option>
                        </select>
                    </div>
                    <div class="col-md-5" id="monthlyFilter"
                        style="{{ $filterType == 'monthly' ? '' : 'display: none;' }}">
                        {{-- <label class="form-label">Rentang Bulan:</label> --}}
                        <div class="input-group">
                            <input type="month" name="start_month" class="form-control" value="{{ $startMonth }}">
                            <span class="input-group-text">s/d</span>
                            <input type="month" name="end_month" class="form-control" value="{{ $endMonth }}">
                        </div>
                    </div>
                    <div class="col-md-5" id="dailyFilter" style="{{ $filterType == 'daily' ? '' : 'display: none;' }}">
                        {{-- <label class="form-label">Rentang Tanggal:</label> --}}
                        <div class="input-group">
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                            <span class="input-group-text">s/d</span>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                        </div>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary">Terapkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (request()->has('filter_type'))
        @if (!empty($dataTabel) && !$dataTabel->isEmpty())
            {{-- Kartu Ringkasan (Summary Cards) --}}
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-book-open fa-3x text-primary mb-3"></i>
                            <h6 class="card-subtitle mb-2 text-muted">Total Penggunaan Koleksi</h6>
                            <h2 class="card-title fw-bold">{{ number_format($totalPenggunaan) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-star fa-3x text-warning mb-3"></i>
                            <h6 class="card-subtitle mb-2 text-muted">Kategori Terpopuler</h6>
                            <h2 class="card-title fw-bold">{{ $kategoriPopuler['nama'] }}</h2>
                            <span class="badge bg-light text-dark fs-6">{{ number_format($kategoriPopuler['jumlah']) }}
                                kali</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Chart --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Grafik Keterpakaian Koleksi</h6>
                </div>
                <div class="card-body"><canvas id="koleksiChart"></canvas></div>
            </div>

            {{-- Tabel Hasil --}}
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Hasil Analisis</h6>
                    <button id="exportCsvBtn" class="btn btn-success btn-sm"><i class="fas fa-file-csv me-2"></i>Export
                        CSV</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="main-table">
                            <thead class="table-light">
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
                                                style="background-color: rgba(54, 162, 235, {{ $opacity }}); color: {{ $opacity > 0.6 ? '#fff' : '#000' }};">
                                                @if ($jumlah > 0)
                                                    <a href="#" class="detail-link" data-bs-toggle="modal"
                                                        data-bs-target="#detailBukuModal"
                                                        data-periode="{{ $row['periode'] }}"
                                                        data-kategori="{{ $kategori }}"
                                                        style="color: inherit; text-decoration: none;">{{ number_format($jumlah) }}</a>
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
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailBukuModalLabel">Detail Buku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Menampilkan detail untuk kategori <strong id="modal-kategori" class="badge bg-primary"></strong>
                    pada periode <strong id="modal-periode" class="badge bg-secondary"></strong>.</p>
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Judul Buku</th>
                            <th style="width: 25%;">Waktu Transaksi</th>
                            <th style="width: 20%;">Tipe</th>
                        </tr>
                    </thead>
                    <tbody id="detailBukuTbody"></tbody>
                </table>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div id="detailBukuPagination"></div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        // Fungsi untuk mengatur visibilitas dan status input filter
        function handleFilterChange() {
            const dailyInputs = dailyFilterDiv.querySelectorAll('input');
            const monthlyInputs = monthlyFilterDiv.querySelectorAll('input');

            if (filterTypeSelect.value === 'daily') {
                dailyFilterDiv.style.display = 'flex';
                monthlyFilterDiv.style.display = 'none';
                // Aktifkan input harian, nonaktifkan input bulanan
                monthlyInputs.forEach(input => input.disabled = true);
                dailyInputs.forEach(input => input.disabled = false);
            } else { // 'monthly'
                dailyFilterDiv.style.display = 'none';
                monthlyFilterDiv.style.display = 'flex';
                // Aktifkan input bulanan, nonaktifkan input harian
                dailyInputs.forEach(input => input.disabled = true);
                monthlyInputs.forEach(input => input.disabled = false);
            }
        }

        // Tambahkan event listener ke dropdown
        if (filterTypeSelect) {
            filterTypeSelect.addEventListener('change', handleFilterChange);

            // Panggil fungsi sekali saat halaman dimuat untuk mengatur kondisi awal
            handleFilterChange();
        }

        // --- Logika untuk Modal Detail Buku ---
        const detailBukuModal = new bootstrap.Modal(document.getElementById('detailBukuModal'));
        const detailBukuTbody = document.getElementById('detailBukuTbody');
        const detailBukuPaginationContainer = document.getElementById('detailBukuPagination');
        const mainTable = document.getElementById('main-table');

        if (mainTable) {
            mainTable.addEventListener('click', function(event) {
                const target = event.target.closest('.detail-link'); // Find the closest link
                if (target) {
                    event.preventDefault();

                    const periode = target.dataset.periode;
                    const kategori = target.dataset.kategori;
                    const filterType = document.getElementById('filter_type').value;

                    // Update modal title
                    document.getElementById('modal-kategori').innerText = kategori;
                    document.getElementById('modal-periode').innerText = (filterType === 'daily') ?
                        moment(periode).format('D MMM YYYY') : moment(periode).format('MMM YYYY');

                    // Fetch data for the first page
                    const url =
                        `{{ route('statistik.keterpakaian_koleksi.detail') }}?periode=${periode}&kategori=${kategori}&filter_type=${filterType}`;
                    fetchDetailBuku(url);
                }
            });
        }

        // Event listener for pagination clicks inside the modal
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

        // Function to fetch and render book details
        async function fetchDetailBuku(url) {
            detailBukuTbody.innerHTML = '<tr><td colspan="3" class="text-center">Memuat data...</td></tr>';
            detailBukuPaginationContainer.innerHTML = '';

            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const result = await response.json();
                renderDetailBukuContent(result);
            } catch (error) {
                console.error('Error fetching book details:', error);
                detailBukuTbody.innerHTML =
                    '<tr><td colspan="3" class="text-center text-danger">Gagal memuat data.</td></tr>';
            }
        }

        // Function to render the modal content
        function renderDetailBukuContent(result) {
            if (result.data && result.data.length > 0) {
                let allRowsHtml = '';
                result.data.forEach(item => {
                    allRowsHtml += `
                <tr>
                    <td>${item.judul_buku}</td>
                    <td>${moment(item.waktu_transaksi).format('DD MMM YYYY, HH:mm')}</td>
                    <td><span class="badge bg-info">${item.tipe_transaksi}</span></td>
                </tr>
            `;
                });
                detailBukuTbody.innerHTML = allRowsHtml;

                // Render pagination links
                let paginationHtml = '<ul class="pagination pagination-sm mb-0">';
                if (result.links) {
                    result.links.forEach(link => {
                        if (link.url) {
                            paginationHtml += `
                        <li class="page-item ${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}">
                            <a class="page-link" href="${link.url}">${link.label.replace(/&laquo;|&raquo;/g, '').trim()}</a>
                        </li>`;
                        }
                    });
                }
                paginationHtml += '</ul>';
                detailBukuPaginationContainer.innerHTML = paginationHtml;
            } else {
                detailBukuTbody.innerHTML =
                    '<tr><td colspan="3" class="text-center">Tidak ada data detail.</td></tr>';
            }
        }

        const dataTabel = @json($dataTabel ?? []);
        const listKategori = @json($listKategori ?? []);
        const filterType = "{{ $filterType }}";

        if (dataTabel.length > 0 && listKategori.length > 0) {
            const ctx = document.getElementById('koleksiChart').getContext('2d');
            const labels = dataTabel.map(row => {
                const format = (filterType === 'daily') ? 'D MMM YYYY' : 'MMM YYYY';
                return moment(row.periode).format(format);
            });

            const datasets = listKategori.map(kategori => {
                const data = dataTabel.map(row => row[kategori] || 0);
                const r = Math.floor(Math.random() * 200);
                const g = Math.floor(Math.random() * 200);
                const b = Math.floor(Math.random() * 200);
                const color = `rgba(${r}, ${g}, ${b}, 0.8)`;

                return {
                    label: kategori,
                    data: data,
                    borderColor: color,
                    backgroundColor: color.replace('0.8', '0.2'),
                    tension: 0.3,
                    fill: true,
                };
            });

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                // ===============================================
                // 4. POLES TAMPILAN CHART
                // ===============================================
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Grafik Tren Penggunaan Koleksi',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Periode'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah Penggunaan'
                            },
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
