@extends('layouts.app')
@section('title', 'E-Resource - Segera Hadir')

@section('content')
    <div class="container" style="min-height:60vh;">
        <div class="d-flex align-items-center justify-content-center" style="min-height:60vh;">
            <div class="text-center">
                <i class="fas fa-hourglass-half fa-6x text-secondary mb-4" aria-hidden="true"></i>
                <h1 class="display-6 mb-2">Segera Hadir</h1>
                <p class="text-muted mb-4">Halaman Statistik E-Resource sedang dalam pengembangan. Mohon ditunggu.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-warning" onclick="history.back()">Kembali</button>
                    <a href="{{ url('/') }}" class="btn btn-primary">Beranda</a>
                </div>
            </div>
        </div>
    </div>
@endsection
