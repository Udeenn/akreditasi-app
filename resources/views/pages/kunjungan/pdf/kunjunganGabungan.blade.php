<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kunjungan Keseluruhan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
            border: none;
        }
        .header td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo {
            width: 80px;
            height: auto;
        }
        .header-text {
            text-align: center;
        }
        .header-text h2 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header-text h3 {
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .header-text p {
            margin: 5px 0 0 0;
            font-size: 11px;
        }
        .report-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .report-periode {
            text-align: center;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .summary-box {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .summary-box th, .summary-box td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .summary-box th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .chart-container {
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
        }
        .chart-container img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            padding: 5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }
        .data-table th {
            background-color: #f4f4f4;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
        }
        .signature-area {
            float: right;
            text-align: center;
            width: 200px;
        }
        .signature-space {
            height: 80px;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT -->
    <div class="header">
        <table cellspacing="0" cellpadding="0">
            <tr>
                <td width="15%">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td width="70%" class="header-text">
                    <h2>UNIVERSITAS MUHAMMADIYAH SURAKARTA</h2>
                    <h3>PERPUSTAKAAN PUSAT</h3>
                    <p>Jl. A. Yani, Mendungan, Pabelan, Kec. Kartasura, Kabupaten Sukoharjo, Jawa Tengah 57162</p>
                </td>
                <td width="15%"></td>
            </tr>
        </table>
    </div>

    <!-- JUDUL LAPORAN -->
    <div class="report-title">
        LAPORAN REKAPITULASI KUNJUNGAN KESELURUHAN
    </div>
    <div class="report-periode">
        {{ $periodeText }} <br>
        <span style="font-weight:normal; font-size:11px;">Lokasi: {{ $displayLokasi }}</span>
    </div>

    <!-- RINGKASAN STATISTIK -->
    <table class="summary-box">
        <tr>
            <th>Total Kunjungan</th>
            <th>Rata-rata Kunjungan (per {{ $filterType == 'daily' ? 'Hari' : 'Bulan' }})</th>
        </tr>
        <tr>
            <td>{{ number_format($totalKunjungan, 0, ',', '.') }} Pemustaka</td>
            <td>{{ number_format($rerataKunjungan, 2, ',', '.') }} Pemustaka</td>
        </tr>
    </table>

    <!-- GRAFIK CHART.JS -->
    @if($chartImage)
        <div class="chart-container">
            <h4>Grafik Tren Kunjungan</h4>
            <img src="{{ $chartImage }}" alt="Grafik Kunjungan">
        </div>
    @endif

    <!-- TABEL DATA -->
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>{{ $filterType == 'daily' ? 'Tanggal' : 'Bulan' }}</th>
                <th>Total Kunjungan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dataToExport as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if($filterType == 'daily')
                            {{ \Carbon\Carbon::parse($row->periode)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                        @else
                            {{ \Carbon\Carbon::parse($row->periode)->locale('id')->isoFormat('MMMM YYYY') }}
                        @endif
                    </td>
                    <td>{{ number_format($row->jumlah, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Tidak ada data untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER / TTD -->
    <div class="footer">
        <div class="signature-area">
            <p>Surakarta, {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM YYYY') }}</p>
            <p>Mengetahui,</p>
            <div class="signature-space"></div>
            <p><strong>Kepala Perpustakaan</strong></p>
        </div>
    </div>

</body>
</html>
