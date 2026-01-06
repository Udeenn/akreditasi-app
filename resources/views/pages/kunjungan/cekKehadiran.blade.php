@extends('layouts.app')

@section('title', 'Cek Kunjungan Per Bulan')

@section('content')
    <div class="container">
        {{-- Judul Halaman --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i> @yield('title')</h2>
        </div>

        {{-- Form Filter --}}
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Filter Pencarian</h5>
                <form method="GET" action="{{ route('kunjungan.cekKehadiran') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="cardnumber" class="form-label">Nomor Kartu Anggota (Cardnumber)</label>
                        <input type="text" name="cardnumber" id="cardnumber" class="form-control"
                            value="{{ request('cardnumber') }}" />
                    </div>
                    <div class="col-md-3">
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
                    {{-- Tombol Aksi --}}
                    <div class="col-md-5 d-flex justify-content-start align-items-end">
                        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search me-1"></i>
                            Lihat</button>
                        <button type="button" id="downloadPdfButton"
                            class="btn btn-danger me-2 {{ !request('cardnumber') ? 'disabled' : '' }}"><i
                                class="fas fa-file-pdf me-1"></i> Export PDF</button>
                        <button type="button" id="downloadExportDataButton"
                            class="btn btn-success {{ !request('cardnumber') ? 'disabled' : '' }}"><i
                                class="fas fa-file-csv me-1"></i> Export CSV</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Alert --}}
        @if (session('error'))
            <div class="alert alert-danger mt-4" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if ($pesan)
            <div class="alert alert-info text-center mt-4" role="alert">
                {{ $pesan }}
            </div>
        @endif

        {{-- Konten Hasil --}}
        @if (isset($fullBorrowerDetails) && $fullBorrowerDetails && $dataKunjungan->isNotEmpty())

            {{-- Grafik --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Grafik Kunjungan</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartKunjungan" height="120"></canvas>
                </div>
            </div>

            {{-- Detail Anggota dan Tabel --}}
            <div class="row">
                <div class="col-lg-4">
                    {{-- Informasi Anggota --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Informasi Anggota</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-nowrap">No. Kartu</dt>
                                <dd class="col-sm-7">{{ $fullBorrowerDetails->cardnumber }}</dd>

                                <dt class="col-sm-5 text-nowrap">Nama</dt>
                                <dd class="col-sm-7">{{ $fullBorrowerDetails->firstname }}
                                    {{ $fullBorrowerDetails->surname }}</dd>

                                <dt class="col-sm-5 text-nowrap">Email</dt>
                                <dd class="col-sm-7">{{ $fullBorrowerDetails->email ?: '-' }}</dd>

                                {{-- <dt class="col-sm-5 text-nowrap">Telepon</dt>
                                <dd class="col-sm-7">{{ $fullBorrowerDetails->phone ?: '-' }}</dd> --}}
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    {{-- Tabel Kunjungan --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Riwayat Kunjungan Bulanan</h5>
                        </div>
                        <div class="card-body pb-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped text-center"
                                    id="kunjunganTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="text-nowrap">No.</th>
                                            <th class="text-nowrap">Bulan Tahun</th>
                                            <th class="text-nowrap">Jumlah Kunjungan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataKunjungan as $row)
                                            <tr>
                                                <td>{{ ($dataKunjungan->currentPage() - 1) * $dataKunjungan->perPage() + $loop->iteration }}
                                                </td>
                                                <td>
                                                    @php
                                                        try {
                                                            $dateString = (string) $row->tahun_bulan;
                                                            if (!empty($dateString)) {
                                                                echo \Carbon\Carbon::createFromFormat(
                                                                    'Ym',
                                                                    $dateString,
                                                                )->format('M Y');
                                                            } else {
                                                                echo '-';
                                                            }
                                                        } catch (\Exception $e) {
                                                            echo $row->tahun_bulan ?? 'Invalid Date';
                                                        }
                                                    @endphp
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="ms-auto">{{ $row->jumlah_kunjungan }}</span>
                                                        <button type="button"
                                                            class="btn btn-sm btn-info ms-auto btn-modal-lokasi"
                                                            data-tahun-bulan="{{ $row->tahun_bulan }}"
                                                            data-cardnumber="{{ $cardnumber }}">
                                                            <i class="fas fa-map-marker-alt me-1"></i> Lokasi
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{-- Footer: Pagination dan Total --}}
                        <div class="card-footer">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h5 class="mb-0 me-3">Total Kunjungan: <span
                                        class="badge bg-primary">{{ $totalKunjunganSum ?? 0 }}</span></h5>
                                <div class="pagination-wrapper">
                                    {{ $dataKunjungan->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal Lokasi --}}
    <div class="modal fade" id="lokasiModal" tabindex="-1" aria-labelledby="lokasiModalLabel">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lokasiModalLabel"><i class="fas fa-map-marked-alt me-2"></i> Detail Lokasi
                        Kunjungan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Bulan Tahun:</strong> <span id="modalBulanTahun"></span></p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped text-center">
                            <thead class="table">
                                <tr>
                                    <th>No.</th>
                                    <th>Waktu Kunjungan</th>
                                    <th>Lokasi</th>
                                </tr>
                            </thead>
                            <tbody id="lokasiTableBody"></tbody>
                        </table>
                    </div>
                    <div id="paginationLinks" class="d-flex justify-content-center mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript tidak berubah --}}
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

                            const headers = ['No.', 'Bulan Tahun', 'Jumlah Kunjungan'];
                            csv.push(headers.join(delimiter));

                            // (PERUBAHAN DI SINI)
                            result.data.forEach((row, index) => {

                                let formattedDate =
                                    "Data Tidak Valid";
                                if (row.tahun_bulan) {
                                    try {
                                        const tahunBulanRaw = row.tahun_bulan.toString();
                                        const year = tahunBulanRaw.substring(0, 4);
                                        const month = tahunBulanRaw.substring(4, 6);
                                        const dateObj = new Date(year, month - 1);

                                        formattedDate = dateObj.toLocaleString('id-ID', {
                                            month: 'short',
                                            year: 'numeric'
                                        });
                                    } catch (e) {
                                        // Jika formatnya bukan YYYYMM, tangkap error
                                        console.error("Error parsing date:", row.tahun_bulan,
                                            e);
                                        formattedDate = "Format Tanggal Salah";
                                    }
                                } else {
                                    // Jika row.tahun_bulan memang null/undefined dari server
                                    console.warn("Ditemukan data 'tahun_bulan' yang kosong:",
                                        row);
                                }

                                // 3. Susun baris data (tetap masukkan barisnya)
                                const rowData = [
                                    index + 1,
                                    `"${formattedDate.replace(/"/g, '""')}"`,
                                    // Tambahkan pengecekan untuk jumlah_kunjungan juga
                                    row.jumlah_kunjungan || 0
                                ];
                                csv.push(rowData.join(delimiter));
                            });
                            // (AKHIR PERUBAHAN)

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
                    type: 'line',
                    data: {
                        labels: {!! json_encode(
                            $dataKunjungan->pluck('tahun_bulan')->map(function ($v) {
                                try {
                                    // Pastikan ada data dan ubah ke string sebelum diformat
                                    return $v ? \Carbon\Carbon::createFromFormat('Ym', (string) $v)->format('M Y') : '-';
                                } catch (\Exception $e) {
                                    // Jika error format, tampilkan data aslinya saja agar tidak error 500
                                    return (string) $v;
                                }
                            }),
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
                                beginAtZero: true,
                                ticks: {
                                    // Pastikan ticks adalah integer jika jumlahnya kecil
                                    precision: 0
                                }
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
                                tdWaktu.textContent = new Date(lokasi.visit_date).toLocaleString(
                                    'id-ID', {
                                        dateStyle: 'medium',
                                        timeStyle: 'short'
                                    }); // Format Indonesia
                                tdLokasi.textContent = lokasi.visit_location;

                                tr.appendChild(tdNo);
                                tr.appendChild(tdWaktu);
                                tr.appendChild(tdLokasi);
                                lokasiTableBody.appendChild(tr);
                            });

                            // Buat tombol-tombol pagination
                            const pageData = result.pagination_data;
                            let navHtml = `<nav><ul class="pagination pagination-sm">`; // pagination-sm

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
                                    if (newPage) { // Pastikan newPage tidak null (misal dari tombol disabled)
                                        fetchLokasiData(cardnumber, tahunBulan, newPage);
                                    }
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

            // document.querySelectorAll('.btn-modal-lokasi').forEach(button => {
            //     button.addEventListener('click', function() {
            //         const cardnumber = this.getAttribute('data-cardnumber');
            //         const tahunBulan = this.getAttribute('data-tahun-bulan');
            //         const lokasiModal = new bootstrap.Modal(document.getElementById('lokasiModal'));
            //         lokasiModal.show();
            //         fetchLokasiData(cardnumber, tahunBulan);
            //     });
            // });
            document.querySelectorAll('.btn-modal-lokasi').forEach(button => {
                button.addEventListener('click', function() {
                    const cardnumber = this.getAttribute('data-cardnumber');
                    const tahunBulan = this.getAttribute('data-tahun-bulan');
                    const modalElement = document.getElementById('lokasiModal');
                    const lokasiModal = bootstrap.Modal.getOrCreateInstance(modalElement);

                    lokasiModal.show();
                    fetchLokasiData(cardnumber, tahunBulan);
                });
            });
        });
    </script>
@endsection
