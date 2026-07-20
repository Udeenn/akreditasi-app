@extends('layouts.app')

@section('title', 'Tren Pertambahan Koleksi')

@section('content')
<div class="container-fluid px-4 py-4">

    <x-breadcrumb title="Tren Pertambahan Koleksi" icon="fas fa-chart-line">
        <x-slot name="subtitle">
            Analisis data historis penambahan koleksi (Judul & Eksemplar) dari tahun ke tahun.
        </x-slot>
    </x-breadcrumb>

    {{-- Filter Data --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card unified-card border-0 shadow-sm">
                <div class="card-header border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-filter text-primary me-2"></i> Filter Rentang Tahun</h5>
                </div>
                <div class="card-body px-4 pb-4 pt-3">
                    <form method="GET" action="{{ route('koleksi.tren_pertambahan') }}" id="filterForm" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small text-muted text-uppercase">Dari Tahun</label>
                            <select name="start_year" class="form-select">
                                @for ($y = date('Y'); $y >= 2000; $y--)
                                    <option value="{{ $y }}" {{ $startYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small text-muted text-uppercase">Sampai Tahun</label>
                            <select name="end_year" class="form-select">
                                @for ($y = date('Y'); $y >= 2000; $y--)
                                    <option value="{{ $y }}" {{ $endYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold">
                                <i class="fas fa-search me-1"></i> Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(count($data) > 0)
    <div class="row mb-4">
        {{-- Chart Section --}}
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card unified-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-chart-bar text-primary me-2"></i> Grafik Penambahan</h5>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height:400px; width:100%">
                        <canvas id="trenChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="col-lg-4">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card unified-card border-0 shadow-sm overflow-hidden bg-primary text-white">
                        <div class="card-body p-4 position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Penambahan Judul</h6>
                                    <h2 class="mb-0 fw-bold display-6">{{ number_format($data->sum('total_titles'), 0, ',', '.') }}</h2>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="fas fa-book-open fs-2"></i>
                                </div>
                            </div>
                            <small class="text-white-50">Dalam rentang tahun terpilih</small>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card unified-card border-0 shadow-sm overflow-hidden bg-info text-white">
                        <div class="card-body p-4 position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Penambahan Eksemplar</h6>
                                    <h2 class="mb-0 fw-bold display-6">{{ number_format($data->sum('total_items'), 0, ',', '.') }}</h2>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="fas fa-layer-group fs-2"></i>
                                </div>
                            </div>
                            <small class="text-white-50">Dalam rentang tahun terpilih</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card unified-card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-table text-primary me-2"></i> Detail Penambahan Per Tahun</h5>
                    <button type="submit" form="filterForm" name="export_csv" value="1" class="btn btn-success btn-sm shadow-sm fw-bold">
                        <i class="fas fa-file-csv me-1"></i> Export CSV
                    </button>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead>
                                <tr>
                                    <th class="text-center" width="10%">No</th>
                                    <th class="text-center" width="30%">Tahun Masuk (Accessioned)</th>
                                    <th class="text-center" width="30%">Judul Baru</th>
                                    <th class="text-center" width="30%">Eksemplar Baru</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $index => $row)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="text-center fw-bold">{{ $row->year }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fs-6">
                                            {{ number_format($row->total_titles, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill fs-6">
                                            {{ number_format($row->total_items, 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="fw-bold border-top">
                                <tr>
                                    <td colspan="2" class="text-end">TOTAL</td>
                                    <td class="text-center">{{ number_format($data->sum('total_titles'), 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($data->sum('total_items'), 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
            <div>Tidak ada data pertambahan koleksi pada rentang tahun tersebut.</div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        @if(count($data) > 0)
        const ctx = document.getElementById('trenChart').getContext('2d');
        
        const labels = {!! json_encode($data->pluck('year')) !!};
        const dataTitles = {!! json_encode($data->pluck('total_titles')) !!};
        const dataItems = {!! json_encode($data->pluck('total_items')) !!};

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Judul Baru',
                        data: dataTitles,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',
                        borderColor: 'rgb(13, 110, 253)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Eksemplar Baru',
                        data: dataItems,
                        backgroundColor: 'rgba(13, 202, 240, 0.7)',
                        borderColor: 'rgb(13, 202, 240)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [2, 4],
                            color: 'rgba(0,0,0,0.05)'
                        }
                    }
                }
            }
        });
        @endif
    });
</script>
@endpush
