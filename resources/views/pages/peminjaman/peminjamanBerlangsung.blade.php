@extends('layouts.app')

@section('title', 'Peminjaman Berlangsung')

@section('content')
    <div class="container">
        {{-- <h4 class="mb-3">Daftar Peminjaman Berlangsung @if ($selectedProdiCode)
                - {{ $namaProdiFilter }}
            @endif
        </h4> --}}
        <div class="card bg-white shadow-sm mb-4">
        <div class="card-body">
            <h4 class="mb-0">Daftar Peminjaman Berlangsung</h4>
            <small class="text-muted">@if ($selectedProdiCode)
                 {{ $namaProdiFilter }}
            @endif</small>
        </div>
    </div>
        <form method="GET" action="{{ route('peminjaman.berlangsung') }}" class="row g-3 mb-4 align-items-end"
            id="filterPeminjamanBerlangsungForm">
            <div class="col-md-4">
                <label for="prodi" class="form-label">Filter Prodi</label>
                <select name="prodi" id="prodi" class="form-select">
                    <option value="">-- Semua Program Studi --</option>
                    @foreach ($listProdi as $prodi)
                        <option value="{{ $prodi->authorised_value }}"
                            {{ $selectedProdiCode == $prodi->authorised_value ? 'selected' : '' }}>
                            ({{$prodi->authorised_value}}) - {{ $prodi->lib }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>

        <div class="card shadow-sm">
            @if ($selectedProdiCode || $dataExists)
                <div class="card-header d-flex justify-content-between align-items-center">
                    Data Peminjaman Berlangsung
                    <button type="buton" id="exportCsvBtn" class="btn btn-success btn-sm"><i class="fas fa-file-csv"></i>
                        Export CSV</button>
                </div>
            @endif
            <div class="card-body">
                @if ($dataExists)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Waktu Peminjaman</th>
                                    <th>Barcode</th>
                                    <th class="text-center">Judul Buku</th>
                                    {{-- <th>Kode Prodi</th> --}}
                                    <th class="text-center">Peminjam</th>
                                    <th>Batas Pengembalian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activeLoans as $index => $loan)
                                    <tr>
                                        <td>{{ $activeLoans->firstItem() + $index }}</td>
                                        <td>{{ \Carbon\Carbon::parse($loan->BukuDipinjamSaat)->format('d F Y H:i:s') }}</td>
                                        <td style="width: 100px">{{ $loan->BarcodeBuku }}</td>
                                        <td>{{ $loan->JudulBuku }}</td>
                                        {{-- <td>{{ $loan->KodeProdi }}</td>  --}}
                                        <td>{{ $loan->Peminjam }}</td>
                                        <td style="color: rgb(199, 43, 43)">{{ \Carbon\Carbon::parse($loan->BatasWaktuPengembalian)->format('d F Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $activeLoans->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center" role="alert">
                        Tidak ada data peminjaman yang sedang berlangsung
                        @if ($selectedProdiCode)
                            untuk program studi {{ $namaProdiFilter }}
                        @endif
                        saat ini.
                    </div>
                @endif
                @push('scripts')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const exportBtn = document.getElementById('exportCsvBtn');
                            if (exportBtn) {
                                exportBtn.addEventListener('click', async function() {
                                    const prodi = document.getElementById('prodi').value;
                                    let url = `{{ route('peminjaman.get_berlangsung_export_data') }}`;
                                    if (prodi) {
                                        url += `?prodi=${encodeURIComponent(prodi)}`;
                                    }
                                    try {
                                        const response = await fetch(url);
                                        const result = await response.json();
                                        if (response.ok && result.data && result.data.length > 0) {
                                            const delimiter = ';';
                                            const headers = ['No.', 'Buku Dipinjam Saat', 'Judul Buku', 'Barcode Buku',
                                                'Peminjam', 'Batas Waktu Pengembalian'
                                            ];
                                            let csv = [headers.join(delimiter)];
                                            result.data.forEach((row, idx) => {
                                                csv.push([
                                                    idx + 1,
                                                    row.BukuDipinjamSaat,
                                                    `"${row.JudulBuku.replace(/"/g, '""')}"`,
                                                    row.BarcodeBuku,
                                                    // row.KodeProdi,
                                                    `"${row.Peminjam.replace(/"/g, '""')}"`,
                                                    row.BatasWaktuPengembalian
                                                ].join(delimiter));
                                            });
                                            const csvString = csv.join('\n');
                                            const BOM = "\uFEFF";
                                            const blob = new Blob([BOM + csvString], {
                                                type: 'text/csv;charset=utf-8;'
                                            });
                                            const link = document.createElement('a');
                                            let fileName = 'peminjaman_berlangsung.csv';
                                            if (prodi) fileName = `peminjaman_berlangsung_${prodi}.csv`;
                                            link.href = URL.createObjectURL(blob);
                                            link.download = fileName;
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                            URL.revokeObjectURL(link.href);
                                        } else {
                                            alert('Tidak ada data untuk diekspor.');
                                        }
                                    } catch (err) {
                                        alert('Gagal mengekspor data.');
                                    }
                                });
                            }
                        });
                    </script>
                @endpush
            </div>
        </div>
    </div>
@endsection
