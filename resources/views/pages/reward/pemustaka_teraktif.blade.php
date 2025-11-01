@extends('layouts.app')

@section('title', 'Pemustaka Teraktif')

@section('content')
    <div class="container px-4">
        <div class="card bg-white shadow-sm mb-4 border-0">
            <div class="card-body p-4">
                <h4 class="mb-1"><i class="fas fa-chart-line me-2 text-primary"></i>Pemustaka Teraktif</h4>
                <p class="text-muted mb-0">Menampilkan daftar pemustaka teraktif di perpustakaan UMS selama satu tahun
                </p>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-funnel-fill me-2"></i>Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reward.pemustaka_teraktif') }}" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label for="tahun" class="form-label fw-bold">Pilih Tahun</label>
                        <select name="tahun" id="tahun" class="form-select form-select-lg">
                            @php $currentYear = date('Y'); @endphp
                            @for ($year = $currentYear; $year >= 2020; $year--)
                                <option value="{{ $year }}"
                                    {{ (int) request('tahun', $currentYear) === $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-search me-2"></i>Lihat
                            Data</button>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <button type="button" id="exportPengunjungButton"
                            class="btn btn-success btn-lg w-100 {{ !$hasFilter ? 'disabled' : '' }}">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export Pengunjung
                        </button>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <button type="button" id="exportPeminjamButton"
                            class="btn btn-warning btn-lg w-100 {{ !$hasFilter ? 'disabled' : '' }}">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export Peminjam
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @if (!$hasFilter)
            <div class="alert alert-info d-flex flex-column flex-sm-row align-items-center justify-content-center shadow-sm text-center"
                role="alert">
                <i class="bi bi-info-circle-fill fs-4 mb-2 mb-sm-0 me-sm-3"></i>
                <div class="px-2">
                    Silakan pilih tahun dan klik "Lihat Data" untuk menampilkan laporan.
                </div>
            </div>
        @endif
        @if ($hasFilter)
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Top 10 Pengunjung Teraktif Tahun
                                {{ $tahun }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover align-middle">
                                    <thead class="">
                                        <tr>
                                            <th scope="col" class="text-center">No</th>
                                            <th scope="col">Kategori</th>
                                            <th scope="col">Cardnumber</th>
                                            <th scope="col" class="text-center">Nama</th>
                                            <th scope="col" class="text-center">Kunjungan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($pengunjungTeraktif as $pengunjung)
                                            <tr>
                                                <td class="text-center" style="width: 100px;">
                                                    <span class="fs-4 fw-bold">{{ $loop->iteration }}</span>
                                                </td>
                                                <td>{{ $pengunjung->kategori }}</td>
                                                <td><code>{{ $pengunjung->cardnumber }}</code></td>
                                                <td>{{ $pengunjung->nama }}</td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-primary-subtle text-primary-emphasis rounded-pill fs-6">
                                                        {{ $pengunjung->jumlah }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center p-4">Tidak ada data pengunjung
                                                    teraktif.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-book-half me-2"></i>Top 10 Peminjam Buku Teraktif Tahun
                                {{ $tahun }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover align-middle">
                                    <thead class="">
                                        <tr>
                                            <th scope="col" class="text-center">No</th>
                                            <th scope="col">Kategori</th>
                                            <th scope="col">Cardnumber</th>
                                            <th scope="col" class="text-center">Nama</th>
                                            <th scope="col" class="text-center">Buku Dipinjam</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($peminjamTeraktif as $peminjam)
                                            <tr>
                                                <td class="text-center" style="width: 100px;">
                                                    <span class="fs-4 fw-bold">{{ $loop->iteration }}</span>
                                                </td>
                                                <td>{{ $peminjam->kategori }}</td>
                                                <td><code>{{ $peminjam->cardnumber }}</code></td>
                                                <td>{{ $peminjam->nama }}</td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-success-subtle text-success-emphasis rounded-pill fs-6">
                                                        {{ $peminjam->jumlah }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center p-4">Tidak ada data peminjam teraktif.
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

            if (exportPengunjungButton) {
                exportPengunjungButton.addEventListener('click', function() {
                    if (this.classList.contains('disabled')) return;
                    const tahun = document.getElementById('tahun').value;
                    const url = `{{ route('reward.export_csv_pemustaka_teraktif') }}?tahun=${tahun}`;
                    window.location.href = url;
                });
            }

            if (exportPeminjamButton) {
                exportPeminjamButton.addEventListener('click', function() {
                    if (this.classList.contains('disabled')) return;
                    const tahun = document.getElementById('tahun').value;
                    const url = `{{ route('reward.export_csv_peminjam_teraktif') }}?tahun=${tahun}`;
                    window.location.href = url;
                });
            }
        });
    </script>
@endsection
