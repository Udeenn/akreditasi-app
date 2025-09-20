<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RewardController extends Controller
{
    /**
     * Menampilkan laporan top 5 pengunjung dan peminjam buku per tahun.
     */
    public function pemustakaTeraktif(Request $request): View
    {
        $tahun = $request->input('tahun', Carbon::now()->year);

        $pengunjungTeraktif = [];
        $peminjamTeraktif = [];
        $hasFilter = $request->has('tahun');

        if ($hasFilter) {
            $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
            $end = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

            // Query TOP 5 PENGUNJUNG
            $queryPengunjung = "
            (
                SELECT 'Mahasiswa' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
                FROM visitorhistory v
                JOIN borrowers b ON b.cardnumber = v.cardnumber
                WHERE v.visittime >= ? AND v.visittime < ?
                    AND b.categorycode LIKE 'STD%'
                    AND b.categorycode NOT LIKE 'LIB%'
                GROUP BY b.cardnumber, b.surname
                ORDER BY jumlah DESC
                LIMIT 5
            )
            UNION ALL
            (
                SELECT 'Dosen' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
                FROM visitorhistory v
                JOIN borrowers b ON b.cardnumber = v.cardnumber
                WHERE v.visittime >= ? AND v.visittime < ?
                    AND b.categorycode LIKE 'TC%'
                    AND b.categorycode NOT LIKE 'LIB%'
                GROUP BY b.cardnumber, b.surname
                ORDER BY jumlah DESC
                LIMIT 5
            )
            UNION ALL
            (
                SELECT 'Tendik' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
                FROM visitorhistory v
                JOIN borrowers b ON b.cardnumber = v.cardnumber
                WHERE v.visittime >= ? AND v.visittime < ?
                    AND b.categorycode LIKE 'STAF%'
                    AND b.categorycode NOT LIKE 'LIB%'
                GROUP BY b.cardnumber, b.surname
                ORDER BY jumlah DESC
                LIMIT 5
            )
            ORDER BY kategori, jumlah DESC;
        ";
            $pengunjungTeraktif = DB::connection('mysql2')->select($queryPengunjung, [
                $start->toDateString(),
                $end->toDateString(),
                $start->toDateString(),
                $end->toDateString(),
                $start->toDateString(),
                $end->toDateString(),
            ]);

            // Query TOP 5 BUKU DIPINJAM
            $queryPeminjam = "
            (
                SELECT 'Mahasiswa' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
                FROM statistics s
                JOIN borrowers b ON b.borrowernumber = s.borrowernumber
                WHERE s.type = 'issue'
                    AND s.datetime >= ? AND s.datetime < ?
                    AND b.categorycode LIKE 'STD%'
                    AND b.categorycode NOT LIKE 'LIB%'
                GROUP BY b.borrowernumber, b.cardnumber, b.surname
                ORDER BY jumlah DESC
                LIMIT 5
            )
            UNION ALL
            (
                SELECT 'Dosen' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
                FROM statistics s
                JOIN borrowers b ON b.borrowernumber = s.borrowernumber
                WHERE s.type = 'issue'
                    AND s.datetime >= ? AND s.datetime < ?
                    AND b.categorycode LIKE 'TC%'
                    AND b.categorycode NOT LIKE 'LIB%'
                GROUP BY b.borrowernumber, b.cardnumber, b.surname
                ORDER BY jumlah DESC
                LIMIT 5
            )
            UNION ALL
            (
                SELECT 'Tendik' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
                FROM statistics s
                JOIN borrowers b ON b.borrowernumber = s.borrowernumber
                WHERE s.type = 'issue'
                    AND s.datetime >= ? AND s.datetime < ?
                    AND b.categorycode LIKE 'STAF%'
                    AND b.categorycode NOT LIKE 'LIB%'
                GROUP BY b.borrowernumber, b.cardnumber, b.surname
                ORDER BY jumlah DESC
                LIMIT 5
            )
            ORDER BY kategori, jumlah DESC;
        ";
            $peminjamTeraktif = DB::connection('mysql2')->select($queryPeminjam, [
                $start->toDateString(),
                $end->toDateString(),
                $start->toDateString(),
                $end->toDateString(),
                $start->toDateString(),
                $end->toDateString(),
            ]);
        }

        return view('pages.reward.pemustaka_teraktif', compact('pengunjungTeraktif', 'peminjamTeraktif', 'tahun', 'hasFilter'));
    }

    /**
     * Mengekspor data top 5 pengunjung ke CSV.
     */
    public function exportCsvPemustakaTeraktif(Request $request): StreamedResponse
    {
        $tahun = $request->input('tahun', Carbon::now()->year);
        $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
        $end = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

        $query = "
        (
            SELECT 'Mahasiswa' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
            FROM visitorhistory v
            JOIN borrowers b ON b.cardnumber = v.cardnumber
            WHERE v.visittime >= ? AND v.visittime < ?
                AND b.categorycode LIKE 'STD%'
                AND b.categorycode NOT LIKE 'LIB%'
            GROUP BY b.cardnumber, b.surname
            ORDER BY jumlah DESC
            LIMIT 5
        )
        UNION ALL
        (
            SELECT 'Dosen' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
            FROM visitorhistory v
            JOIN borrowers b ON b.cardnumber = v.cardnumber
            WHERE v.visittime >= ? AND v.visittime < ?
                AND b.categorycode LIKE 'TC%'
                AND b.categorycode NOT LIKE 'LIB%'
            GROUP BY b.cardnumber, b.surname
            ORDER BY jumlah DESC
            LIMIT 5
        )
        UNION ALL
        (
            SELECT 'Tendik' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
            FROM visitorhistory v
            JOIN borrowers b ON b.cardnumber = v.cardnumber
            WHERE v.visittime >= ? AND v.visittime < ?
                AND b.categorycode LIKE 'STAF%'
                AND b.categorycode NOT LIKE 'LIB%'
            GROUP BY b.cardnumber, b.surname
            ORDER BY jumlah DESC
            LIMIT 5
        )
        ORDER BY kategori, jumlah DESC;
    ";

        $pengunjungTeraktif = DB::connection('mysql2')->select($query, [
            $start->toDateString(),
            $end->toDateString(),
            $start->toDateString(),
            $end->toDateString(),
            $start->toDateString(),
            $end->toDateString(),
        ]);

        $headers = [
            'Content-Type' => 'text/csv;charset=utf-8',
            'Content-Disposition' => 'attachment; filename="pengunjung_teraktif_' . $tahun . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($pengunjungTeraktif) {
            $file = fopen('php://output', 'w');

            // Tambahkan BOM untuk kompatibilitas UTF-8 di Excel
            fwrite($file, "\xEF\xBB\xBF");

            // Gunakan titik koma sebagai delimiter
            fputcsv($file, ['Kategori', 'Cardnumber', 'Nama', 'Jumlah Kunjungan'], ';');
            foreach ($pengunjungTeraktif as $row) {
                fputcsv($file, (array) $row, ';');
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function exportCsvPeminjamTeraktif(Request $request): StreamedResponse
    {
        $tahun = $request->input('tahun', Carbon::now()->year);
        $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
        $end = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

        $query = "
            (
                SELECT 'Mahasiswa' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
                FROM statistics s
                JOIN borrowers b ON b.borrowernumber = s.borrowernumber
                WHERE s.type = 'issue'
                    AND s.datetime >= ? AND s.datetime < ?
                    AND b.categorycode LIKE 'STD%'
                    AND b.categorycode NOT LIKE 'LIB%'
                GROUP BY b.borrowernumber, b.cardnumber, b.surname
                ORDER BY jumlah DESC
                LIMIT 5
            )
            UNION ALL
            (
                SELECT 'Dosen' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
                FROM statistics s
                JOIN borrowers b ON b.borrowernumber = s.borrowernumber
                WHERE s.type = 'issue'
                    AND s.datetime >= ? AND s.datetime < ?
                    AND b.categorycode LIKE 'TC%'
                    AND b.categorycode NOT LIKE 'LIB%'
                GROUP BY b.borrowernumber, b.cardnumber, b.surname
                ORDER BY jumlah DESC
                LIMIT 5
            )
            UNION ALL
            (
                SELECT 'Tendik' AS kategori, b.cardnumber, b.surname AS nama, COUNT(*) AS jumlah
                FROM statistics s
                JOIN borrowers b ON b.borrowernumber = s.borrowernumber
                WHERE s.type = 'issue'
                    AND s.datetime >= ? AND s.datetime < ?
                    AND b.categorycode LIKE 'STAF%'
                    AND b.categorycode NOT LIKE 'LIB%'
                GROUP BY b.borrowernumber, b.cardnumber, b.surname
                ORDER BY jumlah DESC
                LIMIT 5
            )
            ORDER BY kategori, jumlah DESC;
        ";

        $peminjamTeraktif = DB::connection('mysql2')->select($query, [
            $start->toDateString(),
            $end->toDateString(),
            $start->toDateString(),
            $end->toDateString(),
            $start->toDateString(),
            $end->toDateString(),
        ]);

        $headers = [
            'Content-Type' => 'text/csv;charset=utf-8',
            'Content-Disposition' => 'attachment; filename="peminjam_teraktif_' . $tahun . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($peminjamTeraktif) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Kategori', 'Cardnumber', 'Nama', 'Jumlah Buku Dipinjam'], ';');
            foreach ($peminjamTeraktif as $row) {
                fputcsv($file, (array) $row, ';');
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
