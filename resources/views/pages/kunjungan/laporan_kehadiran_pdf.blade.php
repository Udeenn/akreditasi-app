<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Kehadiran Kunjungan</title>
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/x-icon">
    <style>
        @page {
            margin: 28px;
        }

        /* body {
            /* font-family: 'Arial', serif; */
        /* font-size: 11px;
        color: #333;
        line-height: 1.4; */
        /* } */

        */ body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }

        .header-section {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-top: 30px;
            margin-bottom: 5px;
        }

        .header-section td {
            vertical-align: top;
            padding: 0;
        }

        .header-section .logo-left,
        .header-section .logo-right {
            width: 30%;
            text-align: center;
        }

        .header-section .logo-img {
            height: 50px;
            /* Ukuran logo disesuaikan */
            width: auto;
        }

        .header-section .center-content {
            width: 70%;
            text-align: center;
        }

        .header-section .institution-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            /* margin: 0 0 2px 0; */
            color: #1a1a1a;
        }

        .header-section .institution-details {
            font-size: 10px;
            color: #555;
            margin: 0;
            line-height: 1.2;
        }

        .divider-hr {
            border: none;
            height: 1px;
            background-color: #000;
            margin: 10px 40px;
        }

        .report-title-section {
            text-align: center;
            margin: 0 40px 15px 40px;
        }

        .report-title-section .main-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 5px 0;
            color: #1a1a1a;
            margin-top: 30px;
        }

        .report-title-section .period-info {
            font-size: 11px;
            color: #666;
            margin: 0;
        }

        .borrower-details {
            font-size: 12px;
            margin: 20px 40px 35px 40px;
        }

        .borrower-details .detail-item {
            margin-bottom: 4px;
            margin-top: 10px;
        }

        .borrower-details .label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }

        .table {
            width: calc(100% - 80px);
            border-collapse: collapse;
            margin: 15px 40px;
        }

        .table th,
        .table td {
            border: 1px solid #ccc;
            padding: 8px 12px;
            text-align: left;
        }

        .table thead th {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: center;
            color: #444;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table td.num {
            width: 40px;
            text-align: center;
        }

        .table td.right {
            text-align: center;
        }

        .table tfoot td {
            background-color: #e2e6ea;
            font-weight: bold;
            border-top: 2px solid #bbb;
        }

        .signature-section {
            width: 100%;
            display: table;
            margin-top: 40px;
        }

        .signature-section td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }

        .signature-date {
            margin-bottom: 60px;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 9px;
            color: #999;
            text-align: center;
            border-top: 1px dashed #ddd;
            padding-top: 10px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @php
        $LOGO_LEFT_FILENAME = 'ums.png';
        $LOGO_RIGHT_FILENAME = 'logo4a.png';

        function logo_base64_from_public_img($filename)
        {
            if (!is_string($filename) || $filename === '') {
                return null;
            }
            $filename = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filename);
            $filename = preg_replace('#^public' . preg_quote(DIRECTORY_SEPARATOR, '#') . '#i', '', $filename);
            if (stripos($filename, 'img' . DIRECTORY_SEPARATOR) !== 0) {
                $filename = 'img' . DIRECTORY_SEPARATOR . $filename;
            }
            $abs = public_path($filename);
            if (!file_exists($abs)) {
                return null;
            }

            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
            $mimes = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
            ];
            $mime = $mimes[$ext] ?? 'image/png';
            $data = @file_get_contents($abs);
            if ($data === false) {
                return null;
            }

            return 'data:' . $mime . ';base64,' . base64_encode($data);
        }

        $logoLeftB64 = logo_base64_from_public_img($LOGO_LEFT_FILENAME);
        $logoRightB64 = logo_base64_from_public_img($LOGO_RIGHT_FILENAME);

        function ym_to_text($ym)
        {
            $s = (string) $ym;
            if (strlen($s) === 5) {
                $year = substr($s, 0, 4);
                $month = '0' . substr($s, 4, 1);
            } else {
                $year = substr($s, 0, 4);
                $month = substr($s, 4, 2);
            }
            $bulan = [
                '',
                'Januari',
                'Februari',
                'Maret',
                'April',
                'Mei',
                'Juni',
                'Juli',
                'Agustus',
                'September',
                'Oktober',
                'November',
                'Desember',
            ];
            $m = (int) $month;
            return ($bulan[$m] ?? $month) . ' ' . $year;
        }

        $periodeText = 'Tidak ada data';
        if (isset($dataKunjungan) && count($dataKunjungan) > 0) {
            $firstYM = $dataKunjungan->first()->tahun_bulan ?? null;
            $lastYM = $dataKunjungan->last()->tahun_bulan ?? null;
            if ($firstYM && $lastYM) {
                $periodeText = ym_to_text($firstYM) . ' – ' . ym_to_text($lastYM);
            }
        }

        $location = 'Surakarta';
        $printedDate = date('d F Y');
    @endphp

    <table class="header-section">
        <tr>
            <td class="logo-left">
                @if ($logoLeftB64)
                    <img src="{{ $logoLeftB64 }}" alt="Logo Kiri" class="logo-img">
                @endif
            </td>
            <td class="center-content">
                <div class="institution-name">UPT PERPUSTAKAAN DAN LAYANAN DIGITAL</div>
                <div class="institution-name">UNIVERSITAS MUHAMMADIYAH SURAKARTA</div>
                <div class="institution-details">
                    Jl. A. Yani Tromol Pos I Pabelan Surakarta 57102.
                    Telepon 0271-717417
                </div>
                <div class="institution-details">
                    library.ums.ac.id | Email: perpus@ums.ac.id / humas.libums@gmail.com
                </div>
            </td>
            <td class="logo-right">
                @if ($logoRightB64)
                    <img src="{{ $logoRightB64 }}" alt="Logo Kanan" class="logo-img">
                @endif
            </td>
        </tr>
    </table>
    <hr class="divider-hr">

    <div class="report-title-section">
        <div class="main-title">LAPORAN KEHADIRAN ANGGOTA</div>
        <div class="period-info">Periode: {{ $periodeText }}</div>
    </div>

    <div class="borrower-details">
        <div class="detail-item">
            <span class="label">Nomor Kartu Anggota</span>: <span>{{ $fullBorrowerDetails->cardnumber ?? '-' }}</span>
        </div>
        <div class="detail-item">
            <span class="label">Nama</span>:
            <span>{{ trim(($fullBorrowerDetails->firstname ?? '') . ' ' . ($fullBorrowerDetails->surname ?? '')) ?: '-' }}</span>
        </div>
        <div class="detail-item">
            <span class="label">Email</span>: <span>{{ $fullBorrowerDetails->email ?? '-' }}</span>
        </div>
        <div class="detail-item">
            <span class="label">Telepon</span>: <span>{{ $fullBorrowerDetails->phone ?? '-' }}</span>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th class="num">No.</th>
                <th>Bulan Tahun</th>
                <th class="right">Jumlah Kunjungan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $total = 0;
            @endphp
            @forelse($dataKunjungan as $row)
                @php
                    $bulanText = ym_to_text($row->tahun_bulan ?? '');
                    $jml = (int) ($row->jumlah_kunjungan ?? 0);
                    $total += $jml;
                @endphp
                <tr>
                    <td class="num">{{ $no++ }}</td>
                    <td>{{ $bulanText }}</td>
                    <td class="right">{{ number_format($jml, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center; padding:16px;">Tidak ada data kunjungan untuk
                        ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
        @if (($dataKunjungan ?? collect())->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="2" class="right">Total</td>
                    <td class="right">{{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <table class="signature-section">
        <tr>
            <td></td>
            <td>
                <div class="signature-date">{{ $location }}, {{ $printedDate }}</div>
                <div class="signature-label">Petugas</div>
                <div style="height: 80px;"></div>
                <div class="signature-name">
                    ......................................
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        * Laporan ini dihasilkan otomatis dari sistem kunjungan. Apabila terdapat perbedaan data,
        silakan hubungi petugas perpustakaan untuk verifikasi.
    </div>
</body>

</html>
