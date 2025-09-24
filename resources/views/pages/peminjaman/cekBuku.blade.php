@extends('layouts.app')

@section('content')
@section('title', 'Cek Histori Buku')

<div class="container">
    {{-- =============================================== --}}
    {{-- Header Halaman --}}
    {{-- =============================================== --}}
    <div class="d-flex align-items-center mb-4">
        <i class="fas fa-barcode fa-2x text-primary me-3"></i>
        <div>
            <h4 class="mb-0">Cek Histori Penggunaan Buku</h4>
            <small class="text-muted">Lacak riwayat peminjaman, pengembalian, dan penggunaan lokal berdasarkan barcode
                buku.</small>
        </div>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('buku.cek_histori') }}" method="GET">
                <div class="row g-2 align-items-center">
                    <div class="col-lg">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="barcode" id="barcode" class="form-control form-control-lg"
                                value="{{ $barcode ?? '' }}" placeholder="Masukkan Barcode Buku..." required>
                        </div>
                    </div>
                    <div class="col-lg-auto">
                        <select name="type_filter" class="form-select form-select-lg">
                            <option value="all" @if (isset($typeFilter) && $typeFilter == 'all') selected @endif>Semua Transaksi
                            </option>
                            <option value="issue" @if (isset($typeFilter) && $typeFilter == 'issue') selected @endif>Peminjaman</option>
                            <option value="return" @if (isset($typeFilter) && $typeFilter == 'return') selected @endif>Pengembalian
                            </option>
                            <option value="localuse" @if (isset($typeFilter) && $typeFilter == 'localuse') selected @endif>Di Tempat
                            </option>
                        </select>
                    </div>
                    <div class="col-lg-auto">
                        <button type="submit" class="btn btn-primary btn-lg w-100">Cari Histori</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($errorMessage)
        <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-triangle me-2"></i>
            {{ $errorMessage }}</div>
    @endif

    {{-- Tampilkan hasil hanya jika ada pencarian dan tidak ada error --}}
    @if ($barcode && !$errorMessage)
        @if ($book)
            <div class="row">
                {{-- Kolom Kiri: Detail Buku & Jumlah Penggunaan --}}
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-book me-2"></i>Detail Buku</h6>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">{{ $book->Judul }}</h5>
                            <p class="card-text text-muted">
                                <small>Oleh:</small> {{ $book->Pengarang ?? 'Tidak diketahui' }}
                            </p>
                            <hr>
                            <h6 class="card-subtitle mb-2 text-muted">Total Penggunaan</h6>
                            <div class="card p-3 text-center mb-3">
                                <h1 class="display-4 fw-bold text-primary mb-0">{{ $totalUsage }}</h1>
                                <small class="text-muted">Kali Digunakan</small>
                            </div>

                            <h6 class="card-subtitle mb-2 text-muted mt-4">Rincian Penggunaan</h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="card bg-primary text-white h-100">
                                        <div class="card-body p-2">
                                            <h5 class="mb-0">{{ $issueCount }}</h5>
                                            <small>Dipinjam</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="card bg-success text-white h-100">
                                        <div class="card-body p-2">
                                            <h5 class="mb-0">{{ $returnCount }}</h5>
                                            <small>Kembali</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="card bg-info text-dark h-100">
                                        <div class="card-body p-2">
                                            <h5 class="mb-0">{{ $localuseCount }}</h5>
                                            <small>Di Tempat</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Kolom Kanan: Tabel Histori --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Transaksi</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal & Waktu</th>
                                            <th>Tipe Transaksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($history as $item)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($item->datetime)->format('d M Y, H:i:s') }}
                                                </td>
                                                <td>
                                                    @if (strtolower($item->type) == 'issue')
                                                        <span class="badge bg-primary">Peminjaman</span>
                                                    @elseif(strtolower($item->type) == 'return')
                                                        <span class="badge bg-success">Pengembalian</span>
                                                    @elseif(strtolower($item->type) == 'localuse')
                                                        <span class="badge bg-info text-dark">Digunakan di Tempat</span>
                                                    @else
                                                        <span
                                                            class="badge bg-secondary">{{ ucfirst($item->type) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{-- Tampilkan pagination jika ada lebih dari 1 halaman --}}
                        @if ($history->hasPages())
                            <div class="card-footer">
                                {{ $history->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            {{-- Tampilan jika barcode tidak ditemukan --}}
            <div class="alert alert-warning text-center"><i class="fas fa-question-circle me-2"></i>Buku dengan barcode
                "<strong>{{ $barcode }}</strong>" tidak ditemukan dalam histori.</div>
        @endif
    @endif

</div>
@endsection
