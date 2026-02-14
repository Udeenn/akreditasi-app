@extends('layouts.app')
@section('title', 'Statistik Koleksi Text Book')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

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
                                <i class="fas fa-book-reader me-2"></i>Statistik Koleksi Text Book
                            </h3>
                            <p class="mb-0 opacity-75">
                                Data koleksi text book
                                @if ($prodi && $prodi !== 'all')
                                    — {{ $namaProdi }}
                                @elseif ($prodi === 'all')
                                    — Semua Program Studi
                                @endif
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-book fa-4x"></i>
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
                        <form method="GET" action="{{ route('koleksi.textbook') }}" class="row g-3 align-items-end"
                            id="filterFormTextbook">
                            <div class="col-12 col-md-4">
                                <label for="prodi" class="form-label small text-muted fw-bold">Pilih Prodi</label>
                                <select name="prodi" id="prodi" class="form-select">
                                    @foreach ($listprodi as $p)
                                        <option value="{{ $p->authorised_value }}"
                                            {{ $prodi == $p->authorised_value ? 'selected' : '' }}>
                                            ({{ $p->authorised_value }})
                                            - {{ $p->lib }}
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

        {{-- 3. STAT CARDS --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="icon-box bg-info-soft text-info me-3">
                            <i class="fas fa-book"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Total Judul Buku</div>
                            <div class="fs-4 fw-bold">{{ number_format($totalJudul, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="icon-box bg-success-soft text-success me-3">
                            <i class="fas fa-copy"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Total Eksemplar</div>
                            <div class="fs-4 fw-bold">{{ number_format($totalEksemplar, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. DATA TABLE --}}
        <div class="card unified-card border-0 shadow-sm">
            @if ($prodi && $prodi !== 'initial' && $dataExists)
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold">
                        <i class="fas fa-table me-1 text-primary"></i>
                        Daftar Koleksi Text Book
                        @if ($namaProdi && $prodi !== 'all')
                            ({{ $namaProdi }})
                        @elseif ($prodi === 'all')
                            (Semua Program Studi)
                        @endif
                        @if ($tahunTerakhir !== 'all')
                            - {{ $tahunTerakhir }} Tahun Terakhir
                        @endif
                    </h6>
                    <button type="submit" form="filterFormTextbook" name="export_csv" value="1"
                        class="btn btn-success btn-sm"><i class="fas fa-file-csv me-1"></i> Export CSV</button>
                </div>
            @endif
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="Cari judul, penerbit, atau nomor...">
                </div>
                @if ($prodi && $prodi !== 'initial' && $data->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 unified-table" id="myTableTextbook">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Pengarang</th>
                                    <th>Kota Terbit</th>
                                    <th>Penerbit</th>
                                    <th>Tahun Terbit</th>
                                    <th>Eksemplar</th>
                                    <th>Lokasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row->Judul }}</td>
                                        <td>{{ $row->Pengarang }}</td>
                                        <td>{{ $row->Kota_Terbit }}</td>
                                        <td>{{ $row->Penerbit }}</td>
                                        <td>{{ $row->Tahun_Terbit }}</td>
                                        <td>{{ $row->Eksemplar }}</td>
                                        <td>{{ $row->Lokasi }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif ($prodi && $prodi !== 'initial' && $data->isEmpty())
                    <div class="alert alert-info text-center" role="alert">
                        Data tidak ditemukan untuk program studi ini @if ($tahunTerakhir !== 'all')
                            dalam {{ $tahunTerakhir }} tahun terakhir
                        @endif.
                    </div>
                @else
                    <div class="alert alert-info text-center" role="alert">
                        Silakan pilih program studi dan filter tahun untuk menampilkan data Text Book.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#prodi').select2({
                theme: 'bootstrap-5',
                placeholder: 'Ketik untuk mencari prodi...'
            });
            if ($('#myTableTextbook').length) {

                var table = $('#myTableTextbook').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json",
                        "thousands": ".",
                        "decimal": ","
                    },
                    "paging": true,
                    "lengthChange": true,
                    "searching": true,
                    "ordering": true,
                    "info": true,
                    "autoWidth": false,
                    "responsive": true,
                    "dom": '<"d-flex justify-content-between mb-3"lp>rt<"d-flex justify-content-between mt-3"ip>',
                    "columnDefs": [{
                        "searchable": false,
                        "orderable": false,
                        "targets": 0
                    }],
                    "order": [
                        [5, 'desc'],
                        [1, 'asc']
                    ],
                    "fnDrawCallback": function(oSettings) {
                        this.api().column(0, {
                            search: 'applied',
                            order: 'applied'
                        }).nodes().each(function(cell, i) {
                            cell.innerHTML = i + 1;
                        });
                    }
                });

                $('#searchInput').on('keyup change', function() {
                    table.search(this.value).draw();
                });

                function updateCustomInfo() {
                    var pageInfo = table.page.info();
                    let formatter = new Intl.NumberFormat('id-ID');
                    let formattedTotal = formatter.format(pageInfo
                        .recordsDisplay);
                    $('#customInfoJurnal').html(formattedTotal);
                }

                table.on('draw', updateCustomInfo);

                updateCustomInfo();
            }
        });
    </script>
@endpush
