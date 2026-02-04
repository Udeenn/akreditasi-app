@extends('layouts.app')

@section('title', 'Cek Histori Peminjaman')

@push('styles')
    <style>
        /* --- MODERN STYLING (CONSISTENT THEME) --- */
        :root {
            --primary-soft: rgba(13, 110, 253, 0.1);
            --success-soft: rgba(25, 135, 84, 0.1);
            --danger-soft: rgba(220, 53, 69, 0.1);
            --warning-soft: rgba(255, 193, 7, 0.1);
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            overflow: hidden;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
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
            border-radius: 50%;
            font-size: 1.25rem;
            flex-shrink: 0;
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

        .bg-warning-soft {
            background-color: var(--warning-soft);
            color: #ffc107;
        }

        /* Table Styling */
        .table thead th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        /* Dark Mode */
        body.dark-mode .card {
            background-color: #1e1e2d;
            border: 1px solid #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .card-header {
            background-color: #1e1e2d !important;
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

        body.dark-mode .input-group-text {
            background-color: #2b2b40;
            border-color: #2b2b40;
            color: #ffffff;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">

        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div
                        class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0 text-center text-md-start">
                            <h3 class="fw-bold mb-1"><i class="fas fa-history me-2"></i>Cek Histori Peminjaman</h3>
                            <p class="mb-0 opacity-75">Lacak riwayat peminjaman dan pengembalian buku anggota perpustakaan.
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-user-clock fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. SEARCH SECTION --}}
        <div class="row justify-content-center mb-5">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm hover-lift">
                    <div class="card-body p-4">
                        <form action="{{ route('peminjaman.check_history') }}" method="GET">
                            <label for="cardnumber" class="form-label fw-bold text-muted small text-uppercase">Cari
                                Anggota</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text border-0 "><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="cardnumber" id="cardnumber"
                                    class="form-control border-0  fw-bold" value="{{ $cardnumber ?? '' }}"
                                    placeholder="Masukkan Nomor Kartu Peminjam (NIM/ID)..." autofocus>

                                {{-- Filter Tahun --}}
                                <select name="tahun" class="form-select border-0" style="max-width: 180px; border-left: 1px solid var(--bs-border-color) !important;">
                                    <option value="">Semua Tahun</option>
                                    @for ($y = date('Y'); $y >= 2019; $y--)
                                        <option value="{{ $y }}" {{ ($tahun ?? '') == $y ? 'selected' : '' }}>
                                            {{ $y }}</option>
                                    @endfor
                                </select>

                                <button type="submit" class="btn btn-primary px-4 fw-bold">Cari Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. ERROR MESSAGE --}}
        @if ($errorMessage)
            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center mb-4" role="alert">
                <div class="icon-box bg-danger-soft text-danger me-3"><i class="fas fa-exclamation-triangle"></i></div>
                <div>{{ $errorMessage }}</div>
            </div>
        @elseif (request()->has('cardnumber') && !$borrower)
            <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center mb-4" role="alert">
                <div class="icon-box bg-warning-soft text-warning me-3"><i class="fas fa-search-minus"></i></div>
                <div>Nomor kartu peminjam "<strong>{{ $cardnumber }}</strong>" tidak ditemukan dalam database.</div>
            </div>
        @endif

        {{-- 4. RESULT SECTION --}}
        @if ($borrower)
            <div class="row g-4">
                {{-- LEFT COLUMN: PROFILE --}}
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4 text-center">
                            <div class="mb-4">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($borrower->firstname . ' ' . $borrower->surname) }}&background=0d6efd&color=fff&size=128&bold=true"
                                    class="rounded-circle shadow-sm border border-4 border-light" alt="Avatar"
                                    width="100">
                            </div>
                            <h5 class="fw-bold mb-1">{{ $borrower->firstname }} {{ $borrower->surname }}</h5>
                            <span class="badge   border px-3 py-2 rounded-pill mb-3">{{ $borrower->cardnumber }}</span>

                            <div class="d-flex justify-content-center gap-2 mb-4">
                                @if ($borrower->email)
                                    <a href="mailto:{{ $borrower->email }}"
                                        class="btn btn-sm btn-outline-primary rounded-pill"><i
                                            class="fas fa-envelope me-1"></i> Email</a>
                                @endif
                                {{-- <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-phone me-1"></i> Hubungi</a> --}}
                            </div>

                            <div class="row g-2 mt-2">
                                <div class="col-6">
                                    <div class="p-3 border rounded-3  h-100">
                                        <div class="icon-box bg-success-soft mx-auto mb-2"
                                            style="width: 36px; height: 36px; font-size: 1rem;">
                                            <i class="fas fa-arrow-down"></i>
                                        </div>
                                        <h4 class="fw-bold mb-0 text-success">{{ $borrowingHistory->total() }}</h4>
                                        <small class="text-muted" style="font-size: 0.7rem;">TOTAL PINJAM</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 border rounded-3  h-100">
                                        <div class="icon-box bg-danger-soft mx-auto mb-2"
                                            style="width: 36px; height: 36px; font-size: 1rem;">
                                            <i class="fas fa-arrow-up"></i>
                                        </div>
                                        <h4 class="fw-bold mb-0 text-danger">{{ $returnHistory->total() }}</h4>
                                        <small class="text-muted" style="font-size: 0.7rem;">TOTAL KEMBALI</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN: HISTORY TABLES --}}
                <div class="col-lg-8">
                    {{-- A. Borrowing History --}}
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header  d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-success mb-0">
                                <i class="fas fa-history me-2"></i>Riwayat Peminjaman
                            </h6>
                            @if ($borrowingHistory->isNotEmpty())
                                <button type="button" id="exportBorrowingHistory"
                                    class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                                    <i class="fas fa-file-csv me-1"></i> Export
                                </button>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            @if ($borrowingHistory->isEmpty())
                                <div class="text-center py-5">
                                    <i class="fas fa-clipboard-list fa-3x text-muted opacity-25 mb-3"></i>
                                    <p class="text-muted mb-0">Belum ada riwayat peminjaman.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="">
                                            <tr>
                                                <th style="width: 50%;">Buku</th>
                                                <th style="width: 20%;">Tipe Transaksi</th>
                                                <th style="width: 30%;">Waktu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($borrowingHistory as $history)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="icon-box  text-muted me-3 rounded-3"
                                                                style="width: 40px; height: 40px; font-size: 1rem;">
                                                                <i class="fas fa-book"></i>
                                                            </div>
                                                            <div>
                                                                <span class="fw-bold d-block text-truncate"
                                                                    style="max-width: 250px;">{{ $history->title }}</span>
                                                                <small
                                                                    class="text-muted font-monospace">{{ $history->barcode }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if (strtolower($history->type) == 'issue')
                                                            <span
                                                                class="badge bg-success-soft text-success rounded-pill px-3"><i
                                                                    class="fas fa-arrow-down me-1"></i> Pinjam</span>
                                                        @else
                                                            <span
                                                                class="badge bg-warning-soft text-warning rounded-pill px-3"><i
                                                                    class="fas fa-redo me-1"></i> Perpanjang</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted small">
                                                        <i class="far fa-calendar-alt me-1"></i>
                                                        {{ \Carbon\Carbon::parse($history->datetime)->format('d M Y, H:i') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer  border-0 py-3">
                                    <div class="d-flex justify-content-end">
                                        {{ $borrowingHistory->appends(request()->input())->links() }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- B. Return History --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header  d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-danger mb-0">
                                <i class="fas fa-undo-alt me-2"></i>Riwayat Pengembalian
                            </h6>
                            @if ($returnHistory->isNotEmpty())
                                <button type="button" id="exportReturnHistory"
                                    class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm">
                                    <i class="fas fa-file-csv me-1"></i> Export
                                </button>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            @if ($returnHistory->isEmpty())
                                <div class="text-center py-5">
                                    <i class="fas fa-box-open fa-3x text-muted opacity-25 mb-3"></i>
                                    <p class="text-muted mb-0">Belum ada riwayat pengembalian.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="">
                                            <tr>
                                                <th style="width: 50%;">Buku</th>
                                                <th style="width: 20%;">Tipe Transaksi</th>
                                                <th style="width: 30%;">Waktu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($returnHistory as $history)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="icon-box  text-muted me-3 rounded-3"
                                                                style="width: 40px; height: 40px; font-size: 1rem;">
                                                                <i class="fas fa-book"></i>
                                                            </div>
                                                            <div>
                                                                <span class="fw-bold d-block text-truncate"
                                                                    style="max-width: 250px;">{{ $history->title }}</span>
                                                                <small
                                                                    class="text-muted font-monospace">{{ $history->barcode }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-danger-soft text-danger rounded-pill px-3"><i
                                                                class="fas fa-arrow-up me-1"></i> Kembali</span>
                                                    </td>
                                                    <td class="text-muted small">
                                                        <i class="far fa-calendar-alt me-1"></i>
                                                        {{ \Carbon\Carbon::parse($history->datetime)->format('d M Y, H:i') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer  border-0 py-3">
                                    <div class="d-flex justify-content-end">
                                        {{ $returnHistory->appends(request()->input())->links() }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- SCRIPT EXPORT (TIDAK BERUBAH SECARA LOGIKA, HANYA DIPASANG KEMBALI) --}}
    {{-- <script>
        document.addEventListener("DOMContentLoaded", function() {
            async function downloadCsv(url, defaultFileName, headers) {
                const cardnumber = document.getElementById('cardnumber').value;
                if (!cardnumber) {
                    alert("Mohon masukkan Nomor Kartu Peminjam terlebih dahulu.");
                    return;
                }

                try {
                    const response = await fetch(`${url}?cardnumber=${cardnumber}`);
                    const result = await response.json();

                    if (response.ok) {
                        if (result.data.length === 0) {
                            alert(`Tidak ada data untuk diekspor.`);
                            return;
                        }

                        let csv = [];
                        const delimiter = ';';
                        // Add BOM for Excel
                        const BOM = "\uFEFF";

                        // Add Title
                        csv.push([`Laporan ${defaultFileName.replace(/_/g, ' ').toUpperCase()}`]);
                        csv.push([`Peminjam: ${result.borrower_name} (${result.cardnumber})`]);
                        csv.push([]); // Empty line

                        // Headers
                        csv.push(headers.join(delimiter));

                        // Data
                        result.data.forEach(row => {
                            const rowData = headers.map(header => {
                                // Simple mapping logic (sesuaikan jika key di JSON beda)
                                // Asumsi: key di JSON adalah lowercase dari header (e.g. 'Judul Buku' -> 'judul_buku')
                                // Di controller Anda mungkin perlu menyesuaikan key JSON response agar sesuai.
                                // Fallback sederhana:
                                let val = '';
                                if (header.includes('Tanggal')) val = row.datetime || row
                                    .tanggal;
                                else if (header.includes('Tipe')) val = row.type;
                                else if (header.includes('Barcode')) val = row.barcode;
                                else if (header.includes('Judul')) val = row.title;
                                else if (header.includes('Pengarang')) val = row.author;

                                // Clean value
                                let text = val ? String(val) : '';
                                text = text.replace(/"/g, '""');
                                if (text.includes(delimiter) || text.includes('"') || text
                                    .includes('\n')) {
                                    text = `"${text}"`;
                                }
                                return text;
                            });
                            csv.push(rowData.join(delimiter));
                        });

                        const csvString = BOM + csv.join('\n');
                        const blob = new Blob([csvString], {
                            type: 'text/csv;charset=utf-8;'
                        });
                        const link = document.createElement("a");
                        const cleanName = (result.borrower_name || 'user').replace(/[^a-z0-9]/gi, '_')
                            .toLowerCase();
                        const fileName =
                            `${defaultFileName}_${cleanName}_${new Date().toISOString().slice(0,10)}.csv`;

                        if (navigator.msSaveBlob) {
                            navigator.msSaveBlob(blob, fileName);
                        } else {
                            link.href = URL.createObjectURL(blob);
                            link.download = fileName;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        }
                    } else {
                        alert(result.error || "Gagal mengambil data.");
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert("Terjadi kesalahan teknis.");
                }
            }

            const exportBorrowing = document.getElementById("exportBorrowingHistory");
            if (exportBorrowing) {
                exportBorrowing.addEventListener("click", function() {
                    const headers = ['Tanggal & Waktu', 'Tipe', 'Barcode Buku', 'Judul Buku', 'Pengarang'];
                    downloadCsv(`{{ route('peminjaman.get_borrowing_export_data') }}`,
                        'Histori_Peminjaman', headers);
                });
            }

            const exportReturn = document.getElementById("exportReturnHistory");
            if (exportReturn) {
                exportReturn.addEventListener("click", function() {
                    const headers = ['Tanggal & Waktu', 'Tipe', 'Barcode Buku', 'Judul Buku', 'Pengarang'];
                    downloadCsv(`{{ route('peminjaman.get_return_export_data') }}`, 'Histori_Pengembalian',
                        headers);
                });
            }
        });
    </script> --}}

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            async function downloadCsv(url, filenamePrefix) {
                // 1. Ambil cardnumber dengan aman
                // Cek apakah ada input hidden atau ambil dari input search
                let cardnumber = document.getElementById('cardnumber').value;

                // Fallback: Jika input kosong (mungkin user sudah search dan form kereset),
                // ambil dari URL parameter browser
                if (!cardnumber) {
                    const urlParams = new URLSearchParams(window.location.search);
                    cardnumber = urlParams.get('cardnumber');
                }

                if (!cardnumber) {
                    alert("Nomor Kartu Peminjam tidak ditemukan.");
                    return;
                }

                try {
                    // 2. Fetch Data
                    // Gunakan encodeURIComponent untuk keamanan
                    const response = await fetch(`${url}?cardnumber=${encodeURIComponent(cardnumber)}`);
                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.error || 'Gagal mengambil data.');
                    }

                    if (result.data.length === 0) {
                        alert("Tidak ada data untuk diekspor.");
                        return;
                    }

                    // 3. Build CSV Content
                    let csv = [];
                    const delimiter = ';';
                    const BOM = "\uFEFF"; // Agar Excel baca UTF-8 dengan benar

                    // Judul Laporan
                    csv.push([`Laporan Histori ${result.type.toUpperCase()}`]);
                    csv.push([`Nama: ${result.borrower_name}`]);
                    csv.push([`No. Kartu: ${result.cardnumber}`]);
                    csv.push([]); // Empty line

                    // Header Tabel
                    const headers = ['Tanggal & Waktu', 'Tipe', 'Barcode', 'Judul Buku', 'Pengarang'];
                    csv.push(headers.join(delimiter));

                    // Data Rows
                    // Pastikan key ini SAMA dengan yang ada di Controller (map function)
                    result.data.forEach(row => {
                        const rowData = [
                            row.tanggal_waktu,
                            row.tipe,
                            `"${row.barcode}"`, // Quote agar tidak jadi scientific number di Excel
                            `"${(row.judul || '').replace(/"/g, '""')}"`, // Escape double quotes
                            `"${(row.pengarang || '').replace(/"/g, '""')}"`
                        ];
                        csv.push(rowData.join(delimiter));
                    });

                    // 4. Download File
                    const csvString = BOM + csv.join('\n');
                    const blob = new Blob([csvString], {
                        type: 'text/csv;charset=utf-8;'
                    });
                    const link = document.createElement("a");

                    // Nama file bersih
                    const safeName = (result.borrower_name || 'user').replace(/[^a-z0-9]/gi, '_').toLowerCase();
                    const fileName =
                        `${filenamePrefix}_${safeName}_${new Date().toISOString().slice(0,10)}.csv`;

                    if (navigator.msSaveBlob) { // IE Support
                        navigator.msSaveBlob(blob, fileName);
                    } else {
                        link.href = URL.createObjectURL(blob);
                        link.download = fileName;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }

                } catch (error) {
                    console.error('Export Error:', error);
                    alert(error.message);
                }
            }

            // Event Listeners
            const btnBorrowing = document.getElementById("exportBorrowingHistory");
            if (btnBorrowing) {
                btnBorrowing.addEventListener("click", function() {
                    downloadCsv("{{ route('peminjaman.get_borrowing_export_data') }}",
                        "Histori_Peminjaman");
                });
            }

            const btnReturn = document.getElementById("exportReturnHistory");
            if (btnReturn) {
                btnReturn.addEventListener("click", function() {
                    downloadCsv("{{ route('peminjaman.get_return_export_data') }}",
                        "Histori_Pengembalian");
                });
            }
        });
    </script>
@endsection
