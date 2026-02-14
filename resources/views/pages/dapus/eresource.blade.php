@extends('layouts.app')
@section('title', 'E-Resource - Segera Hadir')

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">

        {{-- HEADER BANNER --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card unified-card border-0 shadow-sm page-header-banner">
                    <div
                        class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0">
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-database me-2"></i>E-Resource
                            </h3>
                            <p class="mb-0 opacity-75">
                                Statistik koleksi e-resource perpustakaan
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-database fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- COMING SOON --}}
        <div class="card unified-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center" style="min-height:40vh;">
                    <div class="text-center">
                        <i class="fas fa-hourglass-half fa-6x text-secondary mb-4"></i>
                        <h1 class="display-6 mb-2">Segera Hadir</h1>
                        <p class="text-muted mb-4">Halaman Statistik E-Resource sedang dalam pengembangan. Mohon ditunggu.
                        </p>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-outline-warning"
                                onclick="history.back()">Kembali</button>
                            <a href="{{ url('/') }}" class="btn btn-primary">Beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
