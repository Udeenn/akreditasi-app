@extends('layouts.app')

@section('title', 'Cek Kunjungan Per Bulan')

@push('styles')
    <style>
        /* --- MODERN DASHBOARD STYLING --- */
        :root {
            --primary-soft: rgba(13, 110, 253, 0.1);
            --success-soft: rgba(25, 135, 84, 0.1);
            --danger-soft: rgba(220, 53, 69, 0.1);
            --warning-soft: rgba(255, 193, 7, 0.1);
            --info-soft: rgba(13, 202, 240, 0.1);
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 12px !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background-color: var(--bs-body-bg);
            color: var(--text-dark);
            overflow: hidden !important;
        }

        /* Header Putih di Light Mode */
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--text-dark);
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

        .bg-primary-soft {
            background-color: var(--primary-soft);
            color: #0d6efd;
        }

        .bg-success-soft {
            background-color: var(--success-soft);
            color: #198754;
        }

        .bg-danger-soft {
            background-color: var(--danger-soft);
            color: #dc3545;
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

        /* --- DARK MODE ADAPTATION --- */
        body.dark-mode .card {
            background-color: #1e1e2d;
            border: 1px solid #2b2b40;
            color: #ffffff;
            border-radius: 12px !important;
            overflow: hidden !important;
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
        body.dark-mode .form-select {
            background-color: #1b1b29;
            border-color: #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .text-body {
            color: #ffffff !important;
        }

        body.dark-mode .modal-content {
            background-color: #1e1e2d;
            border-color: #2b2b40;
            color: #fff;
        }

        body.dark-mode .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
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
                                <i class="fas fa-calendar-check me-2"></i>Cek Kunjungan Per Bulan
                            </h3>
                            <p class="mb-0 opacity-75">
                                Lihat riwayat kunjungan anggota perpustakaan secara detail per bulan.
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-id-card fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FORM PENCARIAN --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold text-primary"><i class="fas fa-search me-1"></i> Filter Pencarian</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('kunjungan.cekKehadiran') }}" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="cardnumber" class="form-label small text-muted fw-bold">Nomor Kartu
                                    Anggota</label>
                                <input type="text" name="cardnumber" id="cardnumber" class="form-control border-0 "
                                    placeholder="Masukkan No. Kartu / NIM" value="{{ request('cardnumber') }}" />
                            </div>
                            <div class="col-md-3">
                                <label for="tahun" class="form-label small text-muted fw-bold">Tahun</label>
                                <select name="tahun" id="tahun" class="form-select border-0 ">
                                    <option value="">Semua Tahun</option>
                                    @php
                                        $currentYear = date('Y');
                                        for ($year = $currentYear; $year >= 2020; $year--) {
                                            echo "<option value='{$year}' " .
                                                (request('tahun') == $year ? 'selected' : '') .
                                                ">{$year}</option>";
                                        }
                                    @endphp
                                </select>
                            </div>

                            {{-- Tombol Aksi --}}
                            <div class="col-md-5 d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                    <i class="fas fa-search me-1"></i> Lihat
                                </button>
                                <button type="button" id="downloadPdfButton"
                                    class="btn btn-danger px-3 shadow-sm {{ !request('cardnumber') ? 'disabled' : '' }}">
                                    <i class="fas fa-file-pdf me-1"></i> PDF
                                </button>
                                <button type="button" id="downloadExportDataButton"
                                    class="btn btn-success px-3 shadow-sm {{ !request('cardnumber') ? 'disabled' : '' }}">
                                    <i class="fas fa-file-csv me-1"></i> CSV
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALERT ERROR --}}
        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if ($pesan)
            <div class="alert alert-info border-0 shadow-sm rounded-3 text-center mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i> {{ $pesan }}
            </div>
        @endif

        {{-- KONTEN HASIL --}}
        @if (isset($fullBorrowerDetails) && $fullBorrowerDetails && $dataKunjungan->isNotEmpty())

            {{-- 3. GRAFIK KUNJUNGAN --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-4 px-4">
                            <h5 class="fw-bold mb-0 text-body">Grafik Kunjungan</h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div style="height: 300px; width: 100%;">
                                <canvas id="chartKunjungan"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                {{-- 4. INFORMASI ANGGOTA --}}
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header border-bottom py-3 px-4">
                            <h5 class="fw-bold m-0 text-primary"><i class="fas fa-user-circle me-2"></i>Informasi Anggota
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-2">
                                    <i class="fas fa-user fa-3x text-primary"></i>
                                </div>
                                <h5 class="fw-bold text-body mb-0">{{ $fullBorrowerDetails->firstname }}
                                    {{ $fullBorrowerDetails->surname }}</h5>
                                <p class="text-muted small">{{ $fullBorrowerDetails->cardnumber }}</p>
                            </div>

                            <div class="list-group list-group-flush rounded-3">
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3 border-bottom">
                                    <span class="text-muted small fw-bold text-uppercase">Email</span>
                                    <span
                                        class="fw-medium text-body text-end">{{ $fullBorrowerDetails->email ?: '-' }}</span>
                                </div>
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3 border-bottom">
                                    <span class="text-muted small fw-bold text-uppercase">Nama</span>
                                    <span class="fw-medium text-body text-end">{{ $fullBorrowerDetails->firstname }}
                                        {{ $fullBorrowerDetails->surname }}</span>
                                </div>
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3">
                                    <span class="text-muted small fw-bold text-uppercase">Kategori</span>
                                    <span
                                        class="badge bg-primary-soft text-primary rounded-pill px-3">{{ $fullBorrowerDetails->categorycode ?? 'UMUM' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. TABEL RIWAYAT KUNJUNGAN --}}
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold m-0 text-body">Riwayat Kunjungan Bulanan</h5>
                            <span class="badge bg-primary rounded-pill px-3">Total: {{ $dataKunjungan->total() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="kunjunganTable">
                                    <thead class="">
                                        <tr>
                                            <th class="py-3 px-4 border-bottom-0 text-center" width="5%">No</th>
                                            <th class="py-3 px-4 border-bottom-0">Bulan Tahun</th>
                                            <th class="py-3 px-4 border-bottom-0 text-center">Jumlah</th>
                                            <th class="py-3 px-4 border-bottom-0 text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataKunjungan as $row)
                                            <tr>
                                                <td class="px-4 text-center text-muted fw-bold">
                                                    {{ ($dataKunjungan->currentPage() - 1) * $dataKunjungan->perPage() + $loop->iteration }}
                                                </td>
                                                <td class="px-4 fw-medium text-body">
                                                    <i class="far fa-calendar-alt me-2 text-muted"></i>
                                                    @php
                                                        try {
                                                            $dateString = (string) $row->tahun_bulan;
                                                            echo !empty($dateString)
                                                                ? \Carbon\Carbon::createFromFormat(
                                                                    'Ym',
                                                                    $dateString,
                                                                )->format('F Y')
                                                                : '-';
                                                        } catch (\Exception $e) {
                                                            echo $row->tahun_bulan ?? '-';
                                                        }
                                                    @endphp
                                                </td>
                                                <td class="px-4 text-center">
                                                    <span
                                                        class="badge bg-primary-soft text-primary px-3 py-1 rounded-pill fw-bold">
                                                        {{ $row->jumlah_kunjungan }}
                                                    </span>
                                                </td>
                                                <td class="px-4 text-end">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-modal-lokasi shadow-sm"
                                                        data-tahun-bulan="{{ $row->tahun_bulan }}"
                                                        data-cardnumber="{{ $cardnumber }}">
                                                        <i class="fas fa-map-marker-alt me-1"></i> Lokasi
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @if ($dataKunjungan->hasPages())
                            <div class="card-footer border-0 py-3">
                                <div class="d-flex justify-content-end">
                                    {{ $dataKunjungan->appends(request()->query())->links() }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            {{-- EMPTY STATE --}}
            @if (request('cardnumber'))
                <div class="row justify-content-center mt-5">
                    <div class="col-md-6">
                        <div class="text-center p-5 border-0  rounded-4">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3 opacity-50"></i>
                            <h5 class="fw-bold text-body">Data Anggota Tidak Ditemukan</h5>
                            <p class="text-muted">Pastikan nomor kartu anggota yang dimasukkan sudah benar.</p>
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
                                    <i class="fas fa-search-plus fa-3x text-primary"></i>
                                </div>
                                <h4 class="fw-bold text-body">Mulai Pencarian</h4>
                                <p class="text-muted mb-0">Masukkan Nomor Kartu Anggota di atas untuk melihat riwayat
                                    kunjungan.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- MODAL LOKASI (Tetap Sama Fungsionalitasnya) --}}
    <div class="modal fade" id="lokasiModal" tabindex="-1" aria-labelledby="lokasiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 py-3">
                    <div>
                        <h5 class="modal-title fw-bold text-body" id="lokasiModalLabel">Detail Lokasi Kunjungan</h5>
                        <small class="text-muted" id="modalBulanTahun"></small>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class=" sticky-top">
                                <tr>
                                    <th class="py-3 px-4 border-bottom-0 text-center text-muted small text-uppercase"
                                        width="10%">No</th>
                                    <th class="py-3 px-4 border-bottom-0 text-muted small text-uppercase">Waktu Kunjungan
                                    </th>
                                    <th class="py-3 px-4 border-bottom-0 text-muted small text-uppercase">Lokasi</th>
                                </tr>
                            </thead>
                            <tbody id="lokasiTableBody"></tbody>
                        </table>
                    </div>
                    <div id="paginationLinks" class="d-flex justify-content-center py-3"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4"
                        data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPT JAVASCRIPT --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // --- Export Buttons Logic ---
            const downloadPdfButton = document.getElementById("downloadPdfButton");
            if (downloadPdfButton) {
                downloadPdfButton.addEventListener("click", function() {
                    const cardnumber = document.getElementById('cardnumber').value;
                    const tahun = document.getElementById('tahun').value;
                    if (cardnumber) {
                        window.open(
                            `{{ route('kunjungan.export_pdf') }}?cardnumber=${cardnumber}&tahun=${tahun}`,
                            '_blank');
                    } else {
                        alert("Mohon masukkan Nomor Kartu Anggota terlebih dahulu.");
                    }
                });
            }

            const downloadExportDataButton = document.getElementById("downloadExportDataButton");
            if (downloadExportDataButton) {
                downloadExportDataButton.addEventListener("click", async function() {
                    const cardnumber = document.getElementById('cardnumber').value;
                    const tahun = document.getElementById('tahun').value;

                    if (!cardnumber) {
                        alert("Mohon masukkan Nomor Kartu Anggota terlebih dahulu.");
                        return;
                    }

                    // Animasi Loading Tombol
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    this.disabled = true;

                    try {
                        const response = await fetch(
                            `{{ route('kunjungan.get_export_data') }}?cardnumber=${cardnumber}&tahun=${tahun}`
                        );
                        const result = await response.json();

                        if (response.ok) {
                            if (result.data.length === 0) {
                                alert("Tidak ada data untuk diekspor.");
                            } else {
                                let csv = [];
                                const delimiter = ';';
                                const BOM = "\uFEFF";
                                const headers = ['No.', 'Bulan Tahun', 'Jumlah Kunjungan'];
                                csv.push(headers.join(delimiter));

                                result.data.forEach((row, index) => {
                                    let formattedDate = "Data Tidak Valid";
                                    if (row.tahun_bulan) {
                                        try {
                                            const year = row.tahun_bulan.toString().substring(0,
                                                4);
                                            const month = row.tahun_bulan.toString().substring(
                                                4, 6);
                                            const dateObj = new Date(year, month - 1);
                                            formattedDate = dateObj.toLocaleString('id-ID', {
                                                month: 'short',
                                                year: 'numeric'
                                            });
                                        } catch (e) {
                                            formattedDate = row.tahun_bulan;
                                        }
                                    }
                                    csv.push([index + 1, `"${formattedDate}"`, row
                                        .jumlah_kunjungan || 0
                                    ].join(delimiter));
                                });

                                const blob = new Blob([BOM + csv.join('\n')], {
                                    type: 'text/csv;charset=utf-8;'
                                });
                                const link = document.createElement("a");
                                const fileName =
                                    `riwayat_kunjungan_${result.cardnumber}_${new Date().toISOString().slice(0,10)}.csv`;

                                link.href = URL.createObjectURL(blob);
                                link.download = fileName;
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                            }
                        } else {
                            alert(result.error || "Terjadi kesalahan saat mengambil data export.");
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert("Terjadi kesalahan teknis saat mencoba mengekspor data.");
                    } finally {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                });
            }

            // --- Chart Logic ---
            @if (isset($fullBorrowerDetails) && $fullBorrowerDetails && $dataKunjungan->isNotEmpty())
                const chartCanvas = document.getElementById('chartKunjungan');
                const chart = chartCanvas.getContext('2d');

                new Chart(chart, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode(
                            $dataKunjungan->pluck('tahun_bulan')->map(function ($v) {
                                try {
                                    return $v ? \Carbon\Carbon::createFromFormat('Ym', (string) $v)->format('M Y') : '-';
                                } catch (\Exception $e) {
                                    return (string) $v;
                                }
                            }),
                        ) !!},
                        datasets: [{
                            label: 'Jumlah Kunjungan',
                            data: {!! json_encode($dataKunjungan->pluck('jumlah_kunjungan')) !!},
                            backgroundColor: 'rgba(13, 110, 253, 0.1)', // Soft Primary
                            borderColor: '#0d6efd', // Primary
                            borderWidth: 2,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#0d6efd',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0.4,
                            fill: true
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
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + ' Kunjungan';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f0f2f5',
                                    drawBorder: false
                                },
                                ticks: {
                                    precision: 0,
                                    color: '#6c757d',
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#6c757d',
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });

                // --- Modal Lokasi Logic ---
                async function fetchLokasiData(cardnumber, tahunBulan, page = 1) {
                    const modalBulanTahun = document.getElementById('modalBulanTahun');
                    const lokasiTableBody = document.getElementById('lokasiTableBody');
                    const paginationLinks = document.getElementById('paginationLinks');

                    modalBulanTahun.textContent = `Memuat data...`;
                    lokasiTableBody.innerHTML =
                        '<tr><td colspan="3" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading...</td></tr>';
                    paginationLinks.innerHTML = '';

                    try {
                        const response = await fetch(
                            `{{ route('kunjungan.get_lokasi_detail') }}?cardnumber=${cardnumber}&tahun_bulan=${tahunBulan}&page=${page}`
                        );
                        const result = await response.json();

                        if (response.ok) {
                            modalBulanTahun.textContent = result.bulan_tahun_formatted;
                            lokasiTableBody.innerHTML = '';

                            if (result.lokasi.length > 0) {
                                result.lokasi.forEach((lokasi, index) => {
                                    const startCount = (result.pagination_data.current_page - 1) *
                                        result.pagination_data.per_page;
                                    const dateFormatted = new Date(lokasi.visit_date).toLocaleString(
                                        'id-ID', {
                                            dateStyle: 'medium',
                                            timeStyle: 'short'
                                        });

                                    const tr = document.createElement('tr');
                                    tr.innerHTML = `
                                    <td class="text-center text-muted small">${startCount + index + 1}</td>
                                    <td class="text-body fw-medium">${dateFormatted}</td>
                                    <td><span class="badge bg-primary-soft text-primary rounded-pill border border-info border-opacity-25">${lokasi.visit_location}</span></td>
                                `;
                                    lokasiTableBody.appendChild(tr);
                                });

                                // Pagination Logic (Simplified)
                                const pageData = result.pagination_data;
                                let navHtml = `<nav><ul class="pagination pagination-sm m-0">`;

                                // Prev
                                navHtml +=
                                    `<li class="page-item ${!pageData.prev_page_url ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${pageData.current_page - 1}">Previous</a></li>`;
                                // Pages (Simple Range)
                                for (let i = 1; i <= pageData.last_page; i++) {
                                    if (i == 1 || i == pageData.last_page || (i >= pageData.current_page - 1 &&
                                            i <= pageData.current_page + 1)) {
                                        navHtml +=
                                            `<li class="page-item ${i === pageData.current_page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                                    } else if (i == pageData.current_page - 2 || i == pageData.current_page +
                                        2) {
                                        navHtml +=
                                            `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                                    }
                                }
                                // Next
                                navHtml +=
                                    `<li class="page-item ${!pageData.next_page_url ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${pageData.current_page + 1}">Next</a></li>`;
                                navHtml += `</ul></nav>`;

                                paginationLinks.innerHTML = navHtml;

                                // Add Event Listeners for Pagination
                                paginationLinks.querySelectorAll('a.page-link').forEach(link => {
                                    link.addEventListener('click', (e) => {
                                        e.preventDefault();
                                        const newPage = e.target.getAttribute('data-page');
                                        if (newPage && newPage > 0) fetchLokasiData(cardnumber,
                                            tahunBulan, newPage);
                                    });
                                });
                            } else {
                                lokasiTableBody.innerHTML =
                                    '<tr><td colspan="3" class="text-muted text-center py-4">Tidak ada data lokasi.</td></tr>';
                            }
                        } else {
                            lokasiTableBody.innerHTML =
                                `<tr><td colspan="3" class="text-danger text-center py-4">Error: ${result.error}</td></tr>`;
                        }
                    } catch (error) {
                        console.error(error);
                        lokasiTableBody.innerHTML =
                            `<tr><td colspan="3" class="text-danger text-center py-4">Terjadi kesalahan teknis.</td></tr>`;
                    }
                }

                document.querySelectorAll('.btn-modal-lokasi').forEach(button => {
                    button.addEventListener('click', function() {
                        const cardnumber = this.getAttribute('data-cardnumber');
                        const tahunBulan = this.getAttribute('data-tahun-bulan');
                        const modalEl = document.getElementById('lokasiModal');
                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                        fetchLokasiData(cardnumber, tahunBulan);
                    });
                });
            @endif
        });
    </script>
@endsection
