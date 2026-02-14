@extends('layouts.app')

@section('title', 'Pemustaka Teraktif')

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
                                <i class="fas fa-chart-line me-2"></i>Pemustaka Teraktif
                            </h3>
                            <p class="mb-0 opacity-75">
                                Menampilkan daftar pemustaka teraktif di perpustakaan UMS selama satu tahun
                                @if ($hasFilter)
                                    — Periode: <strong>{{ $tahun }}</strong>
                                @endif
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-trophy fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FILTER PANEL --}}
        <div class="card unified-card border-0 shadow-sm filter-card mb-4">
            <div class="card-header border-bottom-0 pt-3 pb-0">
                <h6 class="fw-bold text-primary"><i class="fas fa-filter me-1"></i> Filter Data</h6>
            </div>
            <div class="card-body p-4">
                <form method="GET" action="{{ route('reward.pemustaka_teraktif') }}">
                    <div class="row g-3 align-items-end">

                        {{-- Periode --}}
                        <div class="col-md-3 col-lg-3">
                            <label for="tahun" class="form-label small text-muted fw-bold text-uppercase">
                                <i class="fas fa-calendar me-1"></i> Periode
                            </label>
                            <select name="tahun" id="tahun" class="form-select">
                                @php $currentYear = date('Y'); @endphp
                                @for ($year = $currentYear; $year >= 2020; $year--)
                                    <option value="{{ $year }}"
                                        {{ (int) request('tahun', $currentYear) === $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-3">
                            <label for="kategori" class="form-label small text-muted fw-bold text-uppercase">
                                <i class="fas fa-filter me-1"></i> Kategori
                            </label>
                            <select name="kategori" id="kategori" class="form-select">
                                <option value="">Semua Kategori</option>
                                <option value="Mahasiswa" {{ request('kategori') == 'Mahasiswa' ? 'selected' : '' }}>
                                    Mahasiswa</option>
                                <option value="Dosen" {{ request('kategori') == 'Dosen' ? 'selected' : '' }}>Dosen</option>
                                <option value="Tendik" {{ request('kategori') == 'Tendik' ? 'selected' : '' }}>Tendik
                                </option>
                            </select>
                        </div>

                        {{-- Tombol Cari --}}
                        <div class="col-md-2 col-lg-2">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                        {{-- Export Buttons --}}
                        <div class="col-md-4 col-lg-4 ms-auto text-md-end">
                            <div class="d-flex gap-2 justify-content-md-end mt-3 mt-md-0">
                                <button type="button" id="exportPengunjungButton"
                                    class="btn btn-outline-success {{ !$hasFilter ? 'disabled' : '' }}"
                                    title="Export Data Pengunjung">
                                    <i class="fas fa-file-csv me-1"></i> <span class="d-none d-xl-inline">
                                        Pengunjung</span>
                                </button>

                                <button type="button" id="exportPeminjamButton"
                                    class="btn btn-outline-warning {{ !$hasFilter ? 'disabled' : '' }}"
                                    title="Export Data Peminjam">
                                    <i class="fas fa-file-csv me-1"></i> <span class="d-none d-xl-inline">
                                        Peminjam</span>
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        @if (!$hasFilter)
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-chart-bar fs-1 text-muted opacity-25"></i>
                </div>
                <h5 class="text-muted fw-normal">Silakan pilih tahun dan klik Cari</h5>
            </div>
        @else
            <div class="row g-4">
                {{-- Helper Function untuk Render Baris --}}
                @php
                    function renderRow($p, $index, $unit)
                    {
                        $avatarUrl =
                            'https://api.dicebear.com/7.x/avataaars/svg?seed=' .
                            urlencode($p->nama);

                        $rankIcon = '';
                        $rankClass = 'text-secondary fw-bold';
                        if ($index == 1) {
                            $rankIcon = '🥇';
                            $rankClass = 'text-warning fs-4';
                        } elseif ($index == 2) {
                            $rankIcon = '🥈';
                            $rankClass = 'text-secondary fs-4';
                        } elseif ($index == 3) {
                            $rankIcon = '🥉';
                            $rankClass = 'text-danger fs-4';
                        } else {
                            $rankIcon = '#' . $index;
                        }

                        $badgeClass = match ($p->kategori) {
                            'Mahasiswa' => 'bg-info bg-opacity-10 text-info',
                            'Dosen' => 'bg-success bg-opacity-10 text-success',
                            'Tendik' => 'bg-warning bg-opacity-10 text-warning',
                            default => ' text-secondary',
                        };

                        return '
                    <tr class="align-middle">
                        <td class="text-center ps-4 ' .
                            $rankClass .
                            '" style="width: 80px;">' .
                            $rankIcon .
                            '</td>
                        <td>
                            <div class="d-flex align-items-center py-2">
                                <img src="' .
                            $avatarUrl .
                            '" class="rounded-circle shadow-sm me-3" width="45" height="45" alt="Avatar">
                                <div>
                                    <div class="fw-bold  mb-0 text-truncate" style="max-width: 200px;">' .
                            $p->nama .
                            '</div>
                                    <div class="small text-muted font-monospace">' .
                            $p->cardnumber .
                            '</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge ' .
                            $badgeClass .
                            ' rounded-pill px-3 fw-normal">' .
                            $p->kategori .
                            '</span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex flex-column align-items-end">
                                <span class="fw-bolder fs-5 ">' .
                            number_format($p->jumlah, 0, ',', '.') .
                            '</span>
                                <span class="text-muted small text-uppercase">' .
                            $unit .
                            '</span>
                            </div>
                        </td>
                    </tr>';
                    }
                @endphp

                {{-- Kolom Pengunjung --}}
                <div class="col-xl-6">
                    <div class="card unified-card h-100 border-0 shadow-sm overflow-hidden">
                        <div class="card-header border-0 pt-4 px-4 pb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold text-primary mb-1"><i class="fas fa-user-check me-2"></i>Top
                                        Pengunjung</h5>
                                    <p class="text-muted small mb-0">Frekuensi tap kartu / presensi</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 unified-table">
                                    <thead>
                                        <tr>
                                            <th class="ps-4 py-3 text-center">Rank</th>
                                            <th class="py-3">Nama Pemustaka</th>
                                            <th class="py-3 text-center">Status</th>
                                            <th class="pe-4 py-3 text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($pengunjungTeraktif as $p)
                                            {!! renderRow($p, $loop->iteration, 'Kunjungan') !!}
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">Data tidak tersedia
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kolom Peminjam --}}
                <div class="col-xl-6">
                    <div class="card unified-card h-100 border-0 shadow-sm overflow-hidden">
                        <div class="card-header border-0 pt-4 px-4 pb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold text-success mb-1"><i class="fas fa-book me-2"></i>Top Peminjam
                                    </h5>
                                    <p class="text-muted small mb-0">Transaksi peminjaman buku</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 unified-table">
                                    <thead>
                                        <tr>
                                            <th class="ps-4 py-3 text-center">Rank</th>
                                            <th class="py-3">Nama Pemustaka</th>
                                            <th class="py-3 text-center">Status</th>
                                            <th class="pe-4 py-3 text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($peminjamTeraktif as $p)
                                            {!! renderRow($p, $loop->iteration, 'Buku') !!}
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">Data tidak tersedia
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exportPengunjungButton = document.getElementById('exportPengunjungButton');
            const exportPeminjamButton = document.getElementById('exportPeminjamButton');

            function getExportUrl(routeName) {
                const tahun = document.getElementById('tahun').value;
                const kategori = document.getElementById('kategori').value;
                return `${routeName}?tahun=${tahun}&kategori=${kategori}`;
            }

            if (exportPengunjungButton) {
                exportPengunjungButton.addEventListener('click', function() {
                    if (this.classList.contains('disabled')) return;
                    const url = getExportUrl("{{ route('reward.export_csv_pemustaka_teraktif') }}");
                    window.location.href = url;
                });
            }

            if (exportPeminjamButton) {
                exportPeminjamButton.addEventListener('click', function() {
                    if (this.classList.contains('disabled')) return;
                    const url = getExportUrl("{{ route('reward.export_csv_peminjam_teraktif') }}");
                    window.location.href = url;
                });
            }
        });
    </script>
@endsection
