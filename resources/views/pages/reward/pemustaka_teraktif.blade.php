@extends('layouts.app')

@section('title', 'Pemustaka Teraktif')

@section('content')
    <div class="container-fluid px-4 py-4">

        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div class="card-body p-4">
                <h4 class="mb-1"><i class="fas fa-chart-line me-2 text-primary"></i>Pemustaka Teraktif</h4>
                <p class="text-muted mb-0">Menampilkan daftar pemustaka teraktif di perpustakaan UMS selama satu tahun
                </p>
            </div>
            @if ($hasFilter)
                <div>
                    <span class="badgeborder px-3 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-calendar-check me-1 text-primary"></i> Periode: <strong>{{ $tahun }}</strong>
                    </span>
                </div>
            @endif
        </div>

        {{-- Filter Panel --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('reward.pemustaka_teraktif') }}">
                    <div class="row g-3 align-items-end">

                        {{-- GROUP 1: FILTER (Kiri) --}}
                        <div class="col-md-3 col-lg-3">
                            <label for="tahun" class="form-label text-secondary fw-bold small text-uppercase ls-1">
                                <i class="bi bi-calendar3 me-1"></i> Periode
                            </label>
                            <select name="tahun" id="tahun" class="form-select form-select-lg border-0 ">
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
                            <label for="kategori" class="form-label text-secondary fw-bold small text-uppercase ls-1">
                                <i class="bi bi-funnel me-1"></i> Kategori
                            </label>
                            <select name="kategori" id="kategori" class="form-select form-select-lg border-0 ">
                                <option value="">Semua Kategori</option>
                                <option value="Mahasiswa" {{ request('kategori') == 'Mahasiswa' ? 'selected' : '' }}>
                                    Mahasiswa</option>
                                <option value="Dosen" {{ request('kategori') == 'Dosen' ? 'selected' : '' }}>Dosen</option>
                                <option value="Tendik" {{ request('kategori') == 'Tendik' ? 'selected' : '' }}>Tendik
                                </option>
                            </select>
                        </div>

                        {{-- Tombol Tampilkan (Primary Action) --}}
                        <div class="col-md-2 col-lg-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100  shadow-sm">
                                <i class="bi bi-search"></i> Cari
                            </button>
                        </div>
                        {{-- GROUP 2: EXPORT (Kanan - Spacer Auto) --}}
                        <div class="col-md-4 col-lg-4 ms-auto text-md-end">
                            <div class="d-flex gap-2 justify-content-md-end mt-3 mt-md-0">
                                <button type="button" id="exportPengunjungButton"
                                    class="btn btn-outline-success btn-lg {{ !$hasFilter ? 'disabled' : '' }}"
                                    title="Export Data Pengunjung">
                                    <i class="bi bi-file-earmark-excel me-1"></i> <span class="d-none d-xl-inline">
                                        Pengunjung</span>
                                </button>

                                <button type="button" id="PeminjamButton"
                                    class="btn btn-outline-warning btn-lg {{ !$hasFilter ? 'disabled' : '' }}"
                                    title="Export Data Peminjam">
                                    <i class="bi bi-file-earmark-excel me-1"></i> <span class="d-none d-xl-inline">
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
                    <i class="bi bi-bar-chart-line fs-1 text-muted opacity-25"></i>
                </div>
                <h5 class="text-muted fw-normal">Silakan pilih tahun dan klik Tampilkan</h5>
            </div>
        @else
            <div class="row g-4">
                {{-- Helper Function untuk Render Baris (Agar tidak duplikasi kode) --}}
                @php
                    function renderRow($p, $index, $unit)
                    {
                        // Warna Avatar Random berdasarkan Nama
                        $avatarUrl =
                            'https://ui-avatars.com/api/?name=' .
                            urlencode($p->nama) .
                            '&background=random&color=fff&size=128&bold=true';

                        // Style Rank
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
                            $rankClass = 'text-danger fs-4'; // Bronze color approx
                        } else {
                            $rankIcon = '#' . $index;
                        }

                        // Badge Kategori
                        $badgeClass = match ($p->kategori) {
                            'Mahasiswa' => 'bg-info bg-opacity-10 text-info',
                            'Dosen' => 'bg-success bg-opacity-10 text-success',
                            'Tendik' => 'bg-warning bg-opacity-10 text-warning',
                            default => ' text-secondary',
                        };

                        return '
                    <tr class="align-middle transition-hover">
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
                                <span class="text-muted fs-xs text-uppercase">' .
                            $unit .
                            '</span>
                            </div>
                        </td>
                    </tr>';
                    }
                @endphp

                {{-- Kolom Pengunjung --}}
                <div class="col-xl-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header  pt-4 px-4 pb-2 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold text-primary mb-1"><i class="bi bi-person-check-fill me-2"></i>Top
                                        Pengunjung</h5>
                                    <p class="text-muted small mb-0">Frekuensi tap kartu / presensi</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class=" text-uppercase fs-xs text-secondary fw-bold">
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
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header  pt-4 px-4 pb-2 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold text-success mb-1"><i class="bi bi-book-half me-2"></i>Top Peminjam
                                    </h5>
                                    <p class="text-muted small mb-0">Transaksi peminjaman buku</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class=" text-uppercase fs-xs text-secondary fw-bold">
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

    <style>
        /* Custom CSS agar tampilan lebih clean */
        .fs-xs {
            font-size: 0.75rem;
        }

        .ls-1 {
            letter-spacing: 0.5px;
        }

        .transition-hover:hover {
            background-color: #f8f9fa;
        }

        /* Tabel tanpa border bawah yang kasar */
        .table> :not(caption)>*>* {
            border-bottom-width: 0;
        }

        .table tbody tr {
            border-bottom: 1px solid #f1f1f1;
        }

        .table tbody tr:last-child {
            border-bottom: 0;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exportPengunjungButton = document.getElementById('exportPengunjungButton');
            const exportPeminjamButton = document.getElementById('exportPeminjamButton');

            // Fungsi Helper untuk buat URL Export yang lengkap
            function getExportUrl(routeName) {
                const tahun = document.getElementById('tahun').value;
                const kategori = document.getElementById('kategori').value; // <--- INI KUNCINYA

                // Kita harus kirim 'tahun' DAN 'kategori' ke controller
                return `${routeName}?tahun=${tahun}&kategori=${kategori}`;
            }

            if (exportPengunjungButton) {
                exportPengunjungButton.addEventListener('click', function() {
                    if (this.classList.contains('disabled')) return;

                    // Gunakan helper function di atas
                    const url = getExportUrl("{{ route('reward.export_csv_pemustaka_teraktif') }}");
                    window.location.href = url;
                });
            }

            if (exportPeminjamButton) {
                exportPeminjamButton.addEventListener('click', function() {
                    if (this.classList.contains('disabled')) return;

                    // Gunakan helper function di atas
                    const url = getExportUrl("{{ route('reward.export_csv_peminjam_teraktif') }}");
                    window.location.href = url;
                });
            }
        });
    </script>
@endsection
