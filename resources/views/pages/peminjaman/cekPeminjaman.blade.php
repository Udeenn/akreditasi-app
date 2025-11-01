@extends('layouts.app')

@section('content')
@section('title', 'Cek Histori Peminjaman')

<div class="container">
    <div class="d-flex align-items-center mb-4">
        <i class="fas fa-user-clock fa-2x text-primary me-3"></i>
        <div>
            <h4 class="mb-0">Cek Histori Peminjaman</h4>
            <small class="text-muted">Lacak riwayat peminjaman dan pengembalian anggota.</small>
        </div>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('peminjaman.check_history') }}" method="GET">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="cardnumber" id="cardnumber" class="form-control form-control-lg"
                        value="{{ $cardnumber ?? '' }}" placeholder="Masukkan Nomor Kartu Peminjam lalu tekan Enter...">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>
            </form>
        </div>
    </div>

    @if ($errorMessage)
        <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-triangle me-2"></i>
            {{ $errorMessage }}</div>
    @endif

    @if ($borrower)
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($borrower->firstname . ' ' . $borrower->surname) }}&background=0D6EFD&color=fff&size=100"
                            class="rounded-circle mb-3" alt="Avatar">
                        <h5 class="card-title">{{ $borrower->firstname }} {{ $borrower->surname }}</h5>
                        <p class="card-text text-muted">{{ $borrower->cardnumber }}</p>
                        <hr>
                        <div class="text-start mb-3">
                            <p><i class="fas fa-envelope fa-fw me-2 text-muted"></i>{{ $borrower->email ?? '-' }}</p>
                            <p><i class="fas fa-phone fa-fw me-2 text-muted"></i>{{ $borrower->phone ?? '-' }}</p>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-around">
                            <div>
                                <small class="text-muted">Total Peminjaman</small>
                                <h4 class="mb-0 fw-bold text-success">{{ $borrowingHistory->total() }}</h4>
                            </div>
                            <div>
                                <small class="text-muted">Total Pengembalian</small>
                                <h4 class="mb-0 fw-bold text-danger">{{ $returnHistory->total() }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                {{-- Histori Peminjaman --}}
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-success"><i class="fas fa-arrow-down me-2"></i>Histori Peminjaman (Issue &
                            Renew)</h6>
                        @if ($borrowingHistory->isNotEmpty())
                            <button type="button" id="exportBorrowingHistory" class="btn btn-sm btn-outline-success"><i
                                    class="fas fa-file-csv"></i> Export</button>
                        @endif
                    </div>
                    <div class="card-body">
                        @if ($borrowingHistory->isEmpty())
                            <div class="alert alert-light text-center">Belum ada histori peminjaman.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover" id="borrowingTable">
                                    <thead>
                                        <tr>
                                            <th>Judul Buku</th>
                                            <th>Tipe</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($borrowingHistory as $history)
                                            <tr>
                                                <td>
                                                    <strong>{{ $history->title }}</strong>
                                                    <br>
                                                    <small class="text-muted">Barcode: {{ $history->barcode }}</small>
                                                </td>
                                                <td>
                                                    @if (strtolower($history->type) == 'issue')
                                                        <span class="badge bg-primary">Pinjam</span>
                                                    @else
                                                        <span class="badge bg-info">Perpanjang</span>
                                                    @endif
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($history->datetime)->format('d M Y, H:i') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-end mt-3">
                                    {{ $borrowingHistory->links() }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Histori Pengembalian --}}
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-danger"><i class="fas fa-arrow-up me-2"></i>Histori Pengembalian (Return)
                        </h6>
                        @if ($returnHistory->isNotEmpty())
                            <button type="button" id="exportReturnHistory" class="btn btn-sm btn-outline-success"><i
                                    class="fas fa-file-csv"></i> Export</button>
                        @endif
                    </div>
                    <div class="card-body">
                        @if ($returnHistory->isEmpty())
                            <div class="alert alert-light text-center">Belum ada histori pengembalian.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover" id="returnTable">
                                    <thead>
                                        <tr>
                                            <th>Judul Buku</th>
                                            <th>Tipe</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($returnHistory as $history)
                                            <tr>
                                                <td>
                                                    <strong>{{ $history->title }}</strong>
                                                    <br>
                                                    <small class="text-muted">Barcode: {{ $history->barcode }}</small>
                                                </td>
                                                <td>
                                                    @if (strtolower($history->type) == 'issue')
                                                        <span class="badge bg-primary">Pinjam Awal</span>
                                                    @elseif(strtolower($history->type) == 'renew')
                                                        <span class="badge bg-info text-dark">Perpanjangan</span>
                                                    @else
                                                        <span
                                                            class="badge bg-secondary">{{ ucfirst($history->type) }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($history->datetime)->format('d M Y, H:i') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-end mt-3">
                                    {{ $returnHistory->links() }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif (request()->has('cardnumber') && !$errorMessage)
        <div class="alert alert-warning d-flex align-items-center"><i class="fas fa-question-circle me-2"></i>Nomor
            kartu peminjam "<strong>{{ $cardnumber }}</strong>" tidak ditemukan.</div>
    @endif
</div>

<script>
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
                        alert(`Tidak ada data untuk diekspor untuk ${result.type} ini.`);
                        return;
                    }

                    let csv = [];
                    const delimiter = ';';

                    csv.push(headers.join(delimiter));

                    result.data.forEach(row => {
                        const rowData = headers.map(header => {
                            const key = header.toLowerCase().replace(/ & /g, '_').replace(
                                / /g, '_');
                            let text = row[key] !== undefined ? String(row[key]) :
                                '';
                            text = text.replace(/"/g, '""');
                            if (text.includes(delimiter) || text.includes('"') || text
                                .includes('\n')) {
                                text = `"${text}"`;
                            }
                            return text;
                        });
                        csv.push(rowData.join(delimiter));
                    });

                    const csvString = csv.join('\n');
                    const BOM = "\uFEFF";
                    const blob = new Blob([BOM + csvString], {
                        type: 'text/csv;charset=utf-8;'
                    });

                    const link = document.createElement("a");
                    const fileName =
                        `${defaultFileName}_${result.cardnumber}_${(result.borrower_name || 'unknown').replace(/\s+/g, '_').toLowerCase()}_${new Date().toISOString().slice(0,10).replace(/-/g,'')}.csv`;

                    if (navigator.msSaveBlob) {
                        navigator.msSaveBlob(blob, fileName);
                    } else {
                        link.href = URL.createObjectURL(blob);
                        link.download = fileName;
                        document.body.appendChild(
                            link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(link.href);
                    }
                } else {
                    alert(result.error || "Terjadi kesalahan saat mengambil data export.");
                }
            } catch (error) {
                console.error('Error fetching export data:', error);
                alert("Terjadi kesalahan teknis saat mencoba mengekspor data.");
            }
        }

        const exportBorrowingHistoryButton = document.getElementById("exportBorrowingHistory");
        if (exportBorrowingHistoryButton) {
            exportBorrowingHistoryButton.addEventListener("click", function() {
                const headers = ['Tanggal & Waktu', 'Tipe', 'Barcode Buku', 'Judul Buku', 'Pengarang'];
                downloadCsv(`{{ route('peminjaman.get_borrowing_export_data') }}`,
                    'histori_peminjaman', headers);
            });
        }

        // Event Listener untuk Export Histori Pengembalian
        const exportReturnHistoryButton = document.getElementById("exportReturnHistory");
        if (exportReturnHistoryButton) {
            exportReturnHistoryButton.addEventListener("click", function() {
                const headers = ['Tanggal & Waktu', 'Tipe', 'Barcode Buku', 'Judul Buku', 'Pengarang'];
                downloadCsv(`{{ route('peminjaman.get_return_export_data') }}`, 'histori_pengembalian',
                    headers);
            });
        }
    });
</script>
@endsection
