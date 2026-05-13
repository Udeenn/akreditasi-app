@extends('layouts.app')
@section('title', 'E-Resource')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">
    {{-- HEADER BANNER --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card unified-card border-0 shadow-sm page-header-banner">
                <div class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                    <div class="mb-3 mb-md-0">
                        <h3 class="fw-bold mb-1">
                            <i class="fas fa-database me-2"></i>E-Resource
                        </h3>
                        <p class="mb-0 opacity-75">
                            Pencarian dokumen dan publikasi ilmiah
                        </p>
                    </div>
                    <div class="d-none d-md-block opacity-50">
                        <i class="fas fa-book-journal-whills fa-4x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- COMING SOON CONTENT --}}
    <div class="row justify-content-center mt-5">
        <div class="col-md-8 text-center">
            <div class="card unified-card border-0 shadow-sm py-5">
                <div class="card-body">
                    <div class="mb-4">
                        <i class="fas fa-tools fa-5x text-muted opacity-50"></i>
                    </div>
                    <h4 class="fw-bold text-primary mb-3">Fitur Sedang Dalam Pengembangan</h4>
                    <p class="text-muted mb-4">
                        Fitur pencarian E-Resource saat ini masih dalam tahap pengembangan (Coming Soon).
                        Silakan kembali lagi nanti untuk menikmati fitur ini.
                    </p>
                    <a href="{{ route('dashboard') }}" class="btn btn-primary px-4 rounded-pill">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
