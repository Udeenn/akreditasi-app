@extends('layouts.app')
@section('title', 'Dashboard')
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
                                <i class="fas fa-tachometer-alt me-2"></i>Statistik Perpustakaan Tahun {{ date('Y') }}
                            </h3>
                            <p class="mb-0 opacity-75">Ringkasan data koleksi dan kunjungan perpustakaan</p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-chart-pie fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. STAT CARDS --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="icon-box bg-primary text-white me-3">
                            <i class="fas fa-book"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Total Jurnal</div>
                            <div class="fs-4 fw-bold">{{ $formatTotalJurnal }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="icon-box bg-success text-white me-3">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Total Judul Buku</div>
                            <div class="fs-4 fw-bold">{{ $formatTotalJudulBuku }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="icon-box bg-info text-white me-3">
                            <i class="fas fa-copy"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Total Eksemplar</div>
                            <div class="fs-4 fw-bold">{{ $formatTotalEksemplar }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card unified-card border-0 shadow-sm hover-lift h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="icon-box bg-danger text-white me-3">
                            <i class="fas fa-tablet-alt"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Jumlah Ebook</div>
                            <div class="fs-4 fw-bold">{{ $formatTotalEbooks }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. KUNJUNGAN HARIAN --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card unified-card border-0 shadow-sm hover-lift">
                    <div class="card-body d-flex justify-content-between align-items-center p-4">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase mb-1">Total Kunjungan Offline —
                                {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY') }}</div>
                            <h3 class="fw-bold mb-0">{{ number_format($kunjunganHarian) }}</h3>
                        </div>
                        <div class="icon-box bg-primary text-white" style="width: 50px; height: 50px;">
                            <i class="fas fa-door-open fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. KUNJUNGAN WEBSITE & REPOSITORY --}}
        @php
            $kunjunganWebsite = [
                'Januari' => 0,
                'Februari' => 0,
                'Maret' => 0,
                'April' => 0,
                'Mei' => 0,
                'Juni' => 0,
                'Juli' => 0,
                'Agustus' => 0,
                'September' => 0,
                'Oktober' => 0,
                'November' => 0,
                'Desember' => 0,
            ];
            $bulanLengkap = [
                'Januari',
                'Februari',
                'Maret',
                'April',
                'Mei',
                'Juni',
                'Juli',
                'Agustus',
                'September',
                'Oktober',
                'November',
                'Desember',
            ];
            foreach ($bulanLengkap as $bln) {
                if (!isset($kunjunganWebsite[$bln])) {
                    $kunjunganWebsite[$bln] = 0;
                }
            }
            $totalKunjunganWebsite = array_sum($kunjunganWebsite);
            $tahunSekarang = date('Y');

            $websiteCol1 = array_slice($kunjunganWebsite, 0, 6, true);
            $websiteCol2 = array_slice($kunjunganWebsite, 6, 6, true);

            $kunjunganRepository = [
                'Januari' => 0,
                'Februari' => 0,
                'Maret' => 0,
                'April' => 0,
                'Mei' => 0,
                'Juni' => 0,
                'Juli' => 0,
                'Agustus' => 0,
                'September' => 0,
                'Oktober' => 0,
                'November' => 0,
                'Desember' => 0,
            ];
            foreach ($bulanLengkap as $bln) {
                if (!isset($kunjunganRepository[$bln])) {
                    $kunjunganRepository[$bln] = 0;
                }
            }
            $totalKunjunganRepository = array_sum($kunjunganRepository);

            $repoCol1 = array_slice($kunjunganRepository, 0, 6, true);
            $repoCol2 = array_slice($kunjunganRepository, 6, 6, true);
        @endphp

        <div class="row g-3 mb-4">
            {{-- Kunjungan Website --}}
            <div class="col-lg-6">
                <div class="card unified-card border-0 shadow-sm h-100">
                    <div class="card-header border-0 d-flex align-items-center pt-3">
                        <div class="icon-box me-2" style="width:32px; height:32px; font-size:0.8rem; background: #f3e8ff; color: #8914d7;">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h6 class="mb-0 fw-bold">Kunjungan Website <span
                                class="text-muted fw-normal">({{ $tahunSekarang }})</span></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <ul class="list-group list-group-flush">
                                    @foreach ($websiteCol1 as $bulan => $jumlah)
                                        <li
                                            class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 list-group-item-action">
                                            <span class="text-body-emphasis">{{ $bulan }}</span>
                                            <span
                                                class="badge bg-primary rounded-pill px-2 py-1">{{ number_format($jumlah) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="col-6">
                                <ul class="list-group list-group-flush">
                                    @foreach ($websiteCol2 as $bulan => $jumlah)
                                        <li
                                            class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 list-group-item-action">
                                            <span class="text-body-emphasis">{{ $bulan }}</span>
                                            <span
                                                class="badge bg-primary rounded-pill px-2 py-1">{{ number_format($jumlah) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Total Tahun Ini:</small>
                            <strong
                                class="d-block fs-5 text-body-emphasis">{{ number_format($totalKunjunganWebsite) }}</strong>
                        </div>
                        <a href="http://statcounter.com/p13060651/summary/?guest=1" target="_blank"
                            class="btn btn-outline-primary btn-sm px-3">
                            <i class="fas fa-external-link-alt me-1"></i> Lihat Detail
                        </a>
                    </div>
                </div>
            </div>

            {{-- Kunjungan Repository --}}
            <div class="col-lg-6">
                <div class="card unified-card border-0 shadow-sm h-100">
                    <div class="card-header border-0 d-flex align-items-center pt-3">
                        <div class="icon-box me-2" style="width:32px; height:32px; font-size:0.8rem; background: #d4edda; color: #04833b;">
                            <i class="fas fa-database"></i>
                        </div>
                        <h6 class="mb-0 fw-bold">Kunjungan Repository <span
                                class="text-muted fw-normal">({{ $tahunSekarang }})</span></h6>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <ul class="list-group list-group-flush">
                                    @foreach ($repoCol1 as $bulan => $jumlah)
                                        <li
                                            class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 list-group-item-action">
                                            <span class="text-body-emphasis">{{ $bulan }}</span>
                                            <span
                                                class="badge bg-success rounded-pill px-2 py-1">{{ number_format($jumlah) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="col-6">
                                <ul class="list-group list-group-flush">
                                    @foreach ($repoCol2 as $bulan => $jumlah)
                                        <li
                                            class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 list-group-item-action">
                                            <span class="text-body-emphasis">{{ $bulan }}</span>
                                            <span
                                                class="badge bg-success rounded-pill px-2 py-1">{{ number_format($jumlah) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Total Tahun Ini:</small>
                            <strong
                                class="d-block fs-5 text-body-emphasis">{{ number_format($totalKunjunganRepository) }}</strong>
                        </div>
                        <a href="http://statcounter.com/p13060683/summary/?guest=1" target="_blank"
                            class="btn btn-outline-primary btn-sm px-3">
                            <i class="fas fa-external-link-alt me-1"></i> Lihat Detail
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/dashboard.css') }}">
@endpush
