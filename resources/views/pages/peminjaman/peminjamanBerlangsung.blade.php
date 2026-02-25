@extends('layouts.app')

@section('title', 'Peminjaman Berlangsung')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />

    <style>
        /* --- MODERN DASHBOARD STYLING --- */
        :root {
            --primary-soft: rgba(13, 110, 253, 0.1);
            --success-soft: rgba(25, 135, 84, 0.1);
            --warning-soft: rgba(255, 193, 7, 0.1);
            --danger-soft: rgba(220, 53, 69, 0.1);
            --info-soft: rgba(13, 202, 240, 0.1);
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            overflow: hidden;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }

        /* Badge Soft */
        .badge.bg-danger-soft {
            background-color: var(--danger-soft);
            color: #dc3545;
        }

        .badge.bg-warning-soft {
            background-color: var(--warning-soft);
            color: #856404;
        }

        .badge.bg-success-soft {
            background-color: var(--success-soft);
            color: #198754;
        }

        .badge.bg-primary-soft {
            background-color: var(--primary-soft);
            color: #0d6efd;
        }

        /* Table Styling */
        .table thead th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
            padding: 1rem;
        }

        .table td {
            padding: 1rem 1rem;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.015);
        }

        /* Row Highlight for Overdue */
        .tr-overdue {
            background-color: rgba(220, 53, 69, 0.03) !important;
        }

        .tr-overdue:hover {
            background-color: rgba(220, 53, 69, 0.06) !important;
        }

        /* --- SELECT2 CUSTOMIZATION --- */
        .select2-container--bootstrap-5 .select2-selection {
            border-color: #dee2e6;
            padding: 0.5rem 1rem;
            height: auto;
            border-radius: 0.5rem;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
            border-color: #86b7fe;
        }

        /* Dark Mode Fixes */
        body.dark-mode .card {
            background-color: #1e1e2d;
            border: 1px solid #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .card-header {
            background-color: #1e293b !important;
            border-bottom-color: #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .text-muted {
            color: #a1a5b7 !important;
        }

        body.dark-mode .table {
            color: #ffffff;
            border-color: #2b2b40;
        }

        body.dark-mode .table thead th {
            background-color: #2b2b40;
            color: #ffffff;
            border-bottom-color: #3f4254;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #1e293b;
            border-color: #2b2b40;
            color: #ffffff;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection {
            background-color: #1e293b !important;
            border-color: #2b2b40 !important;
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-dropdown {
            background-color: #1e293b !important;
            border-color: #2b2b40 !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__rendered {
            color: #ffffff !important;
        }

        body.dark-mode .select2-results__option--highlighted {
            background-color: #0d6efd !important;
            color: white !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-search .select2-search__field {
            background-color: #1e293b !important;
            border-color: #2b2b40 !important;
            color: #ffffff !important;
            caret-color: white;
        }

        body.dark-mode .select2-results__option {
            color: #ffffff !important;
            background-color: #1e293b;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
            background-color: #0d6efd !important;
            color: #ffffff !important;
        }

        body.dark-mode .select2-container--bootstrap-5 .select2-selection__placeholder {
            color: #a1a5b7 !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">

        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div
                        class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0 text-center text-md-start">
                            <h3 class="fw-bold mb-1"><i class="fas fa-tasks me-2"></i>Peminjaman Berlangsung</h3>
                            <p class="mb-0 opacity-75 page-header-subtitle">
                                @if ($selectedProdiCode && $selectedProdiCode !== 'semua')
                                    Menampilkan data aktif untuk: <strong>{{ $namaProdiFilter }}</strong>
                                @else
                                    Daftar seluruh buku yang sedang dipinjam saat ini.
                                @endif
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-book-reader fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FILTER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm hover-lift">
                    <div class="card-header border-bottom-0 ">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-filter me-2"></i> Filter Data</h6>
                    </div>
                    <div class="card-body pt-0 pb-4">
                        <form id="filterPeminjamanBerlangsungForm" class="row g-3 align-items-end">
                            <div class="col-md-9">
                                <label for="prodi" class="form-label fw-bold small text-muted text-uppercase">Program
                                    Studi</label>
                                <select name="prodi" id="prodi" class="form-select">
                                    <option value="semua" {{ request('prodi', 'semua') == 'semua' ? 'selected' : '' }}>
                                        (Semua) - Tampilkan Seluruh Data</option>
                                    @foreach ($listProdi as $kode => $nama)
                                        <option value="{{ $kode }}"
                                            {{ ($selectedProdiCode ?? '') == $kode ? 'selected' : '' }}>
                                            {{ $nama }} ({{ $kode }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="btnTerapkanFilter" class="btn btn-primary w-100 fw-bold shadow-sm"
                                    style="padding: 0.6rem;">
                                    <i class="fas fa-search me-2"></i> Terapkan Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. RESULTS SECTION --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary-soft text-primary rounded-circle me-3"
                        style="width: 40px; height: 40px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-list-ul"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Daftar Peminjaman</h6>
                        <small class="text-muted">Total:
                            <strong id="totalCount">-</strong> Transaksi</small>
                    </div>
                </div>

                <button type="button" id="exportCsvBtn"
                    class="btn btn-success btn-sm fw-bold shadow-sm px-3"><i class="fas fa-file-csv me-2"></i> Export CSV
                </button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="myTablePeminjamanBerlangsung"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center" width="5%">No</th>
                                <th width="20%">Peminjam</th>
                                <th width="30%">Buku</th>
                                <th width="20%">Waktu Pinjam</th>
                                <th width="25%">Status Pengembalian</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/locale/id.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2
            $('#prodi').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih Program Studi...',
                width: '100%'
            });

            // Initialize DataTables with Server-Side Processing
            var table = $('#myTablePeminjamanBerlangsung').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('peminjaman.berlangsung_data') }}",
                    "data": function(d) {
                        // Baca nilai dropdown prodi SAAT INI (bukan static)
                        d.prodi = $('#prodi').val();
                    }
                },
                "columns": [{
                        "data": null,
                        "orderable": false,
                        "searchable": false,
                        "className": "text-center text-muted fw-bold",
                        "render": function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        "data": "Peminjam",
                        "render": function(data) {
                            return '<div class="d-flex flex-column"><span class="fw-bold">' +
                                (data || '-') + '</span></div>';
                        }
                    },
                    {
                        "data": "JudulBuku",
                        "render": function(data, type, row) {
                            const title = data || '-';
                            const barcode = row.BarcodeBuku || '';
                            return '<div class="d-flex align-items-center">' +
                                '<i class="fas fa-book text-muted me-3 opacity-50"></i>' +
                                '<div>' +
                                '<span class="d-block fw-semibold text-truncate" style="max-width: 300px;" title="' +
                                title + '">' + title + '</span>' +
                                '<small class="text-muted font-monospace px-2 rounded border">' +
                                '<i class="fas fa-barcode me-1"></i> ' + barcode +
                                '</small></div></div>';
                        }
                    },
                    {
                        "data": "BukuDipinjamSaat",
                        "render": function(data) {
                            if (!data) return '-';
                            const m = moment(data);
                            return '<div class="text-muted small">' +
                                '<i class="far fa-calendar-alt me-1 text-primary"></i> ' +
                                m.format('DD MMM YYYY') +
                                '<br><i class="far fa-clock me-1 text-primary ms-1"></i> ' +
                                m.format('HH:mm') + ' WIB</div>';
                        }
                    },
                    {
                        "data": "BatasWaktuPengembalian",
                        "render": function(data) {
                            if (!data) return '-';
                            const dueDate = moment(data);
                            const now = moment();
                            const isToday = dueDate.isSame(now, 'day');
                            const isOverdue = dueDate.isBefore(now, 'day');

                            if (isOverdue) {
                                const diff = now.diff(dueDate, 'days');
                                return '<div class="d-flex align-items-center">' +
                                    '<span class="badge bg-danger-soft rounded-pill px-3 py-2 me-2">' +
                                    '<i class="fas fa-exclamation-circle me-1"></i> TERLAMBAT</span>' +
                                    '<small class="text-danger fw-bold">' + diff +
                                    ' hari</small></div>' +
                                    '<small class="text-muted d-block mt-1">Batas: ' +
                                    dueDate.format('DD MMM YYYY') + '</small>';
                            } else if (isToday) {
                                return '<span class="badge bg-warning-soft rounded-pill px-3 py-2">' +
                                    '<i class="fas fa-clock me-1"></i> HARI INI</span>' +
                                    '<small class="text-muted d-block mt-1">Batas: ' +
                                    dueDate.format('HH:mm') + ' WIB</small>';
                            } else {
                                const diff = dueDate.diff(now, 'days');
                                return '<div class="d-flex flex-column">' +
                                    '<span class="badge bg-success-soft rounded-pill px-3 py-2 w-auto align-self-start">' +
                                    '<i class="fas fa-check-circle me-1"></i> Dipinjam</span>' +
                                    '<small class="text-muted mt-1">Sisa: ' + diff +
                                    ' hari</small></div>';
                            }
                        }
                    }
                ],
                "order": [
                    [3, "desc"]
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                },
                "lengthMenu": [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                "pageLength": 10,
                "dom": '<"d-flex justify-content-between mb-3 px-3 mt-3"lfP>t<"d-flex justify-content-between mt-3 px-3 mb-3"ip>',
                "drawCallback": function(settings) {
                    // Update total count di header
                    var api = this.api();
                    var total = api.page.info().recordsTotal;
                    $('#totalCount').text(total.toLocaleString('id-ID'));

                    // Highlight overdue rows
                    api.rows().every(function() {
                        var data = this.data();
                        if (data.BatasWaktuPengembalian) {
                            var dueDate = moment(data.BatasWaktuPengembalian);
                            if (dueDate.isBefore(moment(), 'day')) {
                                $(this.node()).addClass('tr-overdue');
                            }
                        }
                    });
                },
                "createdRow": function(row, data) {
                    if (data.BatasWaktuPengembalian) {
                        var dueDate = moment(data.BatasWaktuPengembalian);
                        if (dueDate.isBefore(moment(), 'day')) {
                            $(row).addClass('tr-overdue');
                        }
                    }
                }
            });

            // Tombol Filter: Reload DataTable via AJAX (tanpa reload halaman!)
            $('#btnTerapkanFilter').on('click', function() {
                // Update subtitle header
                const prodiSelect = document.getElementById('prodi');
                const selectedOption = prodiSelect.options[prodiSelect.selectedIndex];
                const headerText = document.querySelector('.page-header-subtitle');
                if (headerText) {
                    if (prodiSelect.value && prodiSelect.value !== 'semua') {
                        headerText.innerHTML = 'Menampilkan data aktif untuk: <strong>' + selectedOption.text.trim() + '</strong>';
                    } else {
                        headerText.textContent = 'Daftar seluruh buku yang sedang dipinjam saat ini.';
                    }
                }
                // Reload DataTable (akan membaca d.prodi dari dropdown)
                table.ajax.reload();
            });

            // Export CSV Logic
            const exportBtn = document.getElementById('exportCsvBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', async function() {
                    const prodiSelect = document.getElementById('prodi');
                    const prodiCode = prodiSelect.value;
                    let prodiName = '';

                    if (prodiCode && prodiCode !== 'semua') {
                        const selectedOption = prodiSelect.options[prodiSelect.selectedIndex];
                        const text = selectedOption.text;
                        const parts = text.split(' - ');
                        prodiName = parts.length > 1 ? parts[1] : text;
                    } else {
                        prodiName = 'Semua_Prodi';
                    }

                    // Build URL
                    let url = `{{ route('peminjaman.get_berlangsung_export_data') }}`;
                    const params = new URLSearchParams();
                    if (prodiCode) params.append('prodi', prodiCode);
                    if (params.toString()) url += `?${params.toString()}`;

                    try {
                        const response = await fetch(url);
                        const result = await response.json();

                        if (response.ok && result.data && result.data.length > 0) {
                            const delimiter = ';';
                            let csv = [];

                            // --- TITLE / INFO HEADER ---
                            const selectedOption = prodiSelect.options[prodiSelect.selectedIndex];
                            const filterLabel = (prodiCode && prodiCode !== 'semua')
                                ? selectedOption.text.trim()
                                : 'Semua Program Studi';
                            const today = new Date();
                            const exportDate = today.toLocaleDateString('id-ID', {
                                day: 'numeric', month: 'long', year: 'numeric'
                            });

                            csv.push(`"Laporan Peminjaman Berlangsung"`);
                            csv.push(`"Filter Program Studi"${delimiter}"${filterLabel}"`);
                            csv.push(`"Tanggal Export"${delimiter}"${exportDate}"`);
                            csv.push(`"Total Data"${delimiter}"${result.data.length}"`);
                            csv.push(''); // Baris kosong pemisah

                            // --- COLUMN HEADERS ---
                            const headers = ['No.', 'Waktu Pinjam', 'Judul Buku', 'Barcode', 'Peminjam',
                                'Batas Waktu'
                            ];
                            csv.push(headers.join(delimiter));

                            result.data.forEach((row, idx) => {
                                csv.push([
                                    idx + 1,
                                    row.BukuDipinjamSaat,
                                    `"${row.JudulBuku.replace(/"/g, '""')}"`,
                                    `"${row.BarcodeBuku}"`,
                                    `"${row.Peminjam.replace(/"/g, '""')}"`,
                                    row.BatasWaktuPengembalian
                                ].join(delimiter));
                            });

                            // Download
                            const BOM = "\uFEFF";
                            const blob = new Blob([BOM + csv.join('\n')], {
                                type: 'text/csv;charset=utf-8;'
                            });
                            const link = document.createElement('a');

                            const cleanName = prodiName.replace(/[^a-z0-9]/gi, '_').toLowerCase();
                            const fileName =
                                `peminjaman_berlangsung_${cleanName}_${new Date().toISOString().slice(0,10)}.csv`;

                            link.href = URL.createObjectURL(blob);
                            link.download = fileName;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
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
@endpush
