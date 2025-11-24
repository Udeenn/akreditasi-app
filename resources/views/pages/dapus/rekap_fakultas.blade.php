@extends('layouts.app')
@section('title', 'Rekapitulasi Koleksi per Fakultas')

@section('content')
    <div class="container">
        <div class="card bg-white shadow-sm mb-4 border-0">
            <div class="card-body p-4">
                <h4 class="mb-1"><i class="fas fa-sitemap me-2 text-primary"></i>Rekapitulasi Koleksi per Fakultas</h4>
                <p class="text-muted mb-0">Menampilkan jumlah koleksi (judul dan eksemplar) yang dikelompokkan per program
                    studi.</p>
            </div>
        </div>
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header border-0">
                <h6 class="mb-0 mt-2"><i class="fas fa-filter me-2"></i>Filter Fakultas</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('koleksi.rekap_fakultas') }}" class="row g-3 align-items-end">
                    <div class="col-md-12">
                        <label for="fakultas" class="form-label">Pilih Fakultas (akan langsung memuat data):</label>
                        <select name="fakultas" id="fakultas" class="form-select" onchange="this.form.submit()">
                            @if (empty($faculties))
                                <option value="">Tidak ada data fakultas</option>
                            @else
                                <option value="">Pilih Fakultas</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty }}"
                                        {{ $selectedFaculty == $faculty ? 'selected' : '' }}>
                                        {{ $faculty }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </form>
            </div>
        </div>

        @if ($selectedFaculty)
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Fakultas: <span class="fw-bold">{{ $selectedFaculty }}</span></h4>
            </div>

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="prodiSearchInput" class="form-label"><i class="fas fa-search me-2"></i>Cari Program
                                Studi di Bawah Ini:</label>
                            <input type="text" id="prodiSearchInput" class="form-control"
                                placeholder="Ketik nama atau kode prodi untuk memfilter...">
                        </div>
                    </div>
                </div>
            </div>
            @if ($rekapData->isNotEmpty())
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4" id="prodiCardContainer">
                    @foreach ($rekapData as $prodiData)
                        <div class="col d-flex align-items-stretch prodi-card-col">
                            <div class="card h-100 shadow-sm border-0 w-100">
                                <div class="card-header border-0 font-semibold">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold text-truncate" title="{{ $prodiData['nama_prodi'] }}">
                                            {{ Str::limit($prodiData['nama_prodi'], 35) }}
                                        </h6>
                                        <span class="badge bg-light text-primary ms-2">{{ $prodiData['prodi_code'] }}</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        @forelse ($prodiData['counts'] as $kategori => $counts)
                                            <li class="list-group-item px-0 py-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <strong>{{ $kategori }}</strong>
                                                </div>
                                                <div
                                                    class="d-flex justify-content-between align-items-center text-muted small">
                                                    <span>
                                                        <i class="fas fa-book text-info me-1 opacity-75"></i> Judul
                                                    </span>
                                                    <span
                                                        class="badge bg-secondary rounded-pill">{{ number_format($counts['judul']) }}</span>
                                                </div>
                                                <div
                                                    class="d-flex justify-content-between align-items-center text-muted small mt-1">
                                                    <span>
                                                        <i class="fas fa-copy text-success me-1 opacity-75"></i> Eksemplar
                                                    </span>
                                                    <span
                                                        class="badge bg-success rounded-pill">{{ number_format($counts['eksemplar']) }}</span>
                                                </div>
                                            </li>
                                        @empty
                                            <li class="list-group-item px-0 py-2 text-center text-muted fst-italic small">
                                                Belum ada data koleksi.
                                            </li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div id="noProdiResults" class="text-center p-5" style="display: none;">
                    <i class="fas fa-ghost fa-3x text-warning mb-3"></i>
                    <h5 class="text-muted">Prodi Tidak Ditemukan</h5>
                    <p class="text-muted mb-0">Tidak ada program studi yang cocok dengan pencarian Anda.</p>
                </div>
            @else
                <div class="text-center p-5">
                    <i class="fas fa-ghost fa-3x text-warning mb-3"></i>
                    <h5 class="text-muted">Data Kosong</h5>
                    <p class="text-muted mb-0">Tidak ada data koleksi yang ditemukan untuk prodi di fakultas
                        {{ $selectedFaculty }}.</p>
                </div>
            @endif
        @else
            <div class="text-center p-5">
                <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                <h5 class="text-muted">Silakan Pilih Fakultas</h5>
                <p class="text-muted mb-0">Pilih salah satu fakultas di atas untuk memuat data rekapitulasi.</p>
            </div>
        @endif

    </div>
@endsection
@push('styles')
    <style>
        .bg-gradient-primary {
            background: linear-gradient(45deg, #0d6efd, #6f42c1);
        }

        .card-header .badge {
            font-size: 0.75em;
        }

        .list-group-item {
            background-color: transparent;
        }

        html[data-bs-theme="dark"] .list-group-item strong {
            color: #ffffff !important;
        }

        body.dark-mode .list-group-item strong {
            color: #ffffff !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('prodiSearchInput');
            const prodiContainer = document.getElementById('prodiCardContainer');

            if (searchInput && prodiContainer) {
                const prodiCards = prodiContainer.querySelectorAll('.prodi-card-col');
                const noResultsMessage = document.getElementById('noProdiResults');

                searchInput.addEventListener('keyup', function() {
                    const filter = searchInput.value.toLowerCase().trim();
                    let visibleCount = 0;

                    prodiCards.forEach(card => {
                        const prodiNameEl = card.querySelector('h6');
                        const prodiCodeEl = card.querySelector('.badge');

                        const prodiName = prodiNameEl ? prodiNameEl.getAttribute('title').trim()
                            .toLowerCase() : '';
                        const prodiCode = prodiCodeEl ? prodiCodeEl.textContent.trim()
                            .toLowerCase() : '';

                        if (prodiName.includes(filter) || prodiCode.includes(filter)) {
                            card.classList.remove('d-none');
                            card.classList.add('d-flex');

                            visibleCount++;
                        } else {
                            card.classList.remove('d-flex');
                            card.classList.add('d-none');
                        }
                    });
                    if (noResultsMessage) {
                        noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
                    }
                    prodiContainer.style.display = visibleCount === 0 ? 'none' : '';
                });
            }
        });
    </script>
@endpush
