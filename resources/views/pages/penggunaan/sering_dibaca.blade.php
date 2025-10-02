{{-- resources/views/pages/statistik/sering_dibaca.blade.php --}}

@extends('layouts.app')

@section('content')
@section('title', 'Statistik Buku Sering Dibaca')

<div class="container">
    {{-- Header --}}
    <div class="card bg-white shadow-sm mb-4">
        <div class="card-body">
            <h4 class="mb-0">Statistik Buku Sering Dibaca</h4>
            <small class="text-muted">Menampilkan daftar buku yang paling sering digunakan berdasarkan filter.</small>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Data</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('penggunaan.sering_dibaca') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="tahun" class="form-label">Tahun:</label>
                    <select name="tahun" id="tahun" class="form-select">
                        @for ($y = date('Y'); $y >= date('Y') - 10; $y--)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="bulan" class="form-label">Bulan:</label>
                    <select name="bulan" id="bulan" class="form-select">
                        <option value="">-- Semua Bulan --</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Hasil --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Hasil Analisis</h6>
            {{-- Tombol export hanya muncul jika ada filter aktif --}}
            @if (request()->has('tahun'))
                <button id="exportCsvBtn" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv me-2"></i>Export CSV
                </button>
            @endif
        </div>
        <div class="card-body">
            @if (request()->has('tahun'))
                @if ($dataBuku->isEmpty())
                    <div class="alert alert-warning text-center">
                        Tidak ada data untuk ditampilkan pada periode yang dipilih.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="">
                                <tr class="text-center">
                                    <th style="width: 25px;" class="text-center">No</th>
                                    <th>Judul Buku</th>
                                    <th>Pengarang</th>
                                    <th style="width: 150px;" class="text-center">Jumlah Penggunaan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataBuku as $index => $buku)
                                    <tr>
                                        <td class="text-center">{{ $dataBuku->firstItem() + $index }}</td>
                                        <td>{{ $buku->judul_buku }}</td>
                                        <td>{{ $buku->pengarang }}</td>
                                        <td class="text-center fw-bold">{{ number_format($buku->jumlah_penggunaan) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <div class="alert alert-info text-center">
                    Silakan pilih rentang waktu dan tekan "Terapkan" untuk menampilkan data.
                </div>
            @endif
        </div>
        {{-- Tampilkan Link Pagination jika ada filter dan ada halaman --}}
        @if (request()->has('tahun') && $dataBuku->hasPages())
            <div class="card-footer">
                {{ $dataBuku->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

{{-- SCRIPT BARU UNTUK TOMBOL EXPORT --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const exportCsvButton = document.getElementById('exportCsvBtn');
        if (exportCsvButton) {
            exportCsvButton.addEventListener('click', function() {
                // Ambil URL saat ini
                const currentUrl = new URL(window.location.href);
                // Tambahkan parameter 'export=csv'
                currentUrl.searchParams.set('export', 'csv');
                // Arahkan browser ke URL baru untuk men-download file
                window.location.href = currentUrl.toString();
            });
        }
    });
</script>
@endsection
