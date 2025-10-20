@extends('layouts.app')
@section('title', 'Statistik Koleksi E-Book')

@section('content')
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

        <style>
            html[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection {
                background-color: #2b3035;
                border: 1px solid #495057;
            }

            html[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
                color: #dee2e6;
            }

            html[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow b {
                border-color: #adb5bd transparent transparent transparent;
            }

            html[data-bs-theme="dark"] .select2-dropdown {
                background-color: #2b3035;
                border: 1px solid #495057;
            }

            html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
                background-color: #212529;
                color: #dee2e6;
                border: 1px solid #495057;
            }

            html[data-bs-theme="dark"] .select2-results__option {
                color: #dee2e6;
            }

            html[data-bs-theme="dark"] .select2-results__option--highlighted {
                background-color: #0d6efd;
                color: white;
            }
        </style>
    @endpush
    <div class="container">
        <h4>Statistik Koleksi E-Book @if ($prodi && $prodi !== 'all')
                - {{ $namaProdi }}
            @elseif ($prodi === 'all')
                - Semua Program Studi
            @endif
        </h4>
        <form method="GET" action="{{ route('koleksi.ebook') }}" class="row g-3 mb-4 align-items-end"
            id="filterFormEbook">
            <div class="col-md-4">
                <label for="prodi" class="form-label">Pilih Prodi</label>
                <select name="prodi" id="prodi" class="form-select">
                    @foreach ($listprodi as $p)
                        <option value="{{ $p->authorised_value }}" {{ $prodi == $p->authorised_value ? 'selected' : '' }}>
                            ({{ $p->authorised_value }})
                            - {{ $p->lib }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="tahun" class="form-label">Tahun Terbit</label>
                <select name="tahun" id="tahun" class="form-select">
                    <option value="all" {{ $tahunTerakhir == 'all' ? 'selected' : '' }}>Semua Tahun</option>
                    @for ($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}" {{ $tahunTerakhir == $i ? 'selected' : '' }}>
                            {{ $i }} Tahun Terakhir
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>

        <div class="mb-3">
            <input type="text" class="form-control" id="searchInput" placeholder="Cari judul, pengarang, penerbit...">
        </div>
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="alert alert-info py-2">
                            <i class="fas fa-book me-2"></i> Total Judul Ebook:
                            <span class="fw-bold">{{ number_format($totalJudul, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success py-2">
                            <i class="fas fa-copy me-2"></i> Total Eksemplar:
                            <span class="fw-bold">{{ number_format($totalEksemplar, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-danger py-2">
                            <i class="fas fa-database me-2"></i> Total Entri:
                            <span class="fw-bold" id="customInfoJurnal"></span>
                        </div>
                    </div>
                </div>
                @if ($prodi && $prodi !== 'initial' && $dataExists)
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Daftar Koleksi E-Book @if ($namaProdi && $prodi !== 'all')
                                ({{ $namaProdi }})
                            @elseif ($prodi === 'all')
                                (Semua Program Studi)
                            @endif
                            @if ($tahunTerakhir !== 'all')
                                - {{ $tahunTerakhir }} Tahun Terakhir
                            @endif
                        </h6>
                        <button type="submit" form="filterFormEbook" name="export_csv" value="1"
                            class="btn btn-success btn-sm"><i class="fas fa-file-csv"></i> Export CSV</button>
                    </div>
                @endif
                <div class="card-body">
                    @if ($prodi && $prodi !== 'initial' && $data->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped" id="myTableEbook">
                                <thead>
                                    <tr>
                                        <th>No</th> {{-- Tambahkan kolom No --}}
                                        <th>Judul</th>
                                        <th>Pengarang</th>
                                        <th>Penerbit</th>
                                        <th>Kota Terbit</th>
                                        <th>Tahun Terbit</th>
                                        <th>Eksemplar</th>
                                        {{-- <th>Lokasi</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $row)
                                        <tr>
                                            <td></td> {{-- Kolom ini akan diisi oleh DataTables --}}
                                            {{-- <td>{{ $row->Judul }}</td> --}}
                                            <td>{!! $row->Judul !!}</td>
                                            <td>{{ $row->Pengarang }}</td>
                                            <td>{{ $row->Penerbit }}</td>
                                            <td>{{ $row->Kota_Terbit }}</td>
                                            <td>{{ $row->Tahun_Terbit }}</td>
                                            <td>{{ $row->Eksemplar }}</td>
                                            {{-- <td>{{ $row->Lokasi }}</td> --}}
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
                            Silakan pilih program studi dan filter tahun untuk menampilkan data E-Book.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

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
                $('#prodi').on('select2:open', function() {
                    if ($('body').hasClass('dark-mode')) {
                        setTimeout(function() {
                            $('.select2-dropdown').addClass('select2-dark-theme');
                        }, 0);
                    }
                });
                var table = $('#myTableEbook').DataTable({
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
                        }, // Kolom 'No' (indeks 0) tidak bisa diurutkan
                        {
                            "targets": 0, // Target kolom 'No'
                            "render": function(data, type, row, meta) {
                                return meta.row + 1; // Menomori baris secara otomatis oleh DataTables
                            }
                        }
                    ],
                    "lengthMenu": [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "Semua"]
                    ],
                    "pageLength": 10,
                    // "dom": 'lrtip'
                    // "dom": '<"top"lp>t<"bottom"ip>'
                    "dom": '<"d-flex justify-content-between mb-3"lp>t<"d-flex justify-content-between mt-3"ip>',
                    // "infoCallback": function(settings, start, end, max, total, pre) {
                    //     // Gunakan Intl.NumberFormat untuk memformat angka total
                    //     let formatter = new Intl.NumberFormat(
                    //         'id-ID'); // 'id-ID' untuk format Indonesia (titik sebagai pemisah ribuan)
                    //     let formattedTotal = formatter.format(total);
                    //     let formattedStart = formatter.format(start);
                    //     let formattedEnd = formatter.format(end);

                    //     // Sesuaikan string sesuai dengan format DataTables default (atau sesuaikan jika kamu punya teks sendiri)
                    //     return `Menampilkan ${formattedStart} sampai ${formattedEnd} dari ${formattedTotal} entri`;
                    // },
                    // "dom": 'lrtip'
                });
                // Fungsi untuk update info paginasi ke div custom
                function updateCustomInfo() {
                    var pageInfo = table.page.info();
                    let formatter = new Intl.NumberFormat('id-ID');
                    let formattedTotal = formatter.format(pageInfo.recordsTotal);
                    let infoText = `${formattedTotal}`;
                    $('#customInfoJurnal').html(infoText);
                }
                // Update info saat tabel di-draw
                table.on('draw', updateCustomInfo);
                // Inisialisasi info pertama kali
                updateCustomInfo();

                $('#searchInput').on('keyup change', function() {
                    table.search(this.value).draw();
                });
            });
        </script>
    @endpush
@endsection
