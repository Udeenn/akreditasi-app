@extends('layouts.app')
@section('title', 'Statistik Koleksi Majalah')

@section('content')
    <div class="container-fluid px-3 px-md-4 pt-2 pb-4">

        <x-breadcrumb title="Statistik Koleksi Majalah" icon="fas fa-newspaper">
            <x-slot name="subtitle">
                Data koleksi majalah (periodikal)
                                @if ($prodi && $prodi !== 'all')
                                    — {{ $namaProdi }}
                                @elseif ($prodi === 'all')
                                    — Semua Program Studi
                                @endif
            </x-slot>
        </x-breadcrumb>

        {{-- 2. FILTER SECTION --}}
        @include('pages.koleksi.partials.filter-card', ['route' => 'koleksi.periodikal', 'formId' => 'filterFormPeriodikal', 'showCodeInLabel' => false])

        {{-- 3. STAT CARDS --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="icon-box bg-info-soft text-info me-3">
                            <i class="fas fa-book"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Total Judul Majalah</div>
                            <div class="fs-4 fw-bold">{{ number_format($totalJudul, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
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
            <div class="col-sm-4">
                <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="icon-box bg-danger-soft text-danger me-3">
                            <i class="fas fa-database"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Total Entri</div>
                            <div class="fs-4 fw-bold" id="customInfoJurnal">-</div>
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
                        Daftar Koleksi Majalah
                        @if ($namaProdi && $prodi !== 'all')
                            ({{ $namaProdi }})
                        @elseif ($prodi === 'all')
                            (Semua Program Studi)
                        @endif
                        @if ($tahunTerakhir !== 'all')
                            - {{ $tahunTerakhir }} Tahun Terakhir
                        @endif
                    </h6>
                    <button type="submit" form="filterFormPeriodikal" name="export_csv" value="1"
                        class="btn btn-success shadow-sm px-3"><i class="fas fa-file-csv me-2"></i> Export CSV</button>
                </div>
            @endif
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari jenis, judul, nomor...">
                </div>
                @if ($prodi && $prodi !== 'initial' && $data->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 unified-table" id="myTablePeriodikal">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Penerbit</th>
                                    <th>Nomor</th>
                                    <th>Eksemplar</th>
                                    <th>Lokasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $row)
                                    <tr>
                                        <td></td>
                                        <td>{{ $row->Judul }}</td>
                                        <td>{{ $row->Penerbit }}</td>
                                        <td>{{ $row->Nomor }}</td>
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
                        Silakan pilih program studi dan filter tahun untuk menampilkan data majalah.
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
        <script>
            $(document).ready(function() {
                var table = $('#myTablePeriodikal').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                    },
                    "paging": true,
                    "lengthChange": true,
                    "searching": true,
                    "ordering": true,
                    "info": true,
                    "autoWidth": false,
                    "columnDefs": [{
                            "orderable": false,
                            "targets": [0]
                        },
                        {
                            "targets": 0,
                            "render": function(data, type, row, meta) {
                                return meta.row + 1;
                            }
                        }
                    ],
                    "lengthMenu": [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "Semua"]
                    ],
                    "pageLength": 10,
                    "dom": '<"d-flex justify-content-between mb-3"lp>t<"d-flex justify-content-between mt-3"ip>',
                });

                function updateCustomInfo() {
                    var pageInfo = table.page.info();
                    if (!pageInfo) return;
                    let formatter = new Intl.NumberFormat('id-ID');
                    let formattedTotal = formatter.format(pageInfo.recordsTotal);
                    $('#customInfoJurnal').html(formattedTotal);
                }
                table.on('draw', updateCustomInfo);
                updateCustomInfo();
                $('#searchInput').on('keyup change', function() {
                    table.search(this.value).draw();
                });
            });
        </script>
    @endpush
@endsection

