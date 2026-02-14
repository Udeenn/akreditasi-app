@extends('layouts.app')

@section('content')
@section('title', 'Statistik Buku Terlaris')

<div class="container-fluid px-3 px-md-4 py-4">

    {{-- 1. HEADER BANNER --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card unified-card border-0 shadow-sm page-header-banner">
                <div
                    class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                    <div class="mb-3 mb-md-0">
                        <h3 class="fw-bold mb-1">
                            <i class="fas fa-fire me-2"></i>Statistik Buku Terlaris
                        </h3>
                        <p class="mb-0 opacity-75">Menampilkan daftar buku Fiksi dan Non-Fiksi yang paling sering
                            digunakan berdasarkan filter.</p>
                    </div>
                    <div class="d-none d-md-block opacity-50">
                        <i class="fas fa-fire fa-4x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. FILTER --}}
    <div class="card unified-card border-0 shadow-sm filter-card mb-4">
        <div class="card-header border-bottom-0 pt-3 pb-0">
            <h6 class="fw-bold text-primary"><i class="fas fa-filter me-1"></i> Filter Data</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('penggunaan.sering_dibaca') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="tahun" class="form-label small text-muted fw-bold text-uppercase">Tahun:</label>
                    <select name="tahun" id="tahun" class="form-select">
                        @for ($y = date('Y'); $y >= date('Y') - 10; $y--)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="bulan" class="form-label small text-muted fw-bold text-uppercase">Bulan:</label>
                    <select name="bulan" id="bulan" class="form-select">
                        <option value="">Semua Bulan</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i
                            class="fas fa-search me-1"></i>Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (request()->has('tahun'))
        <div class="row g-4">
            {{-- KOLOM FIKSI --}}
            <div class="col-lg-6">
                <div class="card unified-card border-0 shadow-sm h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-magic me-2"></i>Buku Fiksi Terpopuler
                        </h6>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'fiksi']) }}"
                            class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-csv me-1"></i> Export Fiksi
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if ($dataFiksi->isEmpty())
                            <div class="text-center p-5">
                                <i class="fas fa-ghost fa-3x text-warning mb-3"></i>
                                <h5 class="text-muted">Data Kosong</h5>
                                <p class="text-muted mb-0">Tidak ada data fiksi untuk periode ini.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 unified-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 10%;">Peringkat</th>
                                            <th>Judul Buku</th>
                                            <th>Pengarang</th>
                                            <th class="text-center">Jml. Pakai</th>
                                            <th class="text-center">Jml. Eksemplar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataFiksi as $index => $buku)
                                            @php
                                                $rank = $dataFiksi->firstItem() + $index;
                                            @endphp
                                            <tr>
                                                <td class="text-center fw-bold">
                                                    @if ($rank == 1)
                                                        <span class="badge bg-warning text-dark fs-6"
                                                            title="Peringkat 1">
                                                            <i class="fas fa-trophy me-1"></i>1
                                                        </span>
                                                    @elseif ($rank == 2)
                                                        <span class="badge bg-secondary fs-6" title="Peringkat 2">
                                                            <i class="fas fa-medal me-1"></i>2
                                                        </span>
                                                    @elseif ($rank == 3)
                                                        <span class="badge fs-6" title="Peringkat 3"
                                                            style="background-color: #cd7f32; color: white;">
                                                            <i class="fas fa-award me-1"></i>3
                                                        </span>
                                                    @else
                                                        {{ $rank }}
                                                    @endif
                                                </td>
                                                <td>{{ $buku->judul_buku }}</td>
                                                <td>{{ $buku->pengarang }}</td>
                                                <td class="text-center fw-bold">
                                                    {{ number_format($buku->jumlah_penggunaan) }}
                                                </td>
                                                <td class="text-center fw-bold">
                                                    {{ number_format($buku->jumlah_eksemplar) }}
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    @if ($dataFiksi->hasPages())
                        <div class="card-footer border-0 d-flex justify-content-between align-items-center">
                            <small class="text-muted" style="color: var(--bs-secondary-color) !important;">
                                Showing {{ $dataFiksi->firstItem() }} to {{ $dataFiksi->lastItem() }} of
                                {{ $dataFiksi->total() }} results
                            </small>
                            {{ $dataFiksi->withQueryString()->links('pagination::bootstrap-4') }}

                        </div>
                    @endif
                </div>
            </div>

            {{-- KOLOM NON-FIKSI --}}
            <div class="col-lg-6">
                <div class="card unified-card border-0 shadow-sm h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-success"><i class="fas fa-brain me-2"></i>Buku Non-Fiksi
                            Terpopuler</h6>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'nonfiksi']) }}"
                            class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-csv me-1"></i> Export Non-Fiksi
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if ($dataNonFiksi->isEmpty())
                            <div class="text-center p-5">
                                <i class="fas fa-ghost fa-3x text-warning mb-3"></i>
                                <h5 class="text-muted">Data Kosong</h5>
                                <p class="text-muted mb-0">Tidak ada data non-fiksi untuk periode ini.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 unified-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 10%;">Peringkat</th>
                                            <th>Judul Buku</th>
                                            <th>Pengarang</th>
                                            <th class="text-center">Jml. Pakai</th>
                                            <th class="text-center">Jml. Eksemplar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataNonFiksi as $index => $buku)
                                            @php
                                                $rank = $dataNonFiksi->firstItem() + $index;
                                            @endphp
                                            <tr>
                                                <td class="text-center fw-bold">
                                                    @if ($rank == 1)
                                                        <span class="badge bg-warning text-dark fs-6"
                                                            title="Peringkat 1">
                                                            <i class="fas fa-trophy me-1"></i>1
                                                        </span>
                                                    @elseif ($rank == 2)
                                                        <span class="badge bg-secondary fs-6" title="Peringkat 2">
                                                            <i class="fas fa-medal me-1"></i>2
                                                        </span>
                                                    @elseif ($rank == 3)
                                                        <span class="badge fs-6" title="Peringkat 3"
                                                            style="background-color: #cd7f32; color: white;">
                                                            <i class="fas fa-award me-1"></i>3
                                                        </span>
                                                    @else
                                                        {{ $rank }}
                                                    @endif
                                                </td>
                                                <td>{{ $buku->judul_buku }}</td>
                                                <td>{{ $buku->pengarang }}</td>
                                                <td class="text-center fw-bold">
                                                    {{ number_format($buku->jumlah_penggunaan) }}
                                                </td>
                                                <td class="text-center fw-bold">
                                                    {{ number_format($buku->jumlah_eksemplar) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    @if ($dataNonFiksi->hasPages())
                        <div class="card-footer border-0 d-flex justify-content-between align-items-center">
                            <small class="text-muted" style="color: var(--bs-secondary-color) !important;">
                                Showing {{ $dataNonFiksi->firstItem() }} to {{ $dataNonFiksi->lastItem() }} of
                                {{ $dataNonFiksi->total() }} results
                            </small>
                            {{ $dataNonFiksi->withQueryString()->links('pagination::bootstrap-4') }}

                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info text-center p-4">
            <i class="fas fa-info-circle me-2 fa-lg"></i>
            Silakan pilih rentang waktu dan tekan "Terapkan" untuk menampilkan data.
        </div>
    @endif
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {});
</script>
@endsection
