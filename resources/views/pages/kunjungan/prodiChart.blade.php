@extends('layouts.app')

@section('title', 'Grafik Kunjungan Prodi')

@push('styles')
    <style>
        /* --- MODERN DASHBOARD STYLING (Sama dengan Laporan Kunjungan) --- */
        :root {
            --primary-soft: rgba(13, 110, 253, 0.1);
            --success-soft: rgba(25, 135, 84, 0.1);
            --warning-soft: rgba(255, 193, 7, 0.1);
            --info-soft: rgba(13, 202, 240, 0.1);
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 12px !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            overflow: hidden !important;
            /* Menjaga rounded corner */
        }

        /* Header Putih di Light Mode */
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--bs-body-color);
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }

        /* Table Styling */
        .table thead th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        /* --- DARK MODE ADAPTATION --- */
        body.dark-mode .card {
            background-color: #1e1e2d;
            border: 1px solid #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .card-header {
            background-color: #1e1e2d !important;
            border-bottom-color: #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .text-muted {
            color: #a1a5b7 !important;
        }

        body.dark-mode .table {
            color: #ffffff;
            border-color: #2b2b40;
        }

        body.dark-mode .table thead th {
            background-color: #2b2b40;
            color: #ffffff;
            border-bottom-color: #3f4254;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #1b1b29;
            border-color: #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .text-body {
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">

        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div
                        class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0">
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-chart-bar me-2"></i>Grafik Kunjungan Program Studi
                            </h3>
                            <p class="mb-0 opacity-75">
                                @if ($selectedProdi && $selectedTahunAwal && $selectedTahunAkhir && $namaProdi)
                                    Data ditampilkan untuk: <strong>{{ $namaProdi }}</strong> ({{ $selectedTahunAwal }} -
                                    {{ $selectedTahunAkhir }})
                                @else
                                    Analisis tren kunjungan berdasarkan Program Studi spesifik.
                                @endif
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-graduation-cap fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FILTER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-3 pb-0">
                        <h6 class="fw-bold text-primary"><i class="fas fa-filter me-1"></i> Filter Data</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('kunjungan.prodiChart') }}" class="row g-3 align-items-end">

                            <div class="col-12 col-md-4">
                                <label for="prodi" class="form-label small text-muted fw-bold">Pilih Prodi</label>
                                <select name="prodi" id="prodi" class="form-select border-0 bg-light">
                                    <option value="">-- Pilih Program Studi --</option>
                                    <option value="all" {{ $selectedProdi == 'all' ? 'selected' : '' }}>-- Semua Prodi --
                                    </option>
                                    @foreach ($listProdi as $kode => $nama)
                                        <option value="{{ $kode }}" {{ $selectedProdi == $kode ? 'selected' : '' }}>
                                            ({{ $kode }})
                                            -- {{ $nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6 col-md-3">
                                <label for="tahun_awal" class="form-label small text-muted fw-bold">Tahun Awal</label>
                                <input type="number" name="tahun_awal" id="tahun_awal"
                                    class="form-control border-0 bg-light" value="{{ $selectedTahunAwal }}"
                                    placeholder="{{ date('Y') }}">
                            </div>

                            <div class="col-6 col-md-3">
                                <label for="tahun_akhir" class="form-label small text-muted fw-bold">Tahun Akhir</label>
                                <input type="number" name="tahun_akhir" id="tahun_akhir"
                                    class="form-control border-0 bg-light" value="{{ $selectedTahunAkhir }}"
                                    placeholder="{{ date('Y') }}">
                            </div>

                            <div class="col-12 col-md-2">
                                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                                    <i class="fas fa-search me-1"></i> Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if ($selectedProdi && $selectedTahunAwal && $selectedTahunAkhir && $data->isNotEmpty())

            {{-- 3. CHART SECTION --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0 text-body">Visualisasi Data</h5>
                            {{-- Tombol Save dipindah ke Header agar rapi --}}
                            <button id="saveChart" class="btn btn-sm btn-success fw-bold shadow-sm">
                                <i class="fas fa-download me-1"></i> Simpan Grafik (PDF/IMG)
                            </button>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div style="position: relative; height: 400px; width: 100%;">
                                <canvas id="chartKunjungan"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. TABLE SECTION --}}
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div
                            class="card-header border-bottom-0 py-3 px-4 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0 text-primary">
                                <i class="fas fa-table me-2"></i>Rincian Kunjungan
                            </h6>
                            {{-- Badge Total Kunjungan dipindah ke sini agar elegan --}}
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                                Total: {{ number_format($totalKeseluruhanKunjungan) }}
                            </span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="kunjunganProdiTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="py-3 px-4 border-bottom-0">Bulan Tahun</th>
                                            <th class="py-3 px-4 border-bottom-0">Program Studi</th>
                                            <th class="py-3 px-4 border-bottom-0 text-center">Jumlah Kunjungan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data as $row)
                                            <tr>
                                                <td class="px-4 fw-medium text-body">
                                                    <i class="far fa-calendar-alt me-2 text-muted"></i>
                                                    {{ \Carbon\Carbon::createFromFormat('Ym', $row->tahun_bulan)->format('F Y') }}
                                                </td>
                                                <td class="px-4 fw-bold text-muted">
                                                    {{ $row->nama_prodi }}
                                                </td>
                                                <td class="px-4 text-center">
                                                    <span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill">
                                                        {{ number_format($row->jumlah_kunjungan) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 py-3">
                            <div class="d-flex justify-content-center">
                                {{ $data->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @elseif ($selectedProdi && $selectedTahunAwal && $selectedTahunAkhir && $data->isEmpty())
            {{-- EMPTY STATE --}}
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="text-center p-5 border-0 bg-light rounded-4">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3 opacity-50"></i>
                        <h5 class="fw-bold text-body">Data Tidak Ditemukan</h5>
                        <p class="text-muted">
                            Tidak ada data kunjungan untuk <strong>{{ $namaProdi }}</strong>
                            antara tahun {{ $selectedTahunAwal }} - {{ $selectedTahunAkhir }}.
                        </p>
                    </div>
                </div>
            </div>
        @else
            {{-- INITIAL STATE --}}
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm text-center p-5 rounded-4">
                        <div class="card-body">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-4 mb-3">
                                <i class="fas fa-search-plus fa-3x text-primary"></i>
                            </div>
                            <h4 class="fw-bold text-body">Mulai Analisis</h4>
                            <p class="text-muted mb-0">Silakan pilih <strong>Program Studi</strong> dan <strong>Rentang
                                    Tahun</strong> di atas untuk menampilkan grafik dan tabel data.</p>
                        </div>
                    </div>
                </div>
            </div>

        @endif
    </div>

    {{-- SCRIPT ASLI (TIDAK DIUBAH SAMA SEKALI AGAR FUNGSIONALITAS AMAN) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @if ($selectedProdi && $selectedTahunAwal && $selectedTahunAkhir && $data->isNotEmpty())
                const chartCanvas = document.getElementById('chartKunjungan');
                const chart = chartCanvas.getContext('2d');

                const dataChart = new Chart(chart, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode(
                            $data->pluck('tahun_bulan')->map(fn($v) => \Carbon\Carbon::createFromFormat('Ym', $v)->format('M Y')),
                        ) !!},
                        datasets: [{
                            label: 'Jumlah Kunjungan {{ $namaProdi }}',
                            data: {!! json_encode($data->pluck('jumlah_kunjungan')) !!},
                            backgroundColor: 'rgba(13, 110, 253, 0.6)', // Warna Biru Bootstrap
                            borderColor: '#0d6efd',
                            borderWidth: 1,
                            borderRadius: 4, // Rounded bars agar modern
                            barPercentage: 0.6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Penting agar chart mengikuti container
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                backgroundColor: 'rgba(33, 37, 41, 0.9)',
                                padding: 10,
                                titleFont: {
                                    size: 13
                                },
                                bodyFont: {
                                    size: 13
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f0f2f5',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: '#6c757d'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: '#6c757d'
                                }
                            }
                        }
                    }
                });

                // Save Chart (PDF/PNG) logic - TETAP BERFUNGSI
                document.getElementById("saveChart").addEventListener("click", function() {
                    const newCanvas = document.createElement("canvas");
                    newCanvas.width = chartCanvas.width;
                    newCanvas.height = chartCanvas.height;

                    const context = newCanvas.getContext("2d");
                    context.fillStyle = "#ffffff";
                    context.fillRect(0, 0, newCanvas.width, newCanvas.height);
                    context.drawImage(chartCanvas, 0, 0);
                    const imageURL = newCanvas.toDataURL("image/png");

                    const downloadLink = document.createElement("a");
                    downloadLink.href = imageURL;
                    downloadLink.download = "chart_kunjungan_{{ Str::slug($namaProdi) }}.png";
                    downloadLink.click();
                });
            @endif
        });
    </script>
@endsection
