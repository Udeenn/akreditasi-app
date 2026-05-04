<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keterpakaian Koleksi</title>
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
            table-layout: fixed;
            word-wrap: break-word;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 8px; 
        }
        .data-table th {
            background-color: #f4f4f4;
            font-size: 9px;
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
                    <h3>UPT PERPUSTAKAAN DAN LAYANAN DIGITAL</h3>
                    <p>Jl. A. Yani, Mendungan, Pabelan, Kec. Kartasura, Kabupaten Sukoharjo, Jawa Tengah 57162</p>
                </td>
                <td width="15%"></td>
            </tr>
        </table>
    </div>

    <!-- JUDUL LAPORAN -->
    <div class="report-title">
        LAPORAN STATISTIK KETERPAKAIAN KOLEKSI
    </div>
    <div class="report-periode">
        {{ $periodeText }}
    </div>

    <!-- RINGKASAN STATISTIK -->
    <table class="summary-box">
        <tr>
            <th>Total Keterpakaian</th>
            <th>Rata-rata Keterpakaian (per {{ $filterType == 'daily' ? 'Hari' : 'Bulan' }})</th>
            <th>Kategori Paling Populer</th>
        </tr>
        <tr>
            <td>{{ number_format($totalPenggunaan, 0, ',', '.') }} Transaksi</td>
            <td>{{ number_format($rerataPenggunaan, 2, ',', '.') }} Transaksi</td>
            <td>
                {{ $kategoriPopuler['nama'] }} 
                ({{ number_format($kategoriPopuler['jumlah'], 0, ',', '.') }})
            </td>
        </tr>
    </table>

    <!-- GRAFIK CHART.JS -->
    @if($chartImage)
        <div class="chart-container">
            <h4>Grafik Tren Keterpakaian Berdasarkan Kategori</h4>
            <img src="{{ $chartImage }}" alt="Grafik Keterpakaian Koleksi">
        </div>
    @endif

    <!-- TABEL DATA -->
    <table class="data-table">
        <thead>
            @if(count($listKategori) > 0)
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">{{ $filterType == 'daily' ? 'Tanggal' : 'Bulan' }}</th>
                    <th colspan="{{ count($listKategori) }}">Berdasarkan Kategori (Tipe Koleksi)</th>
                    <th rowspan="2">Total Keterpakaian</th>
                </tr>
                <tr>
                    @foreach($listKategori as $kat)
                        <th style="font-size: 8px;">{{ $kat }}</th>
                    @endforeach
                </tr>
            @else
                <tr>
                    <th>No</th>
                    <th>{{ $filterType == 'daily' ? 'Tanggal' : 'Bulan' }}</th>
                    <th>Berdasarkan Kategori (Tipe Koleksi)</th>
                    <th>Total Keterpakaian</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @forelse($dataTabel as $index => $row)
                @php
                    $rowTotal = 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="text-align: left;">
                        @if($filterType == 'daily')
                            {{ \Carbon\Carbon::parse($row['periode'])->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                        @else
                            {{ \Carbon\Carbon::parse($row['periode'])->locale('id')->isoFormat('MMMM YYYY') }}
                        @endif
                    </td>
                    @foreach($listKategori as $kat)
                        @php
                            $val = $row[$kat] ?? 0;
                            $rowTotal += $val;
                        @endphp
                        <td>{{ number_format($val, 0, ',', '.') }}</td>
                    @endforeach
                    <td style="font-weight: bold;">{{ number_format($rowTotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($listKategori) + 3 }}">Tidak ada data keterpakaian.</td>
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
