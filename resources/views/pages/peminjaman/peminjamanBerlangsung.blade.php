@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }

        /* Dark Mode Select2 Styles */
        body.dark-mode .select2-container--bootstrap-5 .select2-selection {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__rendered {
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__arrow b {
            border-color: #adb5bd transparent transparent transparent !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-dropdown {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            background-color: #334155 !important;
            border-color: var(--border-color) !important;
            color: var(--text-dark) !important;
        }

        body.dark-mode .select2-results__option {
            color: #ffffff !important;
        }

        body.dark-mode .select2-results__option--highlighted {
            background-color: var(--primary-color) !important;
            color: white !important;
        }

        body.dark-mode .select2-results__option[aria-selected=true] {
            background-color: rgba(var(--bs-primary-rgb), 0.2) !important;
            color: #ffffff !important;
        }
    </style>
@endpush
@extends('layouts.app')

@section('title', 'Peminjaman Berlangsung')

@section('content')
    <div class="container">
        <div class="card bg-white shadow-sm mb-4 border-0">
            <div class="card-body p-4">
                <h4 class="mb-1"><i class="fas fa-tasks me-2 text-primary"></i>Daftar Peminjaman Berlangsung</h4>
                <p class="text-muted mb-0">
                    @if ($selectedProdiCode)
                        Menampilkan peminjaman aktif untuk: <strong>{{ $namaProdiFilter }}</strong>
                    @else
                        Menampilkan semua peminjaman yang masih aktif di perpustakaan.
                    @endif
                </p>
            </div>
        </div>
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header border-0">
                <h6 class="mb-0 mt-2"><i class="fas fa-filter me-2"></i>Filter & Pencarian</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('peminjaman.berlangsung') }}" class="row g-3 align-items-end"
                    id="filterPeminjamanBerlangsungForm">

                    <div class="col-md-10">
                        <label for="prodi" class="form-label">Filter Program Studi:</label>
                        <select name="prodi" id="prodi" class="form-select">
                            <option value="semua" {{ request('prodi', 'semua') == 'semua' ? 'selected' : '' }}>
                                (Semua) - Seluruh Prodi
                            </option>
                            @foreach ($listProdi as $kode => $nama)
                                <option value="{{ $kode }}"
                                    {{ ($selectedProdiCode ?? '') == $kode ? 'selected' : '' }}>
                                    ({{ $kode }})
                                    - {{ $nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i
                                class="fas fa-search me-2"></i>Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            @if ($selectedProdiCode || $dataExists)
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-book-reader me-2"></i>Hasil Peminjaman</h6>
                    <button type="button" id="exportCsvBtn" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-file-csv me-1"></i> Export CSV
                    </button>
                </div>
            @endif

            <div class="card-body">
                @if ($dataExists)
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <i class="fas fa-info-circle me-2"></i>
                                    Menampilkan data untuk: <strong>{{ $namaProdiFilter }}</strong>
                                </div>
                                <div class="fs-5">
                                    Total Peminjaman Berlangsung:
                                    <span class="badge bg-primary rounded-pill ms-2">
                                        {{ number_format($activeLoans->total(), 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-bordered">
                                <tr class="text-center">
                                    <th>No.</th>
                                    <th>Waktu Pinjam</th>
                                    <th>Peminjam</th>
                                    <th>Judul Buku</th>
                                    <th>Barcode</th>
                                    <th>Status Pengembalian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activeLoans as $index => $loan)
                                    @php
                                        $dueDate = \Carbon\Carbon::parse($loan->BatasWaktuPengembalian);
                                        $isOverdue = $dueDate->isPast() && !$dueDate->isToday();
                                        $isDueToday = $dueDate->isToday();
                                    @endphp

                                    <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                                        <td>{{ $activeLoans->firstItem() + $index }}</td>
                                        <td>{{ \Carbon\Carbon::parse($loan->BukuDipinjamSaat)->format('d F Y H:i') }}</td>
                                        <td>{{ $loan->Peminjam }}</td>
                                        <td>{{ $loan->JudulBuku }}</td>
                                        <td style="width: 100px">{{ $loan->BarcodeBuku }}</td>
                                        <td class="text-center" style="width: 200px;">
                                            @if ($isOverdue)
                                                <span class="badge bg-danger fs-6">
                                                    TERLAMBAT
                                                    <br>
                                                    <small>({{ $dueDate->diffForHumans(null, true) }} lalu)</small>
                                                </span>
                                            @elseif ($isDueToday)
                                                <span class="badge bg-warning text-dark fs-6">
                                                    HARI INI
                                                    <br>
                                                    <small>({{ $dueDate->format('d F Y H:i') }})</small>
                                                </span>
                                            @else
                                                <span class="">{{ $dueDate->format('d F Y H:i') }}</span>
                                                <br>
                                                <small class="text-muted">({{ $dueDate->diffForHumans(null, true) }}
                                                    again)</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($activeLoans->hasPages())
                        <div class="card-footer border-0 d-flex justify-content-between align-items-center">
                            <small class="text-muted" style="color: var(--bs-secondary-color) !important;">
                                Showing {{ $activeLoans->firstItem() }} to {{ $activeLoans->lastItem() }} of
                                {{ $activeLoans->total() }} results
                            </small>
                            {{ $activeLoans->withQueryString()->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                @else
                    <div class="text-center p-5">
                        <i class="fas fa-ghost fa-3x text-warning mb-3"></i>
                        <h5 class="text-muted">Data Kosong</h5>
                        <p class="text-muted mb-0">
                            Tidak ada data peminjaman yang sedang berlangsung
                            @if ($selectedProdiCode)
                                untuk program studi {{ $namaProdiFilter }}
                            @elseif (request('search'))
                                dengan kata kunci "{{ request('search') }}"
                            @endif
                            saat ini.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>


@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exportBtn = document.getElementById('exportCsvBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', async function() {

                    const prodiSelect = document.getElementById('prodi');
                    const prodiCode = prodiSelect.value;

                    let prodiName = '';
                    if (prodiCode) {
                        const selectedOption = prodiSelect.options[prodiSelect.selectedIndex];
                        const fullText = selectedOption.text;
                        const nameParts = fullText.split(' - ');
                        if (nameParts.length > 1) {
                            prodiName = nameParts.slice(1).join(' - ');
                        } else {
                            prodiName = prodiCode;
                        }
                    }


                    let url = `{{ route('peminjaman.get_berlangsung_export_data') }}`;
                    const params = new URLSearchParams();

                    if (prodiCode) {
                        params.append('prodi', prodiCode);
                    }
                    if (params.toString()) {
                        url += `?${params.toString()}`;
                    }
                    try {
                        const response = await fetch(url);
                        const result = await response.json();
                        if (response.ok && result.data && result.data.length > 0) {
                            const delimiter = ';';
                            const headers = ['No.', 'Buku Dipinjam Saat', 'Judul Buku', 'Barcode Buku',
                                'Peminjam', 'Batas Waktu Pengembalian'
                            ];
                            let csv = [headers.join(delimiter)];
                            result.data.forEach((row, idx) => {
                                csv.push([
                                    idx + 1,
                                    row.BukuDipinjamSaat,
                                    `"${row.JudulBuku.replace(/"/g, '""')}"`,
                                    row.BarcodeBuku,
                                    `"${row.Peminjam.replace(/"/g, '""')}"`,
                                    row.BatasWaktuPengembalian
                                ].join(delimiter));
                            });
                            const csvString = csv.join('\n');
                            const BOM = "\uFEFF";
                            const blob = new Blob([BOM + csvString], {
                                type: 'text/csv;charset=utf-8;'
                            });
                            const link = document.createElement('a');

                            let fileName = 'peminjaman_berlangsung';
                            if (prodiCode && prodiName) {
                                const cleanName = prodiName.replace(/[^a-z0-9]/gi, '_').replace(/_+/g,
                                    '_');
                                fileName += `_${cleanName}`;
                            }
                            fileName += '.csv';

                            link.href = URL.createObjectURL(blob);
                            link.download = fileName;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            URL.revokeObjectURL(link.href);
                        } else {
                            alert('Tidak ada data untuk diekspor.');
                        }
                    } catch (err) {
                        console.error('Export Error:', err);
                        alert('Gagal mengekspor data.');
                    }
                });
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#prodi').select2({
                theme: 'bootstrap-5',
                placeholder: 'Ketik untuk mencari prodi...'
            });
            if ($('#myTableTextbook').length) {

                var table = $('#myTableTextbook').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json",
                    },
                    "paging": true,
                    "lengthChange": true,
                    "searching": true,
                    "ordering": true,
                    "info": true,
                    "autoWidth": false,
                    "responsive": true,
                    "dom": '<"d-flex justify-content-between mb-3"lp>rt<"d-flex justify-content-between mt-3"ip>',
                    "columnDefs": [{
                        "searchable": false,
                        "orderable": false,
                        "targets": 0
                    }],
                    "order": [
                        [1, 'asc']
                    ],
                    "fnDrawCallback": function(oSettings) {
                        this.api().column(0, {
                            search: 'applied',
                            order: 'applied'
                        }).nodes().each(function(cell, i) {
                            cell.innerHTML = i + 1;
                        });
                    }
                });

                $('#searchInput').on('keyup change', function() {
                    table.search(this.value).draw();
                });

                function updateCustomInfo() {
                    var pageInfo = table.page.info();
                    let formatter = new Intl.NumberFormat('id-ID');
                    let formattedTotal = formatter.format(pageInfo
                        .recordsDisplay);
                    $('#customInfoJurnal').html(formattedTotal);
                }

                table.on('draw', updateCustomInfo);

                updateCustomInfo();
            }
        });
    </script>
@endpush
