@extends('layouts.app')

@section('content')
@section('title', 'Cek Kunjungan Per Bulan')
<div class="container">
    <h4>Cek Kunjungan Per Bulan</h4>
    <form method="GET" action="{{ route('kunjungan.cekKehadiran') }}" class="row g-3 mb-4 align-items-end">
        <div class="col-md-3">
            <label for="cardnumber" class="form-label">Nomor Kartu Anggota (Cardnumber)</label>
            <input type="text" name="cardnumber" id="cardnumber" class="form-control"
                value="{{ request('cardnumber') }}" />
        </div>
        <div class="col-md-2">
            <label for="tahun" class="form-label">Tahun</label>
            <select name="tahun" id="tahun" class="form-control">
                <option value="">Semua Tahun</option>
                @php
                    $currentYear = date('Y');
                    for ($year = $currentYear; $year >= 2020; $year--) {
                        echo "<option value='{$year}' " .
                            (request('tahun') == $year ? 'selected' : '') .
                            ">{$year}</option>";
                    }
                @endphp
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary w-100">Lihat</button>
        </div>
        <div class="col-md-2">
            <button type="button" id="downloadPdfButton"
                class="btn btn-danger w-100 {{ !request('cardnumber') ? 'disabled' : '' }}">Export ke PDF</button>
        </div>
        <div class="col-md-3">
            <button type="button" id="downloadExportDataButton"
                class="btn btn-success w-100 {{ !request('cardnumber') ? 'disabled' : '' }}">Export ke CSV</button>
        </div>
    </form>

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($pesan)
        <div class="alert alert-info text-center" role="alert">
            {{ $pesan }}
        </div>
    @endif

    @if (isset($fullBorrowerDetails) && $fullBorrowerDetails && $dataKunjungan->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <canvas id="chartKunjungan" height="100"></canvas>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                Informasi Anggota
            </div>
            <div class="card-body">
                <p><strong>Nomor Kartu Anggota:</strong> {{ $fullBorrowerDetails->cardnumber }}</p>
                <p><strong>Nama:</strong> {{ $fullBorrowerDetails->firstname }} {{ $fullBorrowerDetails->surname }}</p>
                <p><strong>Email:</strong> {{ $fullBorrowerDetails->email }}</p>
                <p><strong>Telepon:</strong> {{ $fullBorrowerDetails->phone }}</p>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped text-center" id="kunjunganTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Bulan Tahun</th>
                                <th>Jumlah Kunjungan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dataKunjungan as $row)
                                <tr>
                                    <td>{{ ($dataKunjungan->currentPage() - 1) * $dataKunjungan->perPage() + $loop->iteration }}
                                    </td>
                                    <td>{{ \Carbon\Carbon::createFromFormat('Ym', $row->tahun_bulan)->format('M Y') }}
                                    </td>
                                    <td>
                                        {{ $row->jumlah_kunjungan }}
                                        <button type="button" class="btn btn-sm btn-info float-end btn-modal-lokasi"
                                            data-tahun-bulan="{{ $row->tahun_bulan }}"
                                            data-cardnumber="{{ $cardnumber }}">
                                            <i class="fas fa-map-marker-alt"></i> Lokasi
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center mt-3 row">
                        {{ $dataKunjungan->links() }}
                        <p class="mt-3">Total Keseluruhan Kunjungan: {{ $dataKunjungan->sum('jumlah_kunjungan') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
<div class="modal fade" id="lokasiModal" tabindex="-1" aria-labelledby="lokasiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lokasiModalLabel">Detail Lokasi Kunjungan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Bulan Tahun:</strong> <span id="modalBulanTahun"></span></p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Waktu Kunjungan</th>
                                <th>Lokasi</th>
                            </tr>
                        </thead>
                        <tbody id="lokasiTableBody"></tbody>
                    </table>
                </div>
                <div id="paginationLinks" class="d-flex justify-content-center"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const downloadPdfButton = document.getElementById("downloadPdfButton");
        if (downloadPdfButton) {
            downloadPdfButton.addEventListener("click", function() {
                const cardnumber = document.getElementById('cardnumber').value;
                const tahun = document.getElementById('tahun').value;
                if (cardnumber) {
                    window.open(
                        `{{ route('kunjungan.export-pdf') }}?cardnumber=${cardnumber}&tahun=${tahun}`,
                        '_blank'
                    );
                } else {
                    alert("Mohon masukkan Nomor Kartu Anggota terlebih dahulu.");
                }
            });
        }

        const downloadExportDataButton = document.getElementById("downloadExportDataButton");
        if (downloadExportDataButton) {
            downloadExportDataButton.addEventListener("click", async function() {
                const cardnumber = document.getElementById('cardnumber').value;
                const tahun = document.getElementById('tahun').value;

                if (!cardnumber) {
                    alert("Mohon masukkan Nomor Kartu Anggota terlebih dahulu.");
                    return;
                }
                try {
                    const response = await fetch(
                        `{{ route('kunjungan.get_export_data') }}?cardnumber=${cardnumber}&tahun=${tahun}`
                    );
                    const result = await response.json();
                    if (response.ok) {
                        if (result.data.length === 0) {
                            alert("Tidak ada data untuk diekspor.");
                            return;
                        }

                        let csv = [];
                        const delimiter = ';';
                        const BOM = "\uFEFF";

                        const headers = ['Bulan Tahun', 'Jumlah Kunjungan'];
                        csv.push(headers.join(delimiter));

                        result.data.forEach(row => {
                            const rowData = [
                                `"${row.bulan_tahun.replace(/"/g, '""')}"`,
                                row.jumlah_kunjungan
                            ];
                            csv.push(rowData.join(delimiter));
                        });

                        const csvString = csv.join('\n');
                        const blob = new Blob([BOM + csvString], {
                            type: 'text/csv;charset=utf-8;'
                        });

                        const link = document.createElement("a");
                        const fileName =
                            `laporan_kehadiran_${result.cardnumber}_${(result.borrower_name || 'unknown').replace(/\s+/g, '_').toLowerCase()}_${new Date().toISOString().slice(0,10).replace(/-/g,'')}.csv`;

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
                    } else {
                        alert(result.error || "Terjadi kesalahan saat mengambil data export.");
                    }
                } catch (error) {
                    console.error('Error fetching export data:', error);
                    alert("Terjadi kesalahan teknis saat mencoba mengekspor data.");
                }
            });
        }

        @if (isset($fullBorrowerDetails) && $fullBorrowerDetails && $dataKunjungan->isNotEmpty())
            const chartCanvas = document.getElementById('chartKunjungan');
            const chart = chartCanvas.getContext('2d');

            const dataChart = new Chart(chart, {
                type: 'bar',
                data: {
                    labels: {!! json_encode(
                        $dataKunjungan->pluck('tahun_bulan')->map(fn($v) => \Carbon\Carbon::createFromFormat('Ym', $v)->format('M Y')),
                    ) !!},
                    datasets: [{
                        label: 'Jumlah Kunjungan per Bulan',
                        data: {!! json_encode($dataKunjungan->pluck('jumlah_kunjungan')) !!},
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgb(75, 192, 192)',
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        @endif

        async function fetchLokasiData(cardnumber, tahunBulan, page = 1) {
            const modalBulanTahun = document.getElementById('modalBulanTahun');
            const lokasiTableBody = document.getElementById('lokasiTableBody');
            const paginationLinks = document.getElementById('paginationLinks');

            modalBulanTahun.textContent = `Memuat data...`;
            lokasiTableBody.innerHTML = '<tr><td colspan="3" class="text-center">Loading...</td></tr>';
            paginationLinks.innerHTML = '';

            try {
                const response = await fetch(
                    `{{ route('kunjungan.get_lokasi_detail') }}?cardnumber=${cardnumber}&tahun_bulan=${tahunBulan}&page=${page}`
                );
                const result = await response.json();

                if (response.ok) {
                    modalBulanTahun.textContent = result.bulan_tahun_formatted;
                    lokasiTableBody.innerHTML = '';

                    if (result.lokasi.length > 0) {
                        result.lokasi.forEach((lokasi, index) => {
                            const tr = document.createElement('tr');
                            const tdNo = document.createElement('td');
                            const tdWaktu = document.createElement('td');
                            const tdLokasi = document.createElement('td');

                            const startCount = (result.pagination_data.current_page - 1) * result
                                .pagination_data.per_page;
                            tdNo.textContent = startCount + index + 1;
                            tdWaktu.textContent = new Date(lokasi.visit_date).toLocaleString();
                            tdLokasi.textContent = lokasi.visit_location;

                            tr.appendChild(tdNo);
                            tr.appendChild(tdWaktu);
                            tr.appendChild(tdLokasi);
                            lokasiTableBody.appendChild(tr);
                        });

                        // Buat tombol-tombol pagination
                        const pageData = result.pagination_data;
                        let navHtml = `<nav><ul class="pagination">`;

                        // Tombol Previous
                        navHtml += `<li class="page-item ${!pageData.prev_page_url ? 'disabled' : ''}">
                                        <a class="page-link" href="#" data-page="${pageData.current_page - 1}" data-cardnumber="${cardnumber}" data-tahun-bulan="${tahunBulan}">Previous</a>
                                    </li>`;

                        // Halaman
                        for (let i = 1; i <= pageData.last_page; i++) {
                            navHtml += `<li class="page-item ${i === pageData.current_page ? 'active' : ''}">
                                            <a class="page-link" href="#" data-page="${i}" data-cardnumber="${cardnumber}" data-tahun-bulan="${tahunBulan}">${i}</a>
                                        </li>`;
                        }

                        // Tombol Next
                        navHtml += `<li class="page-item ${!pageData.next_page_url ? 'disabled' : ''}">
                                        <a class="page-link" href="#" data-page="${pageData.current_page + 1}" data-cardnumber="${cardnumber}" data-tahun-bulan="${tahunBulan}">Next</a>
                                    </li>`;

                        navHtml += `</ul></nav>`;
                        paginationLinks.innerHTML = navHtml;

                        paginationLinks.querySelectorAll('.page-link').forEach(link => {
                            link.addEventListener('click', (e) => {
                                e.preventDefault();
                                const newPage = e.target.getAttribute('data-page');
                                fetchLokasiData(cardnumber, tahunBulan, newPage);
                            });
                        });
                    } else {
                        lokasiTableBody.innerHTML =
                            '<tr><td colspan="3" class="text-muted text-center">Tidak ada data lokasi.</td></tr>';
                    }
                } else {
                    lokasiTableBody.innerHTML =
                        `<tr><td colspan="3" class="text-danger text-center">Terjadi kesalahan: ${result.error || 'Unknown Error'}</td></tr>`;
                }
            } catch (error) {
                console.error('Error fetching location data:', error);
                lokasiTableBody.innerHTML =
                    `<tr><td colspan="3" class="text-danger text-center">Terjadi kesalahan teknis.</td></tr>`;
            }
        }

        document.querySelectorAll('.btn-modal-lokasi').forEach(button => {
            button.addEventListener('click', function() {
                const cardnumber = this.getAttribute('data-cardnumber');
                const tahunBulan = this.getAttribute('data-tahun-bulan');
                const lokasiModal = new bootstrap.Modal(document.getElementById('lokasiModal'));
                lokasiModal.show();
                fetchLokasiData(cardnumber, tahunBulan);
            });
        });
    });
</script>
@endsection
