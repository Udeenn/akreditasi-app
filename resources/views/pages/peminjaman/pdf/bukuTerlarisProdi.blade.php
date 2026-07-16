<!DOCTYPE html>
<html>
<head>
    <title>Buku Terlaris Prodi - {{ $namaProdi }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header img { height: 60px; float: left; }
        .header-text { margin-left: 70px; }
        .header h3, .header h4, .header p { margin: 2px 0; }
        .title { text-align: center; margin-top: 15px; margin-bottom: 15px; font-weight: bold; font-size: 14pt; }
        .subtitle { text-align: center; margin-bottom: 20px; font-size: 11pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f4f4f4; text-align: center; font-weight: bold; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; text-align: right; font-size: 10pt; }
    </style>
</head>
<body>

    <div class="header">
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo">
        @endif
        <div class="header-text">
            <h3>UNIVERSITAS MUHAMMADIYAH SURAKARTA</h3>
            <h4>PERPUSTAKAAN</h4>
            <p>Jl. A. Yani Tromol Pos I Pabelan Kartasura Telp. (0271) 717417</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="title">
        LAPORAN BUKU TERLARIS BERDASARKAN PRODI
    </div>
    <div class="subtitle">
        <strong>Program Studi:</strong> {{ $namaProdi }}<br>
        <strong>Periode:</strong> {{ $periodeText }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 40%">Judul Buku</th>
                <th style="width: 25%">Pengarang</th>
                <th style="width: 15%">Klasifikasi</th>
                <th style="width: 15%">Total Transaksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $book)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $book->title }}</td>
                    <td>{{ $book->author ?? '-' }}</td>
                    <td class="text-center">{{ $book->cn_class ?? '-' }}</td>
                    <td class="text-center">{{ number_format($book->total_peminjaman) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada data transaksi peminjaman.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Surakarta, {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM YYYY') }}</p>
        <br><br><br>
        <p>( _________________________ )</p>
        <p>Petugas Perpustakaan</p>
    </div>

</body>
</html>
