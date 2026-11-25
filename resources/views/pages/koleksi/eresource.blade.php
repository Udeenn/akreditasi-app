@extends('layouts.app')
@section('title', 'E-Resource')

@section('content')
<div class="container-fluid px-3 px-md-4 pt-2 pb-4">
    <x-breadcrumb title="E-Resource" icon="fas fa-database">
        <x-slot name="subtitle">
            Pencarian dokumen dan publikasi ilmiah
        </x-slot>
    </x-breadcrumb>

    {{-- COMING SOON CONTENT --}}
    <div class="row justify-content-center mt-4 pt-2">
        <div class="col-12 col-md-8 col-lg-6 text-center">
            <div class="card unified-card border-0 shadow-sm overflow-hidden py-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-inline-flex justify-content-center align-items-center mb-4 stat-icon" style="width: 100px; height: 100px; background: rgba(74, 105, 255, 0.15); border-radius: 50%;">
                        <i class="fas fa-tools fa-3x" style="color: #4A69FF;"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: var(--text-dark);">Fitur Segera Hadir</h4>
                    <p class="mb-4" style="color: var(--text-light); font-size: 1.05rem; line-height: 1.6;">
                        Pencarian <strong>E-Resource</strong> saat ini sedang dalam tahap pengembangan (Coming Soon). 
                        <br>Silakan nantikan pembaruan berikutnya!
                    </p>
                    <a href="{{ route('dashboard') }}" class="btn btn-primary px-4 py-2 rounded-pill fw-medium" style="background: linear-gradient(135deg, #4A69FF, #6366f1); border: none; box-shadow: 0 4px 12px rgba(74,105,255,0.25);">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
