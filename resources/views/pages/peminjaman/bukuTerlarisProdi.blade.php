@extends('layouts.app')
@section('title', 'Buku Terlaris Berdasarkan Prodi')

@section('content')
    <div class="container-fluid px-4 pt-2 pb-4">
        <x-breadcrumb title="Buku Terlaris Berdasarkan Prodi" icon="fas fa-university">
            <x-slot name="subtitle">
                Daftar buku yang paling sering dipinjam oleh civitas akademika pada program studi tertentu.
            </x-slot>
        </x-breadcrumb>

        <div class="card unified-card border-0 shadow-sm mb-4">
            <div class="card-header border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="mb-0 fw-bold"><i class="fas fa-filter text-primary me-2"></i>Filter Data</h5>
            </div>
            <div class="card-body px-4 py-3">
                <form method="GET" action="{{ route('peminjaman.buku_terlaris_prodi') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted text-uppercase text-uppercase">Program Studi</label>
                        <select name="selected_prodi" class="form-select select2-prodi" required>
                            <option value="">Pilih Program Studi</option>
                            @foreach ($prodiList as $code => $name)
                                <option value="{{ $code }}" {{ $selectedProdi == $code ? 'selected' : '' }}>
                                    {{ $code }} - {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_type" class="form-label small text-muted text-uppercase">Mode Tampilan</label>
                        <select name="filter_type" id="filter_type" class="form-select">
                            <option value="daily" {{ ($filterType ?? 'daily') == 'daily' ? 'selected' : '' }}>Harian</option>
                            <option value="monthly" {{ ($filterType ?? '') == 'monthly' ? 'selected' : '' }}>Tahunan</option>
                        </select>
                    </div>

                    <div class="col-md-5" id="dailyFilter" style="{{ ($filterType ?? 'daily') == 'daily' ? '' : 'display: none;' }}">
                        <label class="form-label small text-muted text-uppercase">Rentang Tanggal</label>
                        <div class="input-group">
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate ?? \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') }}">
                            <span class="input-group-text text-muted border-0">s/d</span>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate ?? \Carbon\Carbon::now()->format('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="col-md-5" id="monthlyFilter" style="{{ ($filterType ?? '') == 'monthly' ? '' : 'display: none;' }}">
                        <label class="form-label small text-muted text-uppercase">Rentang Tahun</label>
                        <div class="input-group">
                            @php
                                $currentYear = date('Y');
                                $loopStartYear = $currentYear - 10;
                                $loopEndYear = $currentYear;
                            @endphp
                            <select name="start_year" id="start_year" class="form-select">
                                @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                    <option value="{{ $year }}" {{ ($startYear ?? $currentYear) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                            <span class="input-group-text text-muted border-0">s.d.</span>
                            <select name="end_year" id="end_year" class="form-select">
                                @for ($year = $loopStartYear; $year <= $loopEndYear; $year++)
                                    <option value="{{ $year }}" {{ ($endYear ?? $currentYear) == $year ? 'selected' : '' }}>{{ $year }}</option>
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
            <div class="card-header border-bottom-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-book-open text-primary me-2"></i>Hasil Pencarian</h5>
                <div class="d-flex gap-2 align-items-center">
                    <form method="GET" action="{{ route('peminjaman.buku_terlaris_prodi') }}" class="d-flex align-items-center mb-0">
                        <input type="hidden" name="selected_prodi" value="{{ $selectedProdi }}">
                        <input type="hidden" name="filter_type" value="{{ $filterType ?? 'daily' }}">
                        <input type="hidden" name="start_date" value="{{ $startDate ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $endDate ?? '' }}">
                        <input type="hidden" name="start_year" value="{{ $startYear ?? '' }}">
                        <input type="hidden" name="end_year" value="{{ $endYear ?? '' }}">
                        <span class="text-muted small me-2" style="white-space: nowrap;">Tampilkan:</span>
                        <select name="per_page" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </form>
                    <form method="POST" action="{{ route('peminjaman.export_buku_terlaris_prodi_pdf') }}" target="_blank">
                        @csrf
                        <input type="hidden" name="selected_prodi" value="{{ $selectedProdi }}">
                        <input type="hidden" name="filter_type" value="{{ $filterType ?? 'daily' }}">
                        <input type="hidden" name="start_date" value="{{ $startDate ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $endDate ?? '' }}">
                        <input type="hidden" name="start_year" value="{{ $startYear ?? '' }}">
                        <input type="hidden" name="end_year" value="{{ $endYear ?? '' }}">
                        <button type="submit" class="btn btn-danger  shadow-sm"><i class="fas fa-file-pdf me-1"></i> Cetak PDF</button>
                    </form>
                    <form method="GET" action="{{ route('peminjaman.export_buku_terlaris_prodi') }}">
                        <input type="hidden" name="selected_prodi" value="{{ $selectedProdi }}">
                        <input type="hidden" name="filter_type" value="{{ $filterType ?? 'daily' }}">
                        <input type="hidden" name="start_date" value="{{ $startDate ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $endDate ?? '' }}">
                        <input type="hidden" name="start_year" value="{{ $startYear ?? '' }}">
                        <input type="hidden" name="end_year" value="{{ $endYear ?? '' }}">
                        <button type="submit" class="btn btn-success  shadow-sm"><i class="fas fa-file-excel me-1"></i> Export CSV</button>
                    </form>
                </div>
            </div>
            <div class="card-body px-0 py-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 unified-table">
                        <thead>
                            <tr>
                                <th class="text-center py-3" style="width: 5%">No</th>
                                <th class="py-3">Judul Buku</th>
                                <th class="py-3">Pengarang</th>
                                <th class="text-center py-3">Klasifikasi</th>
                                <th class="text-center py-3">Total Transaksi</th>
                                <th class="text-center py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paginator as $index => $book)
                                <tr>
                                    <td class="text-center text-muted">{{ $paginator->firstItem() + $index }}</td>
                                    <td>{{ $book->title }}</td>
                                    <td class="text-muted">{{ $book->author ?? '-' }}</td>
                                    <td class="text-center"><span class="badge bg-secondary px-2 py-1">{{ $book->cn_class ?? '-' }}</span></td>
                                    <td class="text-center px-3 py-2 fs-6">{{ number_format($book->total_peminjaman) }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary btn-detail-peminjam" data-biblio="{{ $book->biblionumber }}">
                                            <i class="fas fa-users me-1"></i> Peminjam
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="card border-0 shadow-sm text-center p-5 rounded-4 d-inline-block w-100">
                                            <div class="card-body">
                                                <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex p-4 mb-3">
                                                    <i class="fas fa-folder-open fa-3x text-muted"></i>
                                                </div>
                                                <h5 class="fw-bold text-body">Data Tidak Ditemukan</h5>
                                                <p class="text-muted mb-0">Belum ada data peminjaman buku untuk prodi ini pada periode yang dipilih.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer border-top-0 py-3">
                <div class="d-flex justify-content-end">
                    {{ $paginator->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
        @else
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm text-center p-5 rounded-4">
                    <div class="card-body">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-4 mb-3">
                            <i class="fas fa-search-plus fa-3x text-primary"></i>
                        </div>
                        <h4 class="fw-bold text-body">Mulai Pencarian</h4>
                        <p class="text-muted mb-0">Silakan atur filter di atas lalu klik tombol 
                            <strong>"Terapkan"</strong> untuk menampilkan statistik buku terlaris.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Modal Detail Peminjam --}}
        <div class="modal fade" id="detailPeminjamModal" tabindex="-1" aria-labelledby="detailPeminjamModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 py-3">
                        <div>
                            <h5 class="modal-title fw-bold text-body" id="detailPeminjamModalLabel">
                                <i class="fas fa-users me-2"></i> Detail Peminjam
                            </h5>
                            <span class="text-muted small">Buku: <span id="modal-buku-title" class="fw-bold text-primary"></span></span>
                        </div>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 unified-table" id="tableDetailPeminjam">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center py-3" style="width: 5%">No</th>
                                        <th class="py-3">NIM / NIDN</th>
                                        <th class="py-3">Nama Peminjam</th>
                                        <th class="py-3 text-center">Waktu Transaksi</th>
                                        <th class="py-3 text-center">Tipe Transaksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data akan di-load via AJAX -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="loadingIndicator" class="text-center py-5" style="display:none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-2 mb-0">Memuat data peminjam...</p>
                        </div>

                        <div id="emptyMessage" class="text-center py-5" style="display:none;">
                            <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Tidak ada data detail ditemukan.</p>
                        </div>
                        
                        <div id="modalPagination" class="d-flex justify-content-center py-3 border-light">
                            <!-- Pagination via JS -->
                        </div>
                    </div>
                    <div class="modal-footer border-0 py-3">
                        <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
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
            width: '100%',
            placeholder: 'Pilih Program Studi'
        });

        // Toggle date filters
        $('#filter_type').change(function() {
            if ($(this).val() === 'daily') {
                $('#dailyFilter').show();
                $('#monthlyFilter').hide();
                $('#start_date, #end_date').prop('required', true);
                $('#start_year, #end_year').prop('required', false);
            } else {
                $('#dailyFilter').hide();
                $('#monthlyFilter').show();
                $('#start_date, #end_date').prop('required', false);
                $('#start_year, #end_year').prop('required', true);
            }
        });
        $('#filter_type').trigger('change');

        // Modal Logic
        const detailModal = new bootstrap.Modal(document.getElementById('detailPeminjamModal'));
        let currentBiblio = null;
        let currentTitle = null;

        $('.btn-detail-peminjam').click(function(e) {
            e.preventDefault();
            currentBiblio = $(this).data('biblio');
            currentTitle = $(this).closest('tr').find('td:eq(1)').text().trim();
            $('#modal-buku-title').text(currentTitle);
            
            loadDetail(1);
            detailModal.show();
        });

        function loadDetail(page) {
            $('#tableDetailPeminjam tbody').empty();
            $('#tableDetailPeminjam').hide();
            $('#emptyMessage').hide();
            $('#modalPagination').empty();
            $('#loadingIndicator').show();

            const url = `{{ route('peminjaman.peminjam_buku_detail') }}?biblionumber=${currentBiblio}&selected_prodi={{ urlencode($selectedProdi) }}&filter_type={{ $filterType ?? 'daily' }}&start_date={{ $startDate ?? '' }}&end_date={{ $endDate ?? '' }}&start_year={{ $startYear ?? '' }}&end_year={{ $endYear ?? '' }}&page=${page}`;

            $.ajax({
                url: url,
                type: 'GET',
                success: function(res) {
                    $('#loadingIndicator').hide();
                    
                    if(res.success && res.data.data.length > 0) {
                        let rows = '';
                        let startNo = (res.data.current_page - 1) * res.data.per_page + 1;
                        
                        res.data.data.forEach(function(item, index) {
                            let transLabel = item.transaksi;
                            let badgeClass = 'bg-secondary';
                            
                            if (item.transaksi === 'issue') {
                                transLabel = 'Dipinjam';
                                badgeClass = 'bg-primary';
                            } else if (item.transaksi === 'renew') {
                                transLabel = 'Diperpanjang';
                                badgeClass = 'bg-success';
                            } else if (item.transaksi === 'localuse') {
                                transLabel = 'Baca di Tempat';
                                badgeClass = 'bg-info';
                            }

                            rows += `
                                <tr>
                                    <td class="text-center text-muted">${startNo + index}</td>
                                    <td class="fw-bold">${item.cardnumber ?? '-'}</td>
                                    <td>${item.nama_peminjam ?? '-'}</td>
                                    <td class="text-center">${item.waktu_transaksi}</td>
                                    <td class="text-center"><span class="badge ${badgeClass} px-2 py-1">${transLabel}</span></td>
                                </tr>
                            `;
                        });
                        
                        $('#tableDetailPeminjam tbody').html(rows);
                        $('#tableDetailPeminjam').show();

                        // Render Pagination
                        if (res.data.last_page > 1) {
                            renderPagination(res.data);
                        }
                    } else {
                        $('#emptyMessage').show();
                    }
                },
                error: function(err) {
                    $('#loadingIndicator').hide();
                    $('#emptyMessage').show();
                    console.error("Gagal memuat detail:", err);
                }
            });
        }

        function renderPagination(data) {
            let html = '<ul class="pagination pagination-sm mb-0">';
            
            // Prev
            if (data.current_page > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page - 1}">&laquo;</a></li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">&laquo;</span></li>`;
            }

            // Pages
            let start = Math.max(1, data.current_page - 2);
            let end = Math.min(data.last_page, data.current_page + 2);
            
            if (start > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }

            for (let i = start; i <= end; i++) {
                if (i === data.current_page) {
                    html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                } else {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }
            }

            if (end < data.last_page) {
                if (end < data.last_page - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${data.last_page}">${data.last_page}</a></li>`;
            }

            // Next
            if (data.current_page < data.last_page) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page + 1}">&raquo;</a></li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">&raquo;</span></li>`;
            }

            html += '</ul>';
            $('#modalPagination').html(html);

            // Bind click
            $('#modalPagination .page-link[data-page]').click(function(e) {
                e.preventDefault();
                loadDetail($(this).data('page'));
            });
        }
    });
</script>
@endpush

