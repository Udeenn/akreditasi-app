@extends('layouts.app')
@section('title', 'Statistik Koleksi Per Prodi')

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">

        {{-- 1. HEADER BANNER --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card unified-card border-0 shadow-sm page-header-banner">
                    <div
                        class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0">
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-chart-bar me-2"></i>Statistik Koleksi Per Prodi
                            </h3>
                            <p class="mb-0 opacity-75">
                                Rekapitulasi jumlah koleksi berdasarkan program studi
                                @if ($prodi)
                                    — {{ $namaProdi }}
                                @endif
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-university fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FILTER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card unified-card border-0 shadow-sm filter-card">
                    <div class="card-header border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold text-primary"><i class="fas fa-filter me-1"></i> Filter Data</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('koleksi.prodi') }}" class="row g-3 align-items-end">
                            <div class="col-12 col-md-4">
                                <label for="prodi" class="form-label small text-muted fw-bold">Pilih Prodi</label>
                                <select name="prodi" id="prodi" class="form-select">
                                    <option value="">-- Pilih Program Studi --</option>
                                    @foreach ($listprodi as $itemProdi)
                                        <option value="{{ $itemProdi->kode }}"
                                            {{ $prodi == $itemProdi->kode ? 'selected' : '' }}>
                                            ({{ $itemProdi->kode }})
                                            -- {{ $itemProdi->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="tahun" class="form-label small text-muted fw-bold">Tahun Terbit</label>
                                <select name="tahun" id="tahun" class="form-select">
                                    <option value="all" {{ $tahunTerakhir == 'all' ? 'selected' : '' }}>Semua Tahun
                                    </option>
                                    @for ($i = 1; $i <= 10; $i++)
                                        <option value="{{ $i }}"
                                            {{ $tahunTerakhir == $i ? 'selected' : '' }}>
                                            {{ $i }} Tahun Terakhir
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. DATA TABLE --}}
        <div class="card unified-card border-0 shadow-sm">
            <div class="card-body">
                @if ($prodi && $data->isNotEmpty())
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">
                            <i class="fas fa-table me-1 text-primary"></i>
                            Koleksi Per Prodi
                        </h6>
                        <button id="downloadExcelPerProdi" class="btn btn-warning btn-sm">
                            <i class="fas fa-file-excel me-1"></i> Save Tabel (Excel)
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 unified-table" id="myTablePerProdi">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Jenis</th>
                                    <th>Koleksi (ccode)</th>
                                    <th>Jumlah Judul Buku</th>
                                    <th>Jumlah Eksemplar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $no => $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $row->Jenis }}</td>
                                        <td>{{ $row->Koleksi }}</td>
                                        <td>{{ $row->Judul }}</td>
                                        <td>{{ $row->Eksemplar }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Data tidak ditemukan untuk prodi ini
                                            @if ($tahunTerakhir !== 'all')
                                                dalam {{ $tahunTerakhir }} tahun terakhir
                                            @endif.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif ($prodi && $data->isEmpty())
                    <div class="alert alert-info text-center" role="alert">
                        Data tidak ditemukan untuk program studi ini
                        @if ($tahunTerakhir !== 'all')
                            dalam {{ $tahunTerakhir }} tahun terakhir
                        @endif.
                    </div>
                @else
                    <div class="alert alert-info text-center" role="alert">
                        Silakan pilih program studi dan filter tahun untuk menampilkan data koleksi.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const downloadExcelButton = document.getElementById("downloadExcelPerProdi");
            if (downloadExcelButton) {
                downloadExcelButton.addEventListener("click", function() {
                    const table = document.getElementById("myTablePerProdi");
                    if (!table) {
                        console.error("Table 'myTablePerProdi' not found.");
                        return;
                    }
                    let csv = [];
                    const delimiter = ';';


                    const headers = Array.from(table.querySelectorAll('thead th')).map(th => {
                        let text = th.innerText.trim();
                        text = text.replace(/"/g, '""');
                        if (text.includes(delimiter) || text.includes('"') || text.includes('\n')) {
                            text = `"${text}"`;
                        }
                        return text;
                    });
                    csv.push(headers.join(delimiter));

                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const rowData = Array.from(row.querySelectorAll('td')).map(td => {
                            let text = td.innerText.trim();
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
                    const fileName = "koleksi_per_prodi_data.csv";

                    if (navigator.msSaveBlob) {
                        navigator.msSaveBlob(blob, fileName);
                    } else {
                        link.href = URL.createObjectURL(blob);
                        link.download = fileName;
                        link.click();
                        URL.revokeObjectURL(link.href);
                    }
                });
            }
        });
    </script>
@endsection
