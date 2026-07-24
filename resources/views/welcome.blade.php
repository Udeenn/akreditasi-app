<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('/css/dashboard.css') }}">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css">
    <title>ACCREDITATION SUPPORT</title>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark p-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="{{ asset('/img/logo.png') }}" alt="Nama Perusahaan Anda" height="50">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <div class="gap-2 d-flex ms-auto ">
                    @if (Route::has('login'))
                        <nav class="-mx-3 flex flex-1 justify-end">
                            @auth
                                <a href="{{ route('/dashboard') }}">
                                    <button class="btn btn-outline-primary">Login</button>
                                </a>
                            @else
                                <a href="{{ route('login') }}">
                                    <button class="btn btn-outline-primary">Login</button>
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}">
                                        <button class="btn btn-outline-primary">Register</button>
                                    </a>
                                @endif
                            @endauth
                        </nav>
                    @endif
                </div>
            </div>
        </div>
    </nav>


    <div class="hero-section">
        <div class="hero-content">
            <h2>Selamat Datang di Perpustakaan Kami!</h2>
            <p class="lead">
                Nikmati koleksi buku, jurnal, dan sumber daya digital yang luas untuk mendukung kebutuhan belajar dan
                penelitian Anda.
                Lingkungan yang nyaman dan tenang kami dirancang untuk membantu Anda fokus dan produktif.
            </p>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center mb-4">Statistik Perpustakaan Tahun <?php echo date('Y'); ?></h2>
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <small class="text-muted">Total Jurnal</small>
                                <h4 class="card-title mt-2 mb-0">{{ $totalJurnal }}</h4>
                            </div>
                            <div class="text-end mt-3">
                                <i class="bi bi-journal-text fs-1 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <small class="text-muted">Total Judul Buku</small>
                                <h4 class="card-title mt-2 mb-0">{{ $totalBuku }}</h4>
                            </div>
                            <div class="text-end mt-3">
                                <i class="bi bi-book-fill fs-1 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <small class="text-muted">Total Eksemplar</small>
                                <h4 class="card-title mt-2 mb-0">{{ $totalEksemplar }}</h4>
                            </div>
                            <div class="text-end mt-3">
                                <i class="bi bi-files fs-1 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <small class="text-muted">Anggota Aktif</small>
                                <h4 class="card-title mt-2 mb-0">{{ $anggotaAktif }}</h4>
                            </div>
                            <div class="text-end mt-3">
                                <i class="bi bi-person-fill fs-1" style="color: #FFD43B;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mt-4">
            <div class="row">
                <div class="col-md-6 col-sm-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <small class="text-muted">Total Kunjungan : <?php echo date('l, d F Y'); ?></small>
                                <h4 class="card-title mt-2 mb-0">{{ $totalKunjungan }}</h4>
                            </div>
                            <div class="text-end mt-3">
                                <i class="bi bi-door-open-fill fs-1 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <small class="text-muted">Total Kunjungan Website : <?php echo date('l, d F Y'); ?></small>
                                <h4 class="card-title mt-2 mb-0"><a
                                        href="http://statcounter.com/p13060651/summary/?guest=1" target="_blank">Klik
                                        Disini</a></h4>
                            </div>
                            <div class="text-end mt-3">
                                <i class="bi bi-globe fs-1" style="color: #8914d7;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mt-2">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Grafik Data Kunjungan Tahun 2025</h6>
                        </div>
                        <div class="card-body">
                            <div id="grafikKunjungan" style="min-height: 250px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Grafik Data Sirkulasi Tahun 2025</h6>
                        </div>
                        <div class="card-body">
                            <div id="grafikSirkulasi" style="min-height: 250px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mt-2">
            <div class="row">
                <div class="col-md-7 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Buku Terlaris Dipinjam di Tahun 2025</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="bukuTerlarisTable" class="table table-striped table-bordered"
                                style="width:100%">
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
                </div>

                <div class="col-md-5 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Kunjungan Harian Fakultas</h6>
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center">
                            <div id="grafikFakultas" style="min-height: 350px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SCRIPT --}}
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
        </script>
        <script src="https://kit.fontawesome.com/f96c87efe8.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                    },
                    dom: 'Bfrtip', // Menentukan posisi elemen kontrol: Buttons, filter, table, info, paginate
                    buttons: [
                        // Aktifkan tombol export dan column visibility
                    ],
                    searching: false
                    // Contoh data diambil langsung dari HTML, jika Anda memiliki data dari server, gunakan opsi `ajax`
                });
            });

            // Konfigurasi Grafik Pie Kunjungan Fakultas (ApexCharts)
            var optionsFakultas = {
                series: [35, 15, 12, 10, 8, 5, 10, 5],
                chart: {
                    type: 'pie',
                    height: 350,
                    fontFamily: 'Inter, Helvetica, Arial, sans-serif',
                },
                labels: ['FKIP', 'EKONOMI', 'HUKUM', 'TEKNIK', 'GEOGRAFI', 'PSI KOLOGI', 'FAI', 'OTHER'],
                colors: ['#FF6384', '#36A2EB', '#FFCD56', '#4BC0C0', '#9966FF', '#FF9900', '#C9CBCE', '#E7E9ED'],
                legend: {
                    position: 'right'
                },
                dataLabels: {
                    enabled: false
                }
            };
            var chartFakultas = new ApexCharts(document.querySelector("#grafikFakultas"), optionsFakultas);
            chartFakultas.render();

            // Konfigurasi Grafik Kunjungan (ApexCharts)
            var optionsKunjungan = {
                series: [{
                    name: 'Jumlah Kunjungan',
                    data: [6500, 11500, 11900, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                }],
                chart: {
                    type: 'bar',
                    height: 250,
                    fontFamily: 'Inter, Helvetica, Arial, sans-serif',
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '50%',
                    }
                },
                colors: ['#0d6efd'],
                xaxis: {
                    categories: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                    labels: { style: { colors: '#6c757d', fontSize: '11px' } }
                },
                yaxis: {
                    labels: { style: { colors: '#6c757d', fontSize: '11px' } }
                },
                grid: {
                    borderColor: '#f0f2f5',
                    strokeDashArray: 4
                }
            };
            var chartKunjungan = new ApexCharts(document.querySelector("#grafikKunjungan"), optionsKunjungan);
            chartKunjungan.render();

            // Konfigurasi Grafik Sirkulasi (ApexCharts)
            var optionsSirkulasi = {
                series: [{
                    name: 'Peminjaman Buku',
                    data: [350, 850, 500]
                }, {
                    name: 'Perpanjangan Buku',
                    data: [150, 200, 250]
                }, {
                    name: 'Pengembalian Buku',
                    data: [980, 1050, 1200]
                }],
                chart: {
                    type: 'bar',
                    height: 250,
                    fontFamily: 'Inter, Helvetica, Arial, sans-serif',
                    toolbar: { show: false },
                    stacked: false,
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '50%',
                    }
                },
                colors: ['#0d6efd', '#28a745', '#ffc107'],
                xaxis: {
                    categories: ['January', 'February', 'March'],
                    labels: { style: { colors: '#6c757d', fontSize: '11px' } }
                },
                yaxis: {
                    labels: { style: { colors: '#6c757d', fontSize: '11px' } }
                },
                grid: {
                    borderColor: '#f0f2f5',
                    strokeDashArray: 4
                },
                legend: {
                    position: 'bottom'
                }
            };
            var chartSirkulasi = new ApexCharts(document.querySelector("#grafikSirkulasi"), optionsSirkulasi);
            chartSirkulasi.render();
        </script>
        {{-- SCRIPT --}}
</body>

</html>
