@extends('layouts.app')
@section('title', 'Buku Terlaris Berdasarkan Prodi')

@section('content')
    <div class="container-fluid px-4 py-4">
        @include('partials.breadcrumb', [
            'title' => 'Buku Terlaris Berdasarkan Prodi',
            'route' => 'peminjaman.buku_terlaris_prodi',
        ])

        <div class="card unified-card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="mb-0 fw-bold text-gray-800"><i class="fas fa-filter text-primary me-2"></i>Filter Data</h5>
            </div>
            <div class="card-body px-4 py-3">
                <form method="GET" action="{{ route('peminjaman.buku_terlaris_prodi') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-bold text-uppercase">Program Studi</label>
                        <select name="selected_prodi" class="form-select select2-prodi" required>
                            <option value="">Pilih Program Studi</option>
                            @foreach ($prodiList as $code => $name)
                                <option value="{{ $code }}" {{ $selectedProdi == $code ? 'selected' : '' }}>
                                    {{ $code }} - {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-bold text-uppercase">Rentang Tahun</label>
                        <div class="input-group">
                            <input type="number" name="start_year" class="form-control" value="{{ $startYear }}" min="2000" max="{{ date('Y') }}" required>
                            <span class="input-group-text bg-light text-muted border-0">s/d</span>
                            <input type="number" name="end_year" class="form-control" value="{{ $endYear }}" min="2000" max="{{ date('Y') }}" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-bold text-uppercase">Bulan (Opsional)</label>
                        <div class="input-group">
                            <select name="start_month" class="form-select">
                                <option value="">Semua Bulan</option>
                                @for($i=1; $i<=12; $i++)
                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $startMonth == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->locale('id')->monthName }}</option>
                                @endfor
                            </select>
                            <span class="input-group-text bg-light text-muted border-0">s/d</span>
                            <select name="end_month" class="form-select">
                                <option value="">Semua Bulan</option>
                                @for($i=1; $i<=12; $i++)
                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $endMonth == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->locale('id')->monthName }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="fas fa-search me-1"></i> Terapkan</button>
                    </div>
                </form>
            </div>
        </div>

        @if($hasFilter)
        <div class="card unified-card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-gray-800"><i class="fas fa-book-open text-primary me-2"></i>Hasil Pencarian</h5>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('peminjaman.export_buku_terlaris_prodi_pdf') }}" target="_blank">
                        @csrf
                        <input type="hidden" name="selected_prodi" value="{{ $selectedProdi }}">
                        <input type="hidden" name="start_year" value="{{ $startYear }}">
                        <input type="hidden" name="end_year" value="{{ $endYear }}">
                        <input type="hidden" name="start_month" value="{{ $startMonth }}">
                        <input type="hidden" name="end_month" value="{{ $endMonth }}">
                        <button type="submit" class="btn btn-danger btn-sm shadow-sm"><i class="fas fa-file-pdf me-1"></i> Cetak PDF</button>
                    </form>
                    <form method="GET" action="{{ route('peminjaman.export_buku_terlaris_prodi') }}">
                        <input type="hidden" name="selected_prodi" value="{{ $selectedProdi }}">
                        <input type="hidden" name="start_year" value="{{ $startYear }}">
                        <input type="hidden" name="end_year" value="{{ $endYear }}">
                        <input type="hidden" name="start_month" value="{{ $startMonth }}">
                        <input type="hidden" name="end_month" value="{{ $endMonth }}">
                        <button type="submit" class="btn btn-success btn-sm shadow-sm"><i class="fas fa-file-excel me-1"></i> Export CSV</button>
                    </form>
                </div>
            </div>
            <div class="card-body px-0 py-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center py-3" style="width: 5%">No</th>
                                <th class="py-3">Judul Buku</th>
                                <th class="py-3">Pengarang</th>
                                <th class="text-center py-3">Klasifikasi</th>
                                <th class="text-center py-3">Total Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paginator as $index => $book)
                                <tr>
                                    <td class="text-center text-muted">{{ $paginator->firstItem() + $index }}</td>
                                    <td class="fw-bold">{{ $book->title }}</td>
                                    <td class="text-muted">{{ $book->author ?? '-' }}</td>
                                    <td class="text-center"><span class="badge bg-secondary px-2 py-1">{{ $book->cn_class ?? '-' }}</span></td>
                                    <td class="text-center"><span class="badge bg-primary px-3 py-2 fs-6">{{ number_format($book->total_peminjaman) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-folder-open mb-3 fs-2 text-light"></i><br>
                                        Belum ada data peminjaman buku untuk prodi ini pada periode yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 py-3">
                <div class="d-flex justify-content-center">
                    {{ $paginator->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
        @else
        <div class="card unified-card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <h5 class="text-muted fw-bold">Pilih Program Studi</h5>
                <p class="text-muted mb-0">Silakan pilih Program Studi dan rentang waktu di atas untuk melihat buku terlaris.</p>
            </div>
        </div>
        @endif
    </div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-prodi').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Program Studi...',
            width: '100%'
        });
    });
</script>
@endpush

