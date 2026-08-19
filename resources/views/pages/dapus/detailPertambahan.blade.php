@extends('layouts.app')

@section('title', 'Detail Pengadaan Koleksi Tahun ' . $year)

@section('content')
<div class="container-fluid px-4 py-4">

    <x-breadcrumb title="Detail Pengadaan Koleksi Tahun {{ $year }}" icon="fas fa-book">
        <x-slot name="subtitle">
            Daftar judul buku dan eksemplar yang ditambahkan pada tahun accession {{ $year }}.
        </x-slot>
    </x-breadcrumb>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card unified-card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-search text-primary me-2"></i> Cari Buku Tahun {{ $year }}</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('koleksi.tren_pertambahan_detail', ['year' => $year, 'export_csv' => 1, 'search' => $search]) }}" class="btn btn-success btn-sm shadow-sm fw-bold">
                            <i class="fas fa-file-csv me-1"></i> Export CSV
                        </a>
                        <a href="{{ route('koleksi.tren_pertambahan') }}" class="btn btn-outline-secondary btn-sm fw-bold">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Grafik
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('koleksi.tren_pertambahan_detail') }}" class="row g-3">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <div class="col-md-10">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-select border-start-0" placeholder="Cari berdasarkan Judul, Pengarang, Penerbit, atau Call Number..." value="{{ $search }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card unified-card border-0 shadow-sm">
                <div class="card-body p-4">
                    @include('pages.dapus.partials.detail_pertambahan_table', ['details' => $details])
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
