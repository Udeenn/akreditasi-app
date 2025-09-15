@extends('layouts.app')
@section('content')
@section('title', 'Statistik Peminjaman per Program Studi')
<div class="container">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"> Statistik Peminjaman per Program Studi</h1>
    </div>

    {{-- ... (Opsi Filter Data) tetap sama ... --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-filter"></i> Opsi Filter Data</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('peminjaman.peminjaman_prodi_chart') }}" method="GET"
                class="row g-3 align-items-end">
                {{-- Filter per Hari atau per Tahun --}}
                <div class="col-md-auto">
                    <label for="filter_type" class="form-label text-muted">Tampilkan Data:</label>
                    <select name="filter_type" id="filter_type" class="form-select">
                        <option value="yearly" {{ $filterType == 'yearly' ? 'selected' : '' }}>Per Tahun</option>
                        <option value="daily" {{ $filterType == 'daily' ? 'selected' : '' }}>Per Hari</option>
                    </select>
                </div>
                {{-- Input filter per Tahun --}}
                <div class="col-md-3" id="yearlyFilter" style="{{ $filterType == 'yearly' ? '' : 'display: none;' }}">
                    <label for="selected_year" class="form-label text-muted">Pilih Tahun:</label>
                    <select name="selected_year" id="selected_year" class="form-select">
                        @php
                            $currentYear = \Carbon\Carbon::now()->year;
                            $startYear = $currentYear - 5;
                            $endYear = $currentYear;
                        @endphp
                        @for ($year = $startYear; $year <= $endYear; $year++)
                            <option value="{{ $year }}"
                                {{ ($selectedYear ?? $currentYear) == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                </div>
                {{-- Input filter per Hari --}}
                <div class="col-md-4" id="dailyFilter" style="{{ $filterType == 'daily' ? '' : 'display: none;' }}">
                    <label for="start_date" class="form-label text-muted">Rentang Tanggal:</label>
                    <div class="input-group">
                        <input type="date" name="start_date" id="start_date" class="form-control"
                            value="{{ $startDate ?? '' }}">
                        <span class="input-group-text">s.d.</span>
                        <input type="date" name="end_date" id="end_date" class="form-control"
                            value="{{ $endDate ?? '' }}">
                    </div>
                </div>
                {{-- Dropdown Program Studi --}}
                <div class="col-md-4">
                    <label for="selected_prodi" class="form-label text-muted">Pilih Program Studi:</label>
                    <select name="selected_prodi" id="selected_prodi" class="form-select">
                        @foreach ($prodiOptions as $prodi)
                            <option value="{{ $prodi->authorised_value }}"
                                {{ $selectedProdiCode == $prodi->authorised_value ? 'selected' : '' }}>
                                {{ $prodi->lib }} ({{ $prodi->authorised_value }})
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Tombol Filter --}}
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Terapkan
                        Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if (!$dataExists)
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            {{-- Tidak ada data peminjaman untuk program studi yang dipilih pada
            @if ($filterType == 'daily')
                rentang tanggal {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} sampai
                {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}.
            @else
                tahun {{ $selectedYear }}
            @endif --}}
            Silakan gunakan filter di atas untuk menampilkan data statistik peminjaman.
        </div>
    @else
        <div class="card mt-4 shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-line"></i> Grafik Statistik Peminjaman
                    @if ($filterType == 'daily')
                        per Hari
                    @else
                        per Bulan
                    @endif
                    ({{ $prodiOptions->firstWhere('authorised_value', $selectedProdiCode)->lib ?? 'Nama Prodi Tidak Ditemukan' }})
                </h6>
            </div>
            <div class="card-body">
                <canvas id="peminjamanProdiChart"></canvas>
            </div>
        </div>

        <div class="card mt-4 shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                Ringkasan Data Peminjaman
                <button type="button" id="exportCsvBtn" class="btn btn-success btn-sm"><i class="fas fa-file-csv"></i>
                    Export CSV</button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="alert alert-info py-2">
                            <i class="fas fa-book me-2"></i>Buku Terpinjam:
                            <span class="fw-bold">{{ number_format($totalBooks) }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-success py-2">
                            <i class="fas fa-undo-alt me-2"></i>Buku Dikembalikan:
                            <span class="fw-bold">{{ number_format($totalReturns) }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-info py-2">
                            <i class="fas fa-users me-2"></i> Total Peminjam :
                            <span class="fw-bold">{{ number_format($totalBorrowers) }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-warning py-2">
                            <i class="fas fa-book-reader me-2"></i> Total Entri:
                            <span class="fw-bold">{{ $statistics->total() }}</span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="prodiPeminjamanTable">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th>Periode</th>
                                <th>Jumlah Buku Terpinjam</th>
                                {{-- <th>Jumlah Buku Dikembalikan</th> --}}
                                <th>Jumlah Orang Transaksi</th>
                                <th>Detail Sirkulasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($statistics as $stat)
                                <tr>
                                    <td>
                                        @if ($filterType == 'daily')
                                            {{ \Carbon\Carbon::parse($stat->periode)->format('d M Y') }}
                                        @else
                                            {{ \Carbon\Carbon::createFromFormat('Y-m', $stat->periode)->format('M Y') }}
                                        @endif
                                    </td>
                                    <td>{{ $stat->jumlah_buku_terpinjam }}</td>
                                    {{-- <td>{{ $stat->jumlah_buku_kembali }}</td> --}}
                                    <td>{{ $stat->jumlah_peminjam_unik }}</td>
                                    <td>
                                        <button type="button"
                                            class="btn btn-sm btn-primary text-white view-borrowers-btn"
                                            data-bs-toggle="modal" data-bs-target="#borrowersModal"
                                            data-periode="{{ $stat->periode }}"
                                            data-prodi-code="{{ $selectedProdiCode }}" data-page="1">
                                            <i class="fas fa-eye"></i> Lihat
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{-- Pagination untuk tabel utama --}}
                    <div class="d-flex justify-content-center">
                        {{ $statistics->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- <div class="modal fade" id="borrowersModal" tabindex="-1" aria-labelledby="borrowersModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="borrowersModalLabel">
                    <i class="fas fa-users me-2"></i> Detail Peminjam
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fs-6">Program Studi: <strong class="fw-bold" id="modalProdiName"></strong></span>
                    <span class="fs-6">Periode: <strong class="fw-bold" id="modalPeriod"></strong></span>
                </div>
                <div id="loadingSpinner" class="text-center py-5" style="display:none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Memuat data peminjam...</p>
                </div>
                <div id="noDataMessage" class="alert alert-info text-center mt-3" style="display:none;">
                    <i class="fas fa-info-circle me-2"></i> Tidak ada data peminjam untuk periode ini.
                </div>
                <ul id="borrowersList" class="list-group list-group-flush">
                </ul>
                <div id="modalPagination" class="d-flex justify-content-center mt-3">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div> --}}

{{-- modal tabel --}}
<div class="modal fade" id="borrowersModal" tabindex="-1" aria-labelledby="borrowersModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="borrowersModalLabel">
                    <i class="fas fa-users me-2"></i> Detail Peminjam
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fs-6">Program Studi: <strong class="fw-bold" id="modalProdiName"></strong></span>
                    <span class="fs-6">Periode: <strong class="fw-bold" id="modalPeriod"></strong></span>
                </div>
                <div id="loadingSpinner" class="text-center py-5" style="display:none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Memuat data peminjam...</p>
                </div>
                <div id="noDataMessage" class="alert alert-info text-center mt-3" style="display:none;">
                    <i class="fas fa-info-circle me-2"></i> Tidak ada data peminjam untuk periode ini.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="borrowersTable">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th>No.</th>
                                <th>Nama Peminjam</th>
                                <th>NIM</th>
                                <th>Detail Buku</th>
                            </tr>
                        </thead>
                        <tbody id="borrowersTableBody">
                        </tbody>
                    </table>
                </div>
                {{-- Pagination untuk modal --}}
                <div id="modalPagination" class="d-flex justify-content-center mt-3">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterTypeSelect = document.getElementById('filter_type');
            const dailyFilter = document.getElementById('dailyFilter');
            const yearlyFilter = document.getElementById('yearlyFilter');
            const exportCsvBtn = document.getElementById('exportCsvBtn');

            function toggleFilterInputs() {
                const selectedValue = filterTypeSelect.value;
                if (selectedValue === 'daily') {
                    dailyFilter.style.display = 'block';
                    yearlyFilter.style.display = 'none';
                } else {
                    dailyFilter.style.display = 'none';
                    yearlyFilter.style.display = 'block';
                }
            }
            toggleFilterInputs();
            filterTypeSelect.addEventListener('change', toggleFilterInputs);

            @if ($dataExists)
                const ctx = document.getElementById('peminjamanProdiChart').getContext('2d');
                const labels = @json($chartLabels);
                const datasets = @json($chartDatasets);

                const primaryColor = 'rgba(75, 192, 192, 0.8)';
                const secondaryColor = 'rgba(255, 99, 132, 0.8)'; // Warna baru untuk pengembalian
                const tertiaryColor = 'rgba(153, 102, 255, 0.8)'; // Warna untuk peminjam

                datasets[0].backgroundColor = primaryColor;
                datasets[0].borderColor = primaryColor.replace('0.8', '1');
                datasets[1].backgroundColor = secondaryColor;
                datasets[1].borderColor = secondaryColor.replace('0.8', '1');
                datasets[2].backgroundColor = tertiaryColor;
                datasets[2].borderColor = tertiaryColor.replace('0.8', '1');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Tren Peminjaman per Program Studi'
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Periode'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Jumlah'
                                }
                            }
                        }
                    }
                });
            @endif



            if (exportCsvBtn) {
                exportCsvBtn.addEventListener('click', function() {
                    const dataToExport = @json($allStatistics);

                    if (!dataToExport || dataToExport.length === 0) {
                        alert("Tidak ada data untuk diekspor.");
                        return;
                    }

                    // Ambil informasi filter untuk judul
                    const prodiName = document.getElementById('selected_prodi').options[document
                        .getElementById('selected_prodi').selectedIndex].text.trim();
                    const filterType = filterTypeSelect.value;
                    let periodText = '';

                    if (filterType === 'daily') {
                        const startDate = document.getElementById('start_date').value;
                        const endDate = document.getElementById('end_date').value;
                        periodText = `Periode Tanggal ${startDate} s.d. ${endDate}`;
                    } else {
                        const selectedYear = document.getElementById('selected_year').value;
                        periodText = `Tahun ${selectedYear}`;
                    }

                    const title = `Statistik Peminjaman per Program Studi: ${prodiName}, ${periodText}`;

                    let csv = [];
                    const delimiter = ';';
                    csv.push(title); // Tambahkan judul sebagai baris pertama
                    csv.push(''); // Tambahkan baris kosong untuk pemisah

                    let headers = ['Periode', 'Jumlah Buku Terpinjam',
                        'Jumlah Peminjam'
                    ];
                    csv.push(headers.join(delimiter));

                    dataToExport.forEach(row => {
                        let periode = '';
                        if (filterType === 'daily') {
                            periode = new Date(row.periode).toLocaleDateString('id-ID');
                        } else {
                            const date = new Date(row.periode);
                            periode = new Date(date.getFullYear(), date.getMonth(), 1)
                                .toLocaleDateString('id-ID', {
                                    month: 'long',
                                    year: 'numeric'
                                });
                        }

                        let rowData = [
                            periode,
                            row.jumlah_buku_terpinjam,
                            row.jumlah_peminjam_unik
                        ];
                        csv.push(rowData.join(delimiter));
                    });

                    const csvString = csv.join('\n');
                    const BOM = "\uFEFF";
                    const blob = new Blob([BOM + csvString], {
                        type: 'text/csv;charset=utf-8;'
                    });

                    const link = document.createElement("a");
                    const selectedProdiCode = document.getElementById('selected_prodi').value;
                    let fileName = `statistik_peminjaman_prodi_${prodiName.replace(/[^a-z0-9]/gi, '_')}`;

                    if (filterType === 'daily') {
                        const startDate = document.getElementById('start_date').value;
                        const endDate = document.getElementById('end_date').value;
                        fileName += `_${startDate}_${endDate}`;
                    } else {
                        const selectedYear = document.getElementById('selected_year').value;
                        fileName += `_${selectedYear}`;
                    }
                    fileName += `_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.csv`;

                    if (navigator.msSaveBlob) {
                        navigator.msSaveBlob(blob, fileName);
                    } else {
                        link.href = URL.createObjectURL(blob);
                        link.download = fileName;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(link.href);
                    }
                });
            }


            document.querySelectorAll('.view-borrowers-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const periode = this.dataset.periode;
                    const prodiCode = this.dataset.prodiCode;
                    const filterType = filterTypeSelect.value;
                    const prodiNameDisplay = document.getElementById('selected_prodi').options[
                        document.getElementById('selected_prodi').selectedIndex].text.trim();
                    const periodDisplay = this.closest('tr').querySelector('td:first-child')
                        .innerText.trim();

                    document.getElementById('modalProdiName').innerText = prodiNameDisplay;
                    document.getElementById('modalPeriod').innerText = periodDisplay;

                    fetchBorrowers(periode, prodiCode, filterType, 1);
                });
            });

            // function fetchBorrowers(periode, prodiCode, filterType, page) {
            //     const borrowersList = document.getElementById('borrowersList');
            //     const loadingSpinner = document.getElementById('loadingSpinner');
            //     const noDataMessage = document.getElementById('noDataMessage');
            //     const modalPagination = document.getElementById('modalPagination');

            //     borrowersList.innerHTML = '';
            //     modalPagination.innerHTML = '';
            //     loadingSpinner.style.display = 'block';
            //     noDataMessage.style.display = 'none';

            //     fetch(
            //             `{{ route('peminjaman.peminjamDetail') }}?periode=${periode}&filter_type=${filterType}&prodi_code=${prodiCode}&page=${page}`
            //         )
            //         .then(response => {
            //             if (!response.ok) {
            //                 throw new Error('Network response was not ok');
            //             }
            //             return response.json();
            //         })
            //         .then(data => {
            //             loadingSpinner.style.display = 'none';
            //             if (data.success && data.data && data.data.length > 0) {
            //                 // Buat list grup baru untuk setiap peminjam
            //                 const borrowerItems = data.data.map(borrower => {
            //                     // Buat detail buku sebagai card terpisah
            //                     const bookList = borrower.buku.map(book => {
            //                         let transaksiBadge = '';
            //                         if (book.transaksi === 'issue') {
            //                             transaksiBadge =
            //                                 '<span class="badge bg-primary ms-2">Pinjam Awal</span>';
            //                         } else if (book.transaksi === 'renew') {
            //                             transaksiBadge =
            //                                 '<span class="badge bg-warning ms-2">Perpanjangan</span>';
            //                         } else if (book.transaksi === 'return') {
            //                             transaksiBadge =
            //                                 '<span class="badge bg-success ms-2">Pengembalian</span>';
            //                         }

            //                         return `
        //                 <li class="list-group-item">
        //                     <i class="fas fa-book me-2"></i> ${book.title}
        //                     <span class="badge bg-secondary ms-2">${book.waktu_transaksi}</span>
        //                     ${transaksiBadge}
        //                 </li>
        //             `;
            //                     }).join('');

            //                     return `
        //             <li class="list-group-item d-flex justify-content-between align-items-center">
        //                 <div class="ms-2 me-auto">
        //                     <div class="fw-bold">${borrower.nama_peminjam} <span class="badge bg-dark ms-2">NIM: ${borrower.cardnumber}</span></div>
        //                     <ul class="list-group list-group-flush mt-2">
        //                         ${bookList || '<li class="list-group-item text-muted">Tidak ada buku yang tercatat.</li>'}
        //                     </ul>
        //                 </div>
        //             </li>
        //         `;
            //                 }).join('');

            //                 borrowersList.innerHTML = borrowerItems;
            //                 createModalPagination(data.currentPage, data.totalPages, periode, prodiCode,
            //                     filterType);

            //             } else {
            //                 noDataMessage.style.display = 'block';
            //             }
            //         })
            //         .catch(error => {
            //             console.error('Error fetching borrower details:', error);
            //             loadingSpinner.style.display = 'none';
            //             borrowersList.innerHTML =
            //                 '<li class="list-group-item text-danger">Gagal memuat data peminjam.</li>';
            //         });
            // }

            function fetchBorrowers(periode, prodiCode, filterType, page) {
                const borrowersTableBody = document.getElementById('borrowersTableBody');
                const loadingSpinner = document.getElementById('loadingSpinner');
                const noDataMessage = document.getElementById('noDataMessage');
                const modalPagination = document.getElementById('modalPagination');

                borrowersTableBody.innerHTML = '';
                modalPagination.innerHTML = '';
                loadingSpinner.style.display = 'block';
                noDataMessage.style.display = 'none';

                fetch(
                        `{{ route('peminjaman.peminjamDetail') }}?periode=${periode}&filter_type=${filterType}&prodi_code=${prodiCode}&page=${page}`
                    )
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        loadingSpinner.style.display = 'none';
                        if (data.success && data.data && data.data.length > 0) {
                            let rowNumber = (data.currentPage - 1) * data.perPage +
                                1;

                            data.data.forEach(borrower => {
                                const bookList = borrower.buku.map(book => {
                                    let transaksiBadge = '';
                                    if (book.transaksi === 'issue') {
                                        transaksiBadge =
                                            '<span class="badge bg-primary ms-2">Pinjam Awal</span>';
                                    } else if (book.transaksi === 'renew') {
                                        transaksiBadge =
                                            '<span class="badge bg-warning ms-2">Perpanjangan</span>';
                                    } else if (book.transaksi === 'return') {
                                        transaksiBadge =
                                            '<span class="badge bg-success ms-2">Pengembalian</span>';
                                    }

                                    return `
                                <li>
                                    <i class="fas fa-book me-2"></i> ${book.title}
                                    <span class="badge bg-secondary ms-2">${book.waktu_transaksi}</span>
                                    ${transaksiBadge}
                                </li>
                            `;
                                }).join('');

                                const rowHtml = `
                        <tr>
                            <td>${rowNumber++}</td>
                            <td>${borrower.nama_peminjam}</td>
                            <td>${borrower.cardnumber}</td>
                            <td>
                                <ul class="list-unstyled mb-0">
                                    ${bookList || '<li>Tidak ada buku yang tercatat.</li>'}
                                </ul>
                            </td>
                        </tr>
                    `;
                                borrowersTableBody.innerHTML += rowHtml;
                            });

                            createModalPagination(data.currentPage, data.totalPages, periode, prodiCode,
                                filterType);

                        } else {
                            noDataMessage.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching borrower details:', error);
                        loadingSpinner.style.display = 'none';
                        borrowersTableBody.innerHTML =
                            '<tr><td colspan="4" class="text-center text-danger">Gagal memuat data peminjam.</td></tr>';
                    });
            }

            function createModalPagination(currentPage, totalPages, periode, prodiCode, filterType) {
                const modalPagination = document.getElementById('modalPagination');
                if (totalPages <= 1) {
                    modalPagination.innerHTML = '';
                    return;
                }

                let paginationHtml = `<nav aria-label="Page navigation example">
                                                <ul class="pagination pagination-sm">`;

                paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                                                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                                </li>`;

                for (let i = 1; i <= totalPages; i++) {
                    paginationHtml += `<li class="page-item ${currentPage === i ? 'active' : ''}">
                                                <a class="page-link" href="#" data-page="${i}">${i}</a>
                                                </li>`;
                }

                paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                                                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                                </li>
                                            </ul>
                                            </nav>`;

                modalPagination.innerHTML = paginationHtml;

                modalPagination.querySelectorAll('.page-link').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const newPage = parseInt(this.dataset.page);
                        if (newPage > 0 && newPage <= totalPages) {
                            fetchBorrowers(periode, prodiCode, filterType, newPage);
                        }
                    });
                });
            }
        });
    </script>
@endpush
@endsection
