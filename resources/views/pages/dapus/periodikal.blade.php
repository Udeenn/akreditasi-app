@extends('layouts.app')
@section('title', 'Statistik Koleksi Majalah')

@section('content')
    <div class="container">
        <h4>Statistik Koleksi Majalah @if ($prodi && $prodi !== 'all')
                - {{ $namaProdi }}
            @elseif ($prodi === 'all')
                - Semua Program Studi
            @endif
        </h4>
        <form method="GET" action="{{ route('koleksi.periodikal') }}" class="row g-3 mb-4 align-items-end"
            id="filterFormPeriodikal">
            <div class="col-md-4">
                <label for="prodi" class="form-label">Pilih Prodi</label>
                <select name="prodi" id="prodi" class="form-select">
                    @foreach ($listprodi as $p)
                        <option value="{{ $p->authorised_value }}" {{ $prodi == $p->authorised_value ? 'selected' : '' }}>
                            {{ $p->lib }}
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

        {{-- Input Search Langsung untuk DataTables --}}
        <div class="mb-3">
            <input type="text" class="form-control" id="searchInput" placeholder="Cari jenis, judul, nomor...">
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="alert alert-info py-2">
                            <i class="fas fa-book me-2"></i> Total Judul Majalah:
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
                            Daftar Koleksi Majalah @if ($namaProdi && $prodi !== 'all')
                                ({{ $namaProdi }})
                            @elseif ($prodi === 'all')
                                (Semua Program Studi)
                            @endif
                            @if ($tahunTerakhir !== 'all')
                                - {{ $tahunTerakhir }} Tahun Terakhir
                            @endif
                        </h6>
                        <button type="submit" form="filterFormPeriodikal" name="export_csv" value="1"
                            class="btn btn-success btn-sm"><i class="fas fa-file-csv"></i> Export CSV</button>
                    </div>
                @endif
                <div class="card-body">
                    @if ($prodi && $prodi !== 'initial' && $data->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped" id="myTablePeriodikal">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        {{-- <th>Jenis</th> --}}
                                        <th>Judul</th>
                                        <th>Penerbit</th>
                                        <th>Nomor</th>
                                        {{-- <th>Kelas</th> --}}
                                        {{-- <th>Issue</th> --}}
                                        <th>Eksemplar</th>
                                        {{-- <th>Tahun Terbit</th> --}}
                                        <th>Lokasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $row)
                                        <tr>
                                            <td></td>
                                            {{-- <td>{{ $row->Jenis }}</td> --}}
                                            <td>{{ $row->Judul }}</td>
                                            <td>{{ $row->Penerbit }}</td>
                                            <td>{{ $row->Nomor }}</td>
                                            {{-- <td>{{ $row->Kelas }}</td> --}}
                                            {{-- <td>{{ $row->Issue }}</td> --}}
                                            <td>{{ $row->Eksemplar }}</td>
                                            {{-- <td>{{ $row->Tahun_Terbit }}</td> --}}
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
    </div>

    @push('scripts')
        {{-- Memuat DataTables CSS dan JS --}}
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
        <script>
            $(document).ready(function() {
                // Inisialisasi DataTables jika tabel ada
                var table = $('#myTablePeriodikal').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json" // Bahasa Indonesia
                        // Hapus "decimal" dan "thousands" di sini
                    },
                    "paging": true, // Aktifkan paginasi
                    "lengthChange": true, // Aktifkan pilihan jumlah item per halaman
                    "searching": true, // Aktifkan fitur pencarian bawaan DataTables
                    "ordering": true, // Aktifkan pengurutan kolom
                    "info": true, // Tampilkan informasi "Showing X to Y of Z entries"
                    "autoWidth": false, // Nonaktifkan penyesuaian lebar kolom otomatis
                    "columnDefs": [{
                            "orderable": false,
                            "targets": [0]
                        }, // Kolom 'No' (indeks 0) tidak bisa diurutkan
                        {
                            "targets": 0, // Kolom 'No'
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
                    "dom": '<"d-flex justify-content-between mb-3"lp>t<"d-flex justify-content-between mt-3"ip>',
                    // Tambahkan infoCallback di sini untuk format angka
                    // "infoCallback": function(settings, start, end, max, total, pre) {
                    //     let formatter = new Intl.NumberFormat(
                    //         'id-ID'); // 'id-ID' untuk format Indonesia (titik sebagai pemisah ribuan)
                    //     let formattedTotal = formatter.format(total);
                    //     let formattedStart = formatter.format(start);
                    //     let formattedEnd = formatter.format(end);

                    //     return `Menampilkan ${formattedStart} sampai ${formattedEnd} dari ${formattedTotal} entri`;
                    // },
                    // "dom": 'lrtip' // Menghilangkan input search bawaan DataTables
                });

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
                // Sinkronkan input search kustom dengan pencarian DataTables
                $('#searchInput').on('keyup change', function() {
                    table.search(this.value).draw();
                });
            });
        </script>
    @endpush
@endsection
