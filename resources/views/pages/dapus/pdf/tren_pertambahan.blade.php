<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tren Pertambahan Koleksi {{ $startYear }} - {{ $endYear }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; background: #fff; }

        .header { display: flex; align-items: center; border-bottom: 2px solid #0d6efd; padding-bottom: 12px; margin-bottom: 16px; }
        .header-logo { width: 60px; height: 60px; margin-right: 14px; }
        .header-logo img { width: 100%; height: 100%; object-fit: contain; }
        .header-text h1 { font-size: 14px; font-weight: bold; color: #0d6efd; }
        .header-text p { font-size: 9px; color: #666; margin-top: 2px; }

        .meta-box { background: #f0f4ff; border-left: 4px solid #0d6efd; padding: 8px 12px; margin-bottom: 14px; border-radius: 2px; }
        .meta-box p { font-size: 9px; color: #444; margin: 1px 0; }
        .meta-box strong { color: #0d6efd; }

        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        thead tr { background: #0d6efd; color: #fff; }
        thead th { padding: 7px 10px; text-align: center; font-size: 9px; font-weight: bold; letter-spacing: 0.3px; }
        tbody tr:nth-child(even) { background: #f8f9ff; }
        tbody tr:nth-child(odd) { background: #fff; }
        tbody td { padding: 6px 10px; border-bottom: 1px solid #e8ecf0; font-size: 9px; }
        tbody td.center { text-align: center; }
        tbody td.right { text-align: right; }

        tfoot tr { background: #e8ecf0; font-weight: bold; }
        tfoot td { padding: 7px 10px; font-size: 9px; border-top: 2px solid #0d6efd; }
        tfoot td.center { text-align: center; }

        .trend-up   { color: #198754; font-weight: bold; }
        .trend-down { color: #dc3545; font-weight: bold; }
        .trend-na   { color: #888; }

        .footer { margin-top: 18px; text-align: right; font-size: 8px; color: #999; border-top: 1px solid #dee2e6; padding-top: 6px; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        @if($logoBase64)
        <div class="header-logo">
            <img src="{{ $logoBase64 }}" alt="Logo">
        </div>
        @endif
        <div class="header-text">
            <h1>Laporan Tren Pertambahan Koleksi</h1>
            <p>Perpustakaan Universitas Muhammadiyah Surakarta</p>
            <p>Rentang Tahun: {{ $startYear }} &ndash; {{ $endYear }}</p>
        </div>
    </div>

    {{-- Meta info --}}
    <div class="meta-box">
        <p>Total Judul Baru: <strong>{{ number_format($data->sum('total_titles'), 0, ',', '.') }}</strong></p>
        <p>Total Eksemplar Baru: <strong>{{ number_format($data->sum('total_items'), 0, ',', '.') }}</strong></p>
        <p>Dicetak: <strong>{{ now()->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }} WIB</strong></p>
    </div>

    {{-- Tabel --}}
    <table>
        <thead>
            <tr>
                <th width="6%">No</th>
                <th width="22%">Tahun Masuk</th>
                <th width="22%">Judul Baru</th>
                <th width="22%">Eksemplar Baru</th>
                <th width="28%">Tren Pengadaan (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $row)
            @php
                $prevRow = $index > 0 ? $data[$index - 1] : null;
                $growthTitles = null;
                if ($prevRow && $prevRow->total_titles > 0) {
                    $growthTitles = round((($row->total_titles - $prevRow->total_titles) / $prevRow->total_titles) * 100, 1);
                }
            @endphp
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td class="center"><strong>{{ $row->year }}</strong></td>
                <td class="center">{{ number_format($row->total_titles, 0, ',', '.') }}</td>
                <td class="center">{{ number_format($row->total_items, 0, ',', '.') }}</td>
                <td class="center">
                    @if($growthTitles === null)
                        <span class="trend-na">— (tahun pertama)</span>
                    @elseif($growthTitles > 0)
                        <span class="trend-up">&#9650; +{{ $growthTitles }}%</span>
                    @elseif($growthTitles < 0)
                        <span class="trend-down">&#9660; {{ $growthTitles }}%</span>
                    @else
                        <span class="trend-na">0%</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="right">TOTAL</td>
                <td class="center">{{ number_format($data->sum('total_titles'), 0, ',', '.') }}</td>
                <td class="center">{{ number_format($data->sum('total_items'), 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dokumen ini digenerate secara otomatis oleh Sistem Akreditasi Perpustakaan UMS.
    </div>

</body>
</html>
