@extends('layouts.app')

@section('title', 'Cek Histori Buku')

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
                                <i class="fas fa-barcode me-2"></i>Cek Histori Penggunaan Buku
                            </h3>
                            <p class="mb-0 opacity-75">
                                Lacak riwayat peminjaman, pengembalian, dan penggunaan lokal berdasarkan barcode buku.
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-history fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. SEARCH FORM --}}
        <div class="card unified-card border-0 shadow-sm filter-card mb-4">
            <div class="card-header border-bottom-0 pt-3 pb-0">
                <h6 class="fw-bold text-primary"><i class="fas fa-search me-1"></i> Cari Barcode</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('penggunaan.cek_histori') }}" method="GET">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="barcode" id="barcode" class="form-control"
                                    value="{{ $barcode ?? '' }}" placeholder="Masukkan Barcode Buku..." required>
                            </div>
                        </div>

                        {{-- Filter Tahun --}}
                        <div class="col-lg-auto">
                            <select name="tahun" class="form-select">
                                <option value="">Semua Tahun</option>
                                @for ($y = date('Y'); $y >= 2019; $y--)
                                    <option value="{{ $y }}" {{ ($tahun ?? '') == $y ? 'selected' : '' }}>
                                        {{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-lg-auto">
                            <select name="type_filter" class="form-select">
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
                            <button type="submit" class="btn btn-primary w-100">Cari Histori</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($errorMessage)
            <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-triangle me-2"></i>
                {{ $errorMessage }}</div>
        @endif

        @if ($barcode && !$errorMessage)
            @if ($book)
                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <div class="card unified-card shadow-sm h-100 border-0">
                            <div class="card-header border-0">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-book me-2 text-primary"></i>Detail Buku</h6>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">{{ $book->Judul }}</h5>
                                <p class="card-text text-muted">
                                    <small>Oleh:</small> {{ $book->Pengarang ?? 'Tidak diketahui' }}
                                </p>
                                <hr>
                                <h6 class="card-subtitle mb-2 text-muted">Total Penggunaan</h6>
                                <div class="card unified-card p-3 text-center mb-3 border-0"
                                    style="background: var(--primary-soft, #e8f0fe);">
                                    <h1 class="display-4 fw-bold text-primary mb-0">{{ $usageStats->total }}</h1>
                                    <small class="text-muted">Kali Digunakan</small>
                                </div>

                                <h6 class="card-subtitle mb-2 text-muted mt-4">Rincian Penggunaan</h6>
                                <div class="row text-center g-2">
                                    <div class="col-4">
                                        <div class="card unified-card border-0 hover-lift h-100">
                                            <div class="card-body p-2">
                                                <div class="icon-box bg-primary text-white mx-auto mb-1"
                                                    style="width:32px; height:32px; font-size:0.8rem;">
                                                    <i class="fas fa-arrow-right"></i>
                                                </div>
                                                <h5 class="mb-0">{{ $usageStats->issue }}</h5>
                                                <small class="text-muted">Dipinjam</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card unified-card border-0 hover-lift h-100">
                                            <div class="card-body p-2">
                                                <div class="icon-box bg-success text-white mx-auto mb-1"
                                                    style="width:32px; height:32px; font-size:0.8rem;">
                                                    <i class="fas fa-arrow-left"></i>
                                                </div>
                                                <h5 class="mb-0">{{ $usageStats->return }}</h5>
                                                <small class="text-muted">Kembali</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card unified-card border-0 hover-lift h-100">
                                            <div class="card-body p-2">
                                                <div class="icon-box bg-info text-white mx-auto mb-1"
                                                    style="width:32px; height:32px; font-size:0.8rem;">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </div>
                                                <h5 class="mb-0">{{ $usageStats->localuse }}</h5>
                                                <small class="text-muted">Di Tempat</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card unified-card shadow-sm border-0">
                            <div class="card-header border-0">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-primary"></i>Riwayat
                                    Transaksi</h6>
                            </div>
                            <div class="card-body p-0">
                                @php
                                    $badgeMap = [
                                        'issue' => ['class' => 'bg-primary', 'text' => 'Peminjaman'],
                                        'return' => ['class' => 'bg-success', 'text' => 'Pengembalian'],
                                        'localuse' => [
                                            'class' => 'bg-info text-dark',
                                            'text' => 'Digunakan di Tempat',
                                        ],
                                    ];
                                @endphp
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 unified-table">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal & Waktu</th>
                                                <th>Tipe Transaksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($history as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($item->datetime)->format('d M Y, H:i:s') }}
                                                    </td>
                                                    <td>
                                                        @php
                                                            $type = strtolower($item->type);
                                                            $badge = $badgeMap[$type] ?? [
                                                                'class' => 'bg-secondary',
                                                                'text' => ucfirst($type),
                                                            ];
                                                        @endphp
                                                        <span
                                                            class="badge {{ $badge['class'] }}">{{ $badge['text'] }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">
                                                        Tidak ada riwayat transaksi untuk filter ini.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @if ($history->hasPages())
                                <div class="card-footer">
                                    {{ $history->links() }}
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            @else
                <div class="alert alert-warning text-center"><i class="fas fa-question-circle me-2"></i>Buku dengan
                    barcode
                    "<strong>{{ $barcode }}</strong>" tidak ditemukan dalam histori.</div>
            @endif
        @endif

    </div>
@endsection
