@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

    <div class="container-fluid">
        {{-- <pre>
Current Route: {{ Route::currentRouteName() }}
Current Path: {{ request()->path() }}
</pre> --}}
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center mb-4">Statistik Perpustakaan Tahun {{ date('Y') }}</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-around">
                        <div>
                            <small class="text-muted">Total Jurnal</small>
                            <h4 class="card-title mt-2 mb-0">{{ $formatTotalJurnal }}</h4>
                        </div>
                        <div class="text-end mt-3">
                            <i class="fas fa-book fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <small class="text-muted">Total Judul Buku</small>
                            <h4 class="card-title mt-2 mb-0">{{ $formatTotalJudulBuku }}</h4>
                        </div>
                        <div class="text-end mt-3">
                            <i class="fas fa-book-open fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <small class="text-muted">Total Eksemplar</small>
                            <h4 class="card-title mt-2 mb-0">{{ $formatTotalEksemplar }}</h4>
                        </div>
                        <div class="text-end mt-3">
                            <i class="fas fa-copy fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <small class="text-muted">Jumlah Ebook</small>
                            <h4 class="card-title mt-2 mb-0">{{ $formatTotalEbooks }}</h4>
                        </div>
                        <div class="text-end mt-3">
                            <i class="fas fa-tablet-alt fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Total Kunjungan Offline -
                            {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY') }}</small>
                        <h3 class="card-title fw-bold mt-1 mb-0">{{ number_format($kunjunganHarian) }}</h3>
                    </div>
                    <div class="ms-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 50px; height: 50px;">
                            <i class="fas fa-door-open fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        @php
            // --- Data Kunjungan Website ---
            $kunjunganWebsite = [
                'Januari' => 0,
                'Februari' => 0,
                'Maret' => 0,
                'April' => 0,
                'Mei' => 0,
                'Juni' => 0,
                'Juli' => 0,
                'Agustus' => 0,
                'September' => 0,
                'Oktober' => 0,
                'November' => 0,
                'Desember' => 0,
            ];
            $bulanLengkap = [
                'Januari',
                'Februari',
                'Maret',
                'April',
                'Mei',
                'Juni',
                'Juli',
                'Agustus',
                'September',
                'Oktober',
                'November',
                'Desember',
            ];
            foreach ($bulanLengkap as $bln) {
                if (!isset($kunjunganWebsite[$bln])) {
                    $kunjunganWebsite[$bln] = 0;
                }
            }
            $totalKunjunganWebsite = array_sum($kunjunganWebsite);
            $tahunSekarang = date('Y');

            $websiteCol1 = array_slice($kunjunganWebsite, 0, 6, true); // Jan-Jun
            $websiteCol2 = array_slice($kunjunganWebsite, 6, 6, true); // Jul-Des

            // --- Data Kunjungan Repository ---
            $kunjunganRepository = [
                'Januari' => 0,
                'Februari' => 0,
                'Maret' => 0,
                'April' => 0,
                'Mei' => 0,
                'Juni' => 0,
                'Juli' => 0,
                'Agustus' => 0,
                'September' => 0,
                'Oktober' => 0,
                'November' => 0,
                'Desember' => 0,
            ];
            foreach ($bulanLengkap as $bln) {
                if (!isset($kunjunganRepository[$bln])) {
                    $kunjunganRepository[$bln] = 0;
                }
            }
            $totalKunjunganRepository = array_sum($kunjunganRepository);

            $repoCol1 = array_slice($kunjunganRepository, 0, 6, true);
            $repoCol2 = array_slice($kunjunganRepository, 6, 6, true);
        @endphp

        {{-- Card Kunjungan Website (Layout 2 Kolom) --}}
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-globe fa-lg me-2" style="color: #8914d7;"></i>
                    <h6 class="mb-0 fw-bold">Kunjungan Website <span
                            class="text-muted fw-normal">({{ $tahunSekarang }})</span></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <ul class="list-group list-group-flush">
                                @foreach ($websiteCol1 as $bulan => $jumlah)
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 list-group-item-action">
                                        <span class="text-body-emphasis">{{ $bulan }}</span>
                                        <span
                                            class="badge bg-primary rounded-pill px-2 py-1">{{ number_format($jumlah) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-6">
                            <ul class="list-group list-group-flush">
                                @foreach ($websiteCol2 as $bulan => $jumlah)
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 list-group-item-action">
                                        <span class="text-body-emphasis">{{ $bulan }}</span>
                                        <span
                                            class="badge bg-primary rounded-pill px-2 py-1">{{ number_format($jumlah) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Total Tahun Ini:</small>
                        <strong
                            class="d-block fs-5 text-body-emphasis">{{ number_format($totalKunjunganWebsite) }}</strong>
                    </div>
                    <a href="http://statcounter.com/p13060651/summary/?guest=1" target="_blank"
                        class="btn btn-outline-primary btn-sm px-3">
                        <i class="fas fa-external-link-alt me-1"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-database fa-lg me-2" style="color: #04833b;"></i>
                    <h6 class="mb-0 fw-bold">Kunjungan Repository <span
                            class="text-muted fw-normal">({{ $tahunSekarang }})</span></h6>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <ul class="list-group list-group-flush">
                                @foreach ($repoCol1 as $bulan => $jumlah)
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 list-group-item-action">
                                        <span class="text-body-emphasis">{{ $bulan }}</span>
                                        <span
                                            class="badge bg-success rounded-pill px-2 py-1">{{ number_format($jumlah) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-6">
                            <ul class="list-group list-group-flush">
                                @foreach ($repoCol2 as $bulan => $jumlah)
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 list-group-item-action">
                                        <span class="text-body-emphasis">{{ $bulan }}</span>
                                        <span
                                            class="badge bg-success rounded-pill px-2 py-1">{{ number_format($jumlah) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Total Tahun Ini:</small>
                        <strong
                            class="d-block fs-5 text-body-emphasis">{{ number_format($totalKunjunganRepository) }}</strong>
                    </div>
                    <a href="http://statcounter.com/p13060683/summary/?guest=1" target="_blank"
                        class="btn btn-outline-primary btn-sm px-3">
                        <i class="fas fa-external-link-alt me-1"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- <div class="container mt-2">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Grafik Data Kunjungan Tahun 2025</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="grafikKunjungan"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Grafik Data Sirkulasi Tahun 2025</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="grafikSirkulasi"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}
    {{-- <div class="container mt-2">
            <div class="row">
                <div class="col-md-7 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Buku Terlaris Dipinjam di Tahun 2025</h6>
                        </div>
                        <div class="card-body">
                            <table id="bukuTerlarisTable" class="table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Judul Buku</th>
                                        <th>Penulis</th>
                                        <th>Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Membuat aplikasi tutorial inte...</td>
                                        <td>MEMBUAT APLIKASI...</td>
                                        <td>66</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>Buku ajar ilmu kesehatan anak...</td>
                                        <td>GAVI. Allan</td>
                                        <td>22</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>Teruslah bodoh jangan pintar</td>
                                        <td>LIYE. Tere</td>
                                        <td>21</td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td>Malam pertama</td>
                                        <td>Tere Liye</td>
                                        <td>18</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td>Tentang kamu</td>
                                        <td>Tere Liye</td>
                                        <td>15</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Kunjungan Harian Fakultas</h6>
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center">
                            <canvas id="grafikFakultas" style="max-height: 400px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

@endsection
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('/css/dashboard.css') }}">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css">
@endpush

@push('scripts')
    {{-- SCRIPT --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <script src="https://kit.fontawesome.com/f96c87efe8.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.colVis.min.js"></script>

    <script>
        // Inisialisasi DataTables
        $(document).ready(function() {
            $('#bukuTerlarisTable').DataTable({
                dom: 'Bfrtip',
                buttons: [

                ],
                searching: false
            });
        });

        // Data untuk Grafik Pie Kunjungan Fakultas
        const dataFakultas = {
            labels: ['FKIP', 'EKONOMI', 'HUKUM', 'TEKNIK', 'GEOGRAFI', 'PSIKOLOGI', 'FAI', 'OTHER'],
            datasets: [{
                label: 'Jumlah Kunjungan',
                data: [100, 20, 15, 10, 5, 5, 5, 2], // Contoh data
                backgroundColor: [
                    '#FF6384', // Merah muda (contoh)
                    '#36A2EB', // Biru
                    '#FFCD56', // Kuning
                    '#4BC0C0', // Biru kehijauan
                    '#9966FF', // Ungu
                    '#FF9900', // Oranye gelap
                    '#C9CBCE', // Abu-abu
                    '#E7E9ED' // Abu-abu terang
                ],
                hoverOffset: 4
            }]
        };


        const configFakultas = {
            type: 'pie',
            data: dataFakultas,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right', // Posisi legend di kanan
                        labels: {
                            usePointStyle: true, // Gunakan gaya titik untuk item legend
                        }
                    },
                    title: {
                        display: false,
                    }
                }
            }
        };

        // const ctxFakultas = document.getElementById('grafikFakultas').getContext('2d');
        // new Chart(ctxFakultas, configFakultas);

        // const dataKunjungan = {
        //     labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October',
        //         'November', 'December'
        //     ],
        //     datasets: [{
        //         label: 'Jumlah Kunjungan',
        //         data: [6500, 11500, 11900, 0, 0, 0, 0, 0, 0, 0, 0, 0], // Contoh data sesuai gambar
        //         backgroundColor: 'rgba(0, 123, 255, 0.7)', // Biru
        //         borderColor: 'rgba(0, 123, 255, 1)',
        //         borderWidth: 1,
        //         borderRadius: 5,
        //     }]
        // };

        // const configKunjungan = {
        //     type: 'bar',
        //     data: dataKunjungan,
        //     options: {
        //         responsive: true,
        //         maintainAspectRatio: false,
        //         scales: {
        //             y: {
        //                 beginAtZero: true,
        //                 title: {
        //                     display: false,
        //                     text: 'Jumlah'
        //                 }
        //             },
        //             x: {
        //                 title: {
        //                     display: false,
        //                     text: 'Bulan'
        //                 },
        //                 grid: {
        //                     display: false
        //                 }
        //             }
        //         },
        //         plugins: {
        //             legend: {
        //                 display: false //
        //             },
        //             title: {
        //                 display: false
        //             }
        //         }
        //     }
        // };

        // // Inisialisasi Grafik Kunjungan
        // const ctxKunjungan = document.getElementById('grafikKunjungan').getContext('2d');
        // new Chart(ctxKunjungan, configKunjungan);


        // // Data untuk Grafik Sirkulasi
        // const dataSirkulasi = {
        //     labels: ['January', 'February', 'March'],
        //     datasets: [{
        //             label: 'Peminjaman Buku',
        //             data: [350, 850, 500],
        //             backgroundColor: 'rgba(0, 123, 255, 0.7)', // Biru
        //             borderColor: 'rgba(0, 123, 255, 1)',
        //             borderWidth: 1,
        //             borderRadius: 5,
        //         },
        //         {
        //             label: 'Perpanjangan Buku',
        //             data: [150, 200, 250],
        //             backgroundColor: 'rgba(40, 167, 69, 0.7)', // Hijau (success)
        //             borderColor: 'rgba(40, 167, 69, 1)',
        //             borderWidth: 1,
        //             borderRadius: 5,
        //         },
        //         {
        //             label: 'Pengembalian Buku',
        //             data: [980, 1050, 1200],
        //             backgroundColor: 'rgba(255, 193, 7, 0.7)', // Kuning (warning)
        //             borderColor: 'rgba(255, 193, 7, 1)',
        //             borderWidth: 1,
        //             borderRadius: 5,
        //         }
        //     ]
        // };

        // // Konfigurasi untuk Grafik Sirkulasi
        // const configSirkulasi = {
        //     type: 'bar',
        //     data: dataSirkulasi,
        //     options: {
        //         responsive: true,
        //         maintainAspectRatio: false,
        //         scales: {
        //             x: {
        //                 stacked: false,
        //                 grid: {
        //                     display: false
        //                 }
        //             },
        //             y: {
        //                 beginAtZero: true,
        //                 stacked: false
        //             }
        //         },
        //         plugins: {
        //             legend: {
        //                 display: true,
        //                 position: 'bottom',
        //                 labels: {
        //                     usePointStyle: true,
        //                 }
        //             },
        //             title: {
        //                 display: false
        //             }
        //         }
        //     }
        // };

        // // Inisialisasi Grafik Sirkulasi
        // const ctxSirkulasi = document.getElementById('grafikSirkulasi').getContext('2d');
        // new Chart(ctxSirkulasi, configSirkulasi);
    </script>
@endpush
