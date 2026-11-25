<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Pengadaan Koleksi {{ $startYear }}-{{ $endYear }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.5px;
            color: #111;
            background: #fff;
            padding: 20px 28px;
        }

        /* HEADER */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 10px; }
        .logo-cell { width: 60px; vertical-align: middle; }
        .logo-cell img { width: 52px; object-fit: contain; }
        .title-cell { vertical-align: middle; padding-left: 12px; }
        .doc-title { font-size: 13px; font-weight: bold; color: #111; }
        .doc-sub { font-size: 8.5px; color: #555; margin-top: 2px; }
        .meta-cell { vertical-align: middle; text-align: right; font-size: 8px; color: #555; line-height: 1.7; }

        /* DIVIDER */
        hr { border: none; border-top: 1px solid #ddd; margin: 10px 0; }

        /* SUMMARY ROW */
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary-table td { font-size: 8.5px; color: #555; padding: 4px 0; }
        .summary-table td b { color: #111; font-size: 9.5px; }

        /* TABLE */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead th {
            background: #111;
            color: #fff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 10px;
            text-align: center;
        }
        .data-table tbody tr:nth-child(even) { background: #f7f7f7; }
        .data-table tbody tr:nth-child(odd)  { background: #fff; }
        .data-table tbody td {
            padding: 6px 10px;
            font-size: 9px;
            border-bottom: 1px solid #e5e5e5;
            text-align: center;
            color: #222;
        }
        .data-table tbody td.no { color: #999; font-size: 8px; }
        .data-table tbody td.year { font-weight: bold; color: #111; }

        .trend-up   { color: #1a6e3c; font-weight: bold; }
        .trend-down { color: #9b1c1c; font-weight: bold; }
        .trend-na   { color: #999; font-style: italic; }

        .data-table tfoot td {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 9px;
            padding: 7px 10px;
            text-align: center;
            border-top: 2px solid #111;
            color: #111;
        }
        .data-table tfoot td.label { text-align: right; }

        /* FOOTER */
        .doc-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
            font-size: 7.5px;
            color: #888;
        }
        .doc-footer table { width: 100%; border-collapse: collapse; }
        .doc-footer .fl { vertical-align: middle; }
        .doc-footer .fr { text-align: right; vertical-align: middle; }
    </style>
</head>
<body>

{{-- HEADER --}}
<table class="header-table">
    <tr>
        <td class="logo-cell">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo">
            @endif
        </td>
        <td class="title-cell">
            <div class="doc-title">Laporan Pengadaan Koleksi</div>
            <div class="doc-sub">Perpustakaan Universitas Muhammadiyah Surakarta</div>
        </td>
        <td class="meta-cell">
            Periode: <b>{{ $startYear }} &ndash; {{ $endYear }}</b><br>
            Dicetak: {{ now()->locale('id')->isoFormat('D MMMM YYYY') }}<br>
            Pukul: {{ now()->format('H:i') }} WIB
        </td>
    </tr>
</table>

{{-- SUMMARY --}}
<table class="summary-table">
    <tr>
        <td>Total Judul Baru: <b>{{ number_format($data->sum('total_titles'), 0, ',', '.') }}</b></td>
        <td>Total Eksemplar Baru: <b>{{ number_format($data->sum('total_items'), 0, ',', '.') }}</b></td>
        <td style="text-align:right; color:#999;">{{ $endYear - $startYear + 1 }} tahun data</td>
    </tr>
</table>

{{-- TABLE --}}
<table class="data-table">
    <thead>
        <tr>
            <th style="width:6%;">No</th>
            <th style="width:20%;">Tahun Masuk</th>
            <th style="width:24%;">Judul Baru</th>
            <th style="width:24%;">Eksemplar Baru</th>
            <th style="width:26%;">Tren Pengadaan</th>
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
            <td class="no">{{ $index + 1 }}</td>
            <td class="year">{{ $row->year }}</td>
            <td>{{ number_format($row->total_titles, 0, ',', '.') }}</td>
            <td>{{ number_format($row->total_items, 0, ',', '.') }}</td>
            <td>
                @if($growthTitles === null)
                    <span class="trend-na">— tahun pertama</span>
                @elseif($growthTitles > 0)
                    <span class="trend-up">&#9650; +{{ $growthTitles }}%</span>
                @elseif($growthTitles < 0)
                    <span class="trend-down">&#9660; {{ $growthTitles }}%</span>
                @else
                    <span style="color:#555;">0%</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="label">Total</td>
            <td>{{ number_format($data->sum('total_titles'), 0, ',', '.') }}</td>
            <td>{{ number_format($data->sum('total_items'), 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- FOOTER --}}
<div class="doc-footer">
    <table>
        <tr>
            <td class="fl">Digenerate otomatis oleh Sistem Informasi Akreditasi Perpustakaan UMS.</td>
            <td class="fr">data-lib.ums.ac.id</td>
        </tr>
    </table>
</div>

</body>
</html>
