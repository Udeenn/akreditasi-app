@extends('layouts.app')

@section('title', 'Developers')

@section('content')
    <div class="card shadow-lg">
        <div class="card-header text-center py-3">
            <h4 class="fw-bold mb-0">Tim Pengembang Aplikasi</h4>
            <p class="lead mt-1 mb-0">Akreditasi Support</p>
        </div>
        <div class="card-body p-3">
            <p class="text-center mb-4 small">Aplikasi ini dikembangkan oleh:</p>
            <div class="row g-3 justify-content-center">
                <div class="col-md-6 col-lg-3 d-flex">
                    <div class="card h-100 shadow-sm border-0 developer-card p-2">
                        <div class="card-body text-center d-flex flex-column align-items-center p-2">
                            <i class="fas fa-user-circle fa-4x text-info mb-2"></i>
                            <h6 class="fw-bold mb-0">Muhammad Asharul Maali, S.Kom</h6>
                            <p class="text-muted small mb-1">Lead Developer</p>
                            <div>
                                <a href="#" class="btn btn-outline-dark btn-sm me-1" target="_blank"
                                    data-bs-toggle="tooltip" title="GitHub">
                                    <i class="fab fa-github"></i>
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm" target="_blank"
                                    data-bs-toggle="tooltip" title="LinkedIn">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 d-flex">
                    <div class="card h-100 shadow-sm border-0 developer-card p-2">
                        <div class="card-body text-center d-flex flex-column align-items-center p-2">
                            <i class="fas fa-user-circle fa-4x text-success mb-2"></i>
                            <h6 class="fw-bold mb-0">Ammar Miftahudin Anshori</h6>
                            <p class="text-muted small mb-1">Web Developer / UI/UX</p>
                            <div>
                                <a href="#" class="btn btn-outline-dark btn-sm me-1" target="_blank"
                                    data-bs-toggle="tooltip" title="GitHub">
                                    <i class="fab fa-github"></i>
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm" target="_blank"
                                    data-bs-toggle="tooltip" title="LinkedIn">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 d-flex">
                    <div class="card h-100 shadow-sm border-0 developer-card p-2">
                        <div class="card-body text-center d-flex flex-column align-items-center p-2">
                            <i class="fas fa-user-circle fa-4x text-warning mb-2"></i>
                            <h6 class="fw-bold mb-0">Khoiruddin Nur Wahid, S.Pd</h6>
                            <p class="text-muted small mb-1">Database Administrator / Support</p>
                            <div>
                                <a href="#" class="btn btn-outline-dark btn-sm me-1" target="_blank"
                                    data-bs-toggle="tooltip" title="GitHub">
                                    <i class="fab fa-github"></i>
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm" target="_blank"
                                    data-bs-toggle="tooltip" title="LinkedIn">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <div class="px-5 fs-6">
                    <h5>Ucapan Terima Kasih</h5>
                    <p>Kami mengucapkan terima kasih kepada semua pihak yang telah berkontribusi dan memberikan dukungan
                        dalam
                        pengembangan aplikasi ini, termasuk:</p>
                    <ul>
                        <li>Allah Swt</li>
                        <li>Tim Perpustakaan</li>
                        <li>Komunitas Open Source (Laravel, Bootstrap, Font Awesome, jQuery, Chart.js, DataTables)</li>
                    </ul>
                </div>
            </div>
            <div class="card-footer text-center text-muted p-2">
                <p class="mb-0 small">Dibuat dengan ❤️ dari Indonesia.</p>
            </div>
        </div>
    @endsection
