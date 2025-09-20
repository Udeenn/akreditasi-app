@extends('layouts.app')

@section('content')
@section('title', 'Pemustaka Teraktif')
<div class="container">
    <h4>Pemustaka Teraktif</h4>

    <form method="GET" action="{{ route('reward.pemustaka_teraktif') }}" class="row g-3 mb-4 align-items-end">
        <div class="col-md-3">
            <label for="tahun" class="form-label">Tahun</label>
            <select name="tahun" id="tahun" class="form-control">
                @php
                    $currentYear = date('Y');
                    for ($year = $currentYear; $year >= 2020; $year--) {
                        echo "<option value='{$year}' " .
                            ((int) request('tahun', $currentYear) === $year ? 'selected' : '') .
                            ">{$year}</option>";
                    }
                @endphp
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Lihat</button>
        </div>
        <div class="col-md-3">
            <button type="button" id="exportPengunjungButton"
                class="btn btn-success w-100 {{ !$hasFilter ? 'disabled' : '' }}">Export Pengunjung ke CSV</button>
        </div>
        <div class="col-md-3">
            <button type="button" id="exportPeminjamButton"
                class="btn btn-warning w-100 {{ !$hasFilter ? 'disabled' : '' }}">Export Peminjam ke CSV</button>
        </div>
    </form>

    {{-- Tampilkan pesan jika form belum di-submit --}}
    @if (!$hasFilter)
        <div class="alert alert-info text-center" role="alert">
            Silakan pilih tahun dan klik "Lihat" untuk menampilkan data laporan.
        </div>
    @endif

    {{-- Tampilkan laporan hanya jika form sudah di-submit --}}
    @if ($hasFilter)
        {{-- Laporan Top 5 Pengunjung --}}
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Top 5 Pengunjung Teraktif Tahun {{ $tahun }}
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Kategori</th>
                                <th>Cardnumber</th>
                                <th>Nama</th>
                                <th>Jumlah Kunjungan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pengunjungTeraktif as $index => $pengunjung)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $pengunjung->kategori }}</td>
                                    <td>{{ $pengunjung->cardnumber }}</td>
                                    <td>{{ $pengunjung->nama }}</td>
                                    <td>{{ $pengunjung->jumlah }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data pengunjung teraktif.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Laporan Top 5 Peminjam Buku --}}
        <div class="card">
            <div class="card-header bg-success text-white">
                Top 5 Peminjam Buku Teraktif Tahun {{ $tahun }}
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Kategori</th>
                                <th>Cardnumber</th>
                                <th>Nama</th>
                                <th>Jumlah Buku Dipinjam</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($peminjamTeraktif as $index => $peminjam)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $peminjam->kategori }}</td>
                                    <td>{{ $peminjam->cardnumber }}</td>
                                    <td>{{ $peminjam->nama }}</td>
                                    <td>{{ $peminjam->jumlah }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data peminjam teraktif.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
                const tahun = document.getElementById('tahun').value;
                const url = `{{ route('reward.export_csv_pemustaka_teraktif') }}?tahun=${tahun}`;
                window.location.href = url;
            });
        }

        if (exportPeminjamButton) {
            exportPeminjamButton.addEventListener('click', function() {
                const tahun = document.getElementById('tahun').value;
                const url = `{{ route('reward.export_csv_peminjam_teraktif') }}?tahun=${tahun}`;
                window.location.href = url;
            });
        }
    });
</script>
@endsection
