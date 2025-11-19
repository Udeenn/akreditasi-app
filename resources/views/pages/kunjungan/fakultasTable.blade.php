@extends('layouts.app')
@section('title', 'Statistik Kunjungan Per Fakultas')

@section('content')
    <div class="container">
        {{-- Header --}}
        <div class="card bg-white shadow-sm mb-4 border-0 rounded-3">
            <div class="card-body p-4">
                <h4 class="mb-1 text-primary fw-bold"><i class="fas fa-university me-2"></i>Statistik Kunjungan Per Fakultas
                </h4>
                <p class="text-muted mb-0">Menampilkan rincian kunjungan Prodi berdasarkan Fakultas yang dipilih.</p>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Data</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('kunjungan.fakultasTable') }}" id="filterForm"
                    class="row g-2 align-items-end">

                    <input type="hidden" name="search" id="hiddenSearchInput" value="{{ request('search') }}">

                    {{-- 1. Filter Tipe --}}
                    <div class="col-lg-2 col-md-3 col-6">
                        <label for="filter_type" class="form-label fw-bold small">Tampilkan Data:</label>
                        <select name="filter_type" id="filter_type" class="form-select form-select-sm">
                            <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>Harian
                            </option>
                            <option value="yearly" {{ ($filterType ?? '') == 'yearly' ? 'selected' : '' }}>Bulanan</option>
                        </select>
                    </div>

                    {{-- 2. Filter Fakultas --}}
                    <div class="col-lg-4 col-md-5 col-6">
                        <label for="fakultas" class="form-label fw-bold small">Pilih Fakultas:</label>
                        <select name="fakultas" id="fakultas" class="form-select form-select-sm">
                            <option value="semua" {{ request('fakultas') == 'semua' ? 'selected' : '' }}>(Semua Fakultas)
                            </option>
                            @foreach ($listFakultas as $namaFakultas)
                                <option value="{{ $namaFakultas }}"
                                    {{ request('fakultas') == $namaFakultas ? 'selected' : '' }}>
                                    {{ $namaFakultas }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. Filter Tanggal/Tahun --}}
                    <div class="col-lg-2 col-md-2 col-6 daily-filter"
                        style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                        <label class="form-label fw-bold small">Tgl Awal:</label>
                        <input type="date" name="tanggal_awal" class="form-control form-control-sm"
                            value="{{ $tanggalAwal }}">
                    </div>
                    <div class="col-lg-2 col-md-2 col-6 yearly-filter"
                        style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                        <label class="form-label fw-bold small">Thn Awal:</label>
                        <input type="number" name="tahun_awal" class="form-control form-control-sm"
                            value="{{ $tahunAwal }}">
                    </div>

                    <div class="col-lg-2 col-md-2 col-6 daily-filter"
                        style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                        <label class="form-label fw-bold small">Tgl Akhir:</label>
                        <input type="date" name="tanggal_akhir" class="form-control form-control-sm"
                            value="{{ $tanggalAkhir }}">
                    </div>
                    <div class="col-lg-2 col-md-2 col-6 yearly-filter"
                        style="{{ ($filterType ?? '') == 'yearly' ? '' : 'display: none;' }}">
                        <label class="form-label fw-bold small">Thn Akhir:</label>
                        <input type="number" name="tahun_akhir" class="form-control form-control-sm"
                            value="{{ $tahunAkhir }}">
                    </div>

                    {{-- 4. Tombol --}}
                    <div class="col-lg-auto col-md-12 col-12">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>
                            Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($hasFilter)
            {{-- Chart Section --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0">Grafik Kunjungan</h5>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="kunjunganChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Table Section --}}
            <div class="card shadow-sm mb-4 border-0">
                <div
                    class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Tabel Data</h5>
                    <div class="alert alert-info py-1 px-3 mb-0 fw-bold" id="totalBadge">
                        Total: {{ number_format($totalKeseluruhanKunjungan ?? 0, 0, ',', '.') }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="yajraTable" class="table table-hover table-striped align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="30%">Tanggal / Periode</th>
                                    <th width="30%">Prodi / Kategori</th>
                                    <th width="35%" class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-light border text-center p-5">
                <i class="fas fa-arrow-up fa-2x text-muted mb-3"></i>
                <h5>Silakan Filter Data</h5>
                <p class="text-muted">Pilih Fakultas dan Periode di atas untuk menampilkan data.</p>
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        $(document).ready(function() {
            // 1. INIT YAJRA DATATABLE
            var table = $('#yajraTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                ordering: false,
                ajax: {
                    url: "{{ route('kunjungan.fakultasTable') }}",
                    data: function(d) {
                        d.filter_type = $('#filter_type').val();
                        d.fakultas = $('#fakultas').val();
                        d.tanggal_awal = $('input[name="tanggal_awal"]').val();
                        d.tanggal_akhir = $('input[name="tanggal_akhir"]').val();
                        d.tahun_awal = $('input[name="tahun_awal"]').val();
                        d.tahun_akhir = $('input[name="tahun_akhir"]').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tanggal_kunjungan',
                        name: 'tanggal_kunjungan'
                    },
                    {
                        data: 'nama_prodi',
                        name: 'nama_prodi'
                    },
                    {
                        data: 'jumlah_kunjungan_harian',
                        name: 'jumlah_kunjungan_harian',
                        className: 'text-end'
                    }
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
                },

                drawCallback: function(settings) {
                    var api = this.api();
                    var json = api.ajax.json();

                    if (json && json.recordsTotalFiltered) {
                        // Update teks di badge Total
                        $('#totalBadge').html('Total: ' + json.recordsTotalFiltered);
                    }
                }
            });

            @if (isset($chartData) && count($chartData) > 0)
                const ctx = document.getElementById('kunjunganChart');
                if (ctx) {
                    const chartData = @json($chartData);
                    const labels = chartData.map(item => item.label);
                    const dataValues = chartData.map(item => item.total_kunjungan);

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Total Kunjungan',
                                data: dataValues,
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }
            @endif

            // Toggle Filter UI
            function toggleFilters() {
                const val = $('#filter_type').val();
                if (val === 'yearly') {
                    $('.daily-filter').hide();
                    $('.yearly-filter').show();
                } else {
                    $('.daily-filter').show();
                    $('.yearly-filter').hide();
                }
            }
            $('#filter_type').on('change', toggleFilters);
            toggleFilters();
        });
    </script>
@endpush
