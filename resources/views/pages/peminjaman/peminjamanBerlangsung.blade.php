@extends('layouts.app')

@section('title', 'Peminjaman Berlangsung')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <style>
        .modern-header {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
        }
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1) !important;
        }
        .table-custom th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #6b7280;
            background-color: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            padding: 1rem;
        }
        .table-custom td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
        }
        .avatar-circle {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .bg-indigo-soft { background-color: #e0e7ff; color: #4338ca; }
        .bg-green-soft { background-color: #d1fae5; color: #059669; }
        .bg-red-soft { background-color: #fee2e2; color: #dc2626; }
        .bg-yellow-soft { background-color: #fef3c7; color: #d97706; }
        .bg-blue-soft { background-color: #dbeafe; color: #2563eb; }
        
        /* Modern Select2 */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            min-height: 46px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        .btn-modern {
            border-radius: 8px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            letter-spacing: 0.025em;
            transition: all 0.2s ease;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
        }
    </style>
    {{-- Shared styles loaded from unified-components.css --}}
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
                <div class="card border-0 shadow-sm rounded-4 hover-lift">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-muted mb-3 text-uppercase" style="letter-spacing: 1px;"><i class="fas fa-filter me-2 text-primary"></i> Filter Data</h6>
                        <form id="filterPeminjamanBerlangsungForm" class="row g-3 align-items-end">
                            <div class="col-md-9">
                                <label for="prodi" class="form-label fw-semibold text-secondary">Pilih Program Studi</label>
                                <select name="prodi" id="prodi" class="form-select shadow-none">
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
                                <button type="button" id="btnTerapkanFilter" class="btn btn-primary w-100 btn-modern shadow-sm">
                                    <i class="fas fa-search me-2"></i> Terapkan Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. RESULTS SECTION --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom px-4 py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="avatar-circle bg-indigo-soft me-3">
                        <i class="fas fa-list-ul"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Daftar Peminjaman</h5>
                        <small class="text-muted">Total: <strong id="totalCount" class="text-primary fs-6">-</strong> Transaksi Aktif</small>
                    </div>
                </div>

                <button type="button" id="exportCsvBtn"
                    class="btn btn-success btn-modern shadow-sm"><i class="fas fa-file-excel me-2"></i> Export Data
                </button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0" id="myTablePeminjamanBerlangsung"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center" width="5%">No</th>
                                <th width="25%">Peminjam</th>
                                <th width="30%">Informasi Buku</th>
                                <th width="20%">Waktu Pinjam</th>
                                <th width="20%">Status Pengembalian</th>
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
                            var text = data || '-';
                            var parts = text.split(' - ');
                            var nim = parts.length > 1 ? parts[0] : '';
                            var nama = parts.length > 1 ? parts[1] : text;
                            var initial = nama !== '-' ? nama.charAt(0).toUpperCase() : '?';
                            
                            // Generate pseudo-random color based on name
                            var colors = ['bg-indigo-soft', 'bg-blue-soft', 'bg-green-soft', 'bg-yellow-soft', 'bg-red-soft'];
                            var colorClass = colors[initial.charCodeAt(0) % colors.length];

                            return '<div class="d-flex align-items-center">' +
                                '<div class="avatar-circle ' + colorClass + ' me-3 shadow-sm">' + initial + '</div>' +
                                '<div>' +
                                '<span class="d-block fw-bold text-dark">' + nama + '</span>' +
                                (nim ? '<small class="text-muted"><i class="fas fa-id-card me-1"></i> ' + nim + '</small>' : '') +
                                '</div></div>';
                        }
                    },
                    {
                        "data": "JudulBuku",
                        "render": function(data, type, row) {
                            const title = data || '-';
                            const barcode = row.BarcodeBuku || '';
                            return '<div class="d-flex flex-column">' +
                                '<span class="fw-semibold text-dark text-truncate" style="max-width: 320px;" title="' + title + '">' + title + '</span>' +
                                '<div class="d-flex align-items-center mt-1">' +
                                '<span class="badge bg-light text-secondary border px-2 py-1"><i class="fas fa-barcode me-1"></i> ' + barcode + '</span>' +
                                '</div></div>';
                        }
                    },
                    {
                        "data": "BukuDipinjamSaat",
                        "render": function(data) {
                            if (!data) return '-';
                            const m = moment(data);
                            return '<div class="text-muted">' +
                                '<span class="d-block fw-medium"><i class="far fa-calendar-alt me-2 text-primary opacity-75"></i>' + m.format('DD MMM YYYY') + '</span>' +
                                '<small><i class="far fa-clock me-2 text-primary opacity-75"></i>' + m.format('HH:mm') + ' WIB</small></div>';
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
                                return '<div class="d-flex flex-column align-items-start">' +
                                    '<span class="badge bg-red-soft rounded-pill px-3 py-2 mb-1 shadow-sm">' +
                                    '<i class="fas fa-exclamation-triangle me-1"></i> Terlambat ' + diff + ' Hari</span>' +
                                    '<small class="text-danger fw-bold"><i class="fas fa-flag me-1"></i> Batas: ' + dueDate.format('DD MMM YYYY') + '</small></div>';
                            } else if (isToday) {
                                return '<div class="d-flex flex-column align-items-start">' +
                                    '<span class="badge bg-yellow-soft rounded-pill px-3 py-2 mb-1 shadow-sm">' +
                                    '<i class="fas fa-clock me-1"></i> Hari Ini</span>' +
                                    '<small class="text-warning fw-bold text-dark"><i class="fas fa-flag me-1"></i> Batas: ' + dueDate.format('HH:mm') + ' WIB</small></div>';
                            } else {
                                const diff = dueDate.diff(now, 'days');
                                return '<div class="d-flex flex-column align-items-start">' +
                                    '<span class="badge bg-green-soft rounded-pill px-3 py-2 mb-1 shadow-sm">' +
                                    '<i class="fas fa-check-circle me-1"></i> Dipinjam</span>' +
                                    '<small class="text-success fw-bold"><i class="fas fa-hourglass-half me-1"></i> Sisa ' + diff + ' hari</small></div>';
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
                "dom": '<"d-flex justify-content-between mb-3 px-3 mt-3"lf>t<"d-flex justify-content-between mt-3 px-3 mb-3"ip>',
                "drawCallback": function(settings) {
                    // Update total count di header
                    var api = this.api();
                    var pageInfo = api.page.info();
                    if (!pageInfo) return;
                    var total = pageInfo.recordsTotal;
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
