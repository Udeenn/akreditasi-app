@extends('layouts.app')

@section('title', '404 - Halaman Tidak Ditemukan')

@section('content')
    <div class="container py-5">
        <div class="row d-flex align-items-center justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="row align-items-center">
                            <div class="col-md-6 text-center animate__animated animate__fadeInLeft">
                                <img src="https://cdni.iconscout.com/illustration/premium/thumb/404-error-3702339-3119148.png"
                                    alt="Buku tidak ditemukan" class="img-fluid" style="max-width: 350px;">
                            </div>

                            <div class="col-md-6 text-center text-md-start animate__animated animate__fadeInRight">
                                <h1 class="display-1 fw-bolder text-primary">404</h1>
                                <h2 class="fw-bold">Halaman Tidak Ditemukan</h2>
                                <p class="text-muted mb-4 fs-5">
                                    Sepertinya yang Anda cari tidak ada di website kami. Mungkin salah ketik atau
                                    sudah dipindahkan.
                                </p>
                                <a href="{{ url('/') }}" class="btn btn-md btn-outline-primary">
                                    <i class="fas fa-home me-2"></i>Kembali ke Beranda
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
