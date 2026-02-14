<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\View\View;


class RewardController extends Controller
{

    /**
     * Menampilkan laporan top 5 pengunjung dan peminjam buku per tahun.
     */


    public function pemustakaTeraktif(Request $request): View
    {
        // Ambil input tahun & kategori
        $tahun = $request->input('tahun', Carbon::now()->year);
        $kategoriFilter = $request->input('kategori');
        $hasFilter = $request->has('tahun');

        $pengunjungTeraktif = collect();
        $peminjamTeraktif   = collect();

        if ($hasFilter) {
            $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
            $end   = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

            // --- HELPER: Closure untuk penentuan kategori (Agar Kodingan Rapi) ---
            $getKategori = function ($cat) {
                if (!$cat) return null;
                if (str_starts_with($cat, 'STD') && !str_starts_with($cat, 'LIB')) return 'Mahasiswa';
                if (str_starts_with($cat, 'TC') && !str_starts_with($cat, 'LIB')) return 'Dosen';
                if ((str_starts_with($cat, 'STAF') || $cat === 'LIBRARIAN') && !str_starts_with($cat, 'LIB')) return 'Tendik';
                return null;
            };

            // ==========================================
            // 1. PENGUNJUNG TERAKTIF (OPTIMIZED SQL)
            // ==========================================
            $sqlVisitor = "
            SELECT cardnumber, SUM(total) as total_kunjungan
            FROM (
                SELECT cardnumber, COUNT(*) as total
                FROM visitorhistory
                WHERE visittime BETWEEN ? AND ?
                GROUP BY cardnumber
                UNION ALL
                SELECT cardnumber, COUNT(*) as total
                FROM visitorcorner
                WHERE visittime BETWEEN ? AND ?
                GROUP BY cardnumber
            ) as gabungan
            GROUP BY cardnumber
            ORDER BY total_kunjungan DESC
            LIMIT 25000
        ";

            $rawVisits = DB::connection('mysql')->select($sqlVisitor, [$start, $end, $start, $end]);

            $cardNumbers = collect($rawVisits)->pluck('cardnumber')->map(fn($c) => trim(strtolower($c)))->all();

            $borrowers = DB::connection('mysql2')->table('borrowers')
                ->select('cardnumber', 'surname', 'firstname', 'categorycode')
                ->whereIn('cardnumber', $cardNumbers)
                ->get()
                ->mapWithKeys(function ($item) {
                    return [trim(strtolower($item->cardnumber)) => $item];
                });

            $pengunjungData = collect();
            foreach ($rawVisits as $visit) {
                $key = trim(strtolower($visit->cardnumber));
                $user = $borrowers->get($key);

                if (!$user) continue;

                $kategori = $getKategori($user->categorycode);

                if ($kategoriFilter && $kategori !== $kategoriFilter) continue;

                if ($kategori) {
                    $pengunjungData->push((object)[
                        'kategori'   => $kategori,
                        'cardnumber' => $user->cardnumber,
                        'nama'       => $user->firstname . ' ' . $user->surname,
                        'jumlah'     => $visit->total_kunjungan
                    ]);
                }
            }

            // Ambil Top 10 per Kategori
            $pengunjungTeraktif = $pengunjungData->groupBy('kategori')->map(function ($items) {
                return $items->sortByDesc('jumlah')->take(10)->values();
            })->flatten()->sortBy([['kategori', 'asc'], ['jumlah', 'desc']]);


            // ==========================================
            // 2. PEMINJAM TERAKTIF (OPTIMIZED SQL)
            // ==========================================
            $rawLoans = DB::connection('mysql2')->table('statistics')
                ->select('borrowernumber', DB::raw('count(*) as total'))
                ->where('type', 'issue')
                ->whereBetween('datetime', [$start, $end])
                ->groupBy('borrowernumber')
                ->orderBy('total', 'desc')
                ->limit(2000)
                ->get();

            $borrowerIds = $rawLoans->pluck('borrowernumber')->filter()->all();

            $borrowersLoan = DB::connection('mysql2')->table('borrowers')
                ->select('borrowernumber', 'cardnumber', 'surname', 'firstname', 'categorycode')
                ->whereIn('borrowernumber', $borrowerIds)
                ->get()
                ->keyBy('borrowernumber');

            $peminjamData = collect();
            foreach ($rawLoans as $stat) {
                $user = $borrowersLoan->get($stat->borrowernumber);
                if (!$user) continue;

                $kategori = $getKategori($user->categorycode);

                if ($kategoriFilter && $kategori !== $kategoriFilter) continue;

                if ($kategori) {
                    $peminjamData->push((object)[
                        'kategori'   => $kategori,
                        'cardnumber' => $user->cardnumber,
                        'nama'       => $user->firstname . ' ' . $user->surname,
                        'jumlah'     => $stat->total
                    ]);
                }
            }

            $peminjamTeraktif = $peminjamData->groupBy('kategori')->map(function ($items) {
                return $items->sortByDesc('jumlah')->take(10)->values();
            })->flatten()->sortBy([['kategori', 'asc'], ['jumlah', 'desc']]);
        }

        return view('pages.reward.pemustaka_teraktif', compact('pengunjungTeraktif', 'peminjamTeraktif', 'tahun', 'hasFilter'));
    }


    /**
     * Mengekspor data top pengunjung ke CSV.
     */
    public function exportCsvPemustakaTeraktif(Request $request)
    {
        $tahun = $request->input('tahun', Carbon::now()->year);
        $kategoriFilter = $request->input('kategori');

        $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
        $end   = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

        $judulLaporan = "LAPORAN PEMUSTAKA TERAKTIF TAHUN " . $tahun;
        $suffixFile   = "_Semua_Kategori";

        if ($kategoriFilter) {
            $judulLaporan .= " - KATEGORI: " . strtoupper($kategoriFilter);
            $suffixFile    = "_" . $kategoriFilter;
        } else {
            $judulLaporan .= " - SEMUA KATEGORI";
        }

        $fileName = 'top_pengunjung_' . $tahun . $suffixFile . '.csv';

        $getKategori = function ($cat) {
            if (!$cat) return null;
            if (str_starts_with($cat, 'STD') && !str_starts_with($cat, 'LIB')) return 'Mahasiswa';
            if (str_starts_with($cat, 'TC') && !str_starts_with($cat, 'LIB')) return 'Dosen';
            if ((str_starts_with($cat, 'STAF') || $cat === 'LIBRARIAN') && !str_starts_with($cat, 'LIB')) return 'Tendik';
            return null;
        };

        // Build CSV in memory (avoids output buffer contamination)
        $file = fopen('php://temp', 'r+');
        fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, [$judulLaporan], ';');
        fputcsv($file, [], ';');
        fputcsv($file, ['Rank', 'Kategori', 'Cardnumber', 'Nama Lengkap', 'Jumlah Kunjungan'], ';');

        $sqlVisitor = "
            SELECT cardnumber, SUM(total) as total_kunjungan
            FROM (
                SELECT cardnumber, COUNT(*) as total FROM visitorhistory
                WHERE visittime BETWEEN ? AND ? GROUP BY cardnumber
                UNION ALL
                SELECT cardnumber, COUNT(*) as total FROM visitorcorner
                WHERE visittime BETWEEN ? AND ? GROUP BY cardnumber
            ) as gabungan
            GROUP BY cardnumber
            ORDER BY total_kunjungan DESC
            LIMIT 2000
        ";

        $rawVisits = DB::connection('mysql')->select($sqlVisitor, [$start, $end, $start, $end]);

        if (!empty($rawVisits)) {
            $cardNumbers = collect($rawVisits)->pluck('cardnumber')->map(fn($c) => trim(strtolower($c)))->all();

            $borrowers = DB::connection('mysql2')->table('borrowers')
                ->select('cardnumber', 'surname', 'firstname', 'categorycode')
                ->whereIn('cardnumber', $cardNumbers)
                ->get()
                ->mapWithKeys(fn($item) => [trim(strtolower($item->cardnumber)) => $item]);

            $finalData = collect();
            foreach ($rawVisits as $visit) {
                $key = trim(strtolower($visit->cardnumber));
                $user = $borrowers->get($key);
                if (!$user) continue;
                $kategori = $getKategori($user->categorycode);
                if (!$kategori) continue;
                if ($kategoriFilter && $kategori !== $kategoriFilter) continue;

                $finalData->push([
                    'kategori'   => $kategori,
                    'cardnumber' => $user->cardnumber,
                    'nama'       => $user->firstname . ' ' . $user->surname,
                    'jumlah'     => $visit->total_kunjungan
                ]);
            }

            $sortedData = $finalData->groupBy('kategori')->map(function ($items) {
                return $items->sortByDesc('jumlah')->take(10)->values();
            })->flatten(1)->sortBy([['kategori', 'asc'], ['jumlah', 'desc']]);

            $rank = 1;
            $prevCat = '';
            foreach ($sortedData as $row) {
                if ($row['kategori'] !== $prevCat) {
                    $rank = 1;
                    $prevCat = $row['kategori'];
                }
                fputcsv($file, [$rank++, $row['kategori'], $row['cardnumber'], $row['nama'], $row['jumlah']], ';');
            }
        } else {
            fputcsv($file, ['Tidak ada data ditemukan untuk periode ini'], ';');
        }

        rewind($file);
        $csvContent = stream_get_contents($file);
        fclose($file);

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }


    /**
     * Mengekspor data top peminjam ke CSV.
     */
    public function exportCsvPeminjamTeraktif(Request $request)
    {
        $tahun = $request->input('tahun', Carbon::now()->year);
        $kategoriFilter = $request->input('kategori');

        $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
        $end   = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

        $judulLaporan = "LAPORAN PEMINJAM TERAKTIF TAHUN " . $tahun;
        $suffixFile   = "_Semua_Kategori";

        if ($kategoriFilter) {
            $judulLaporan .= " - KATEGORI: " . strtoupper($kategoriFilter);
            $suffixFile    = "_" . $kategoriFilter;
        } else {
            $judulLaporan .= " - SEMUA KATEGORI";
        }

        $fileName = 'top_peminjam_' . $tahun . $suffixFile . '.csv';

        $getKategori = function ($cat) {
            if (!$cat) return null;
            if (str_starts_with($cat, 'STD') && !str_starts_with($cat, 'LIB')) return 'Mahasiswa';
            if (str_starts_with($cat, 'TC') && !str_starts_with($cat, 'LIB')) return 'Dosen';
            if ((str_starts_with($cat, 'STAF') || $cat === 'LIBRARIAN') && !str_starts_with($cat, 'LIB')) return 'Tendik';
            return null;
        };

        // Build CSV in memory
        $file = fopen('php://temp', 'r+');
        fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, [$judulLaporan], ';');
        fputcsv($file, [], ';');
        fputcsv($file, ['Rank', 'Kategori', 'Cardnumber', 'Nama Lengkap', 'Jumlah Buku Dipinjam'], ';');

        $rawLoans = DB::connection('mysql2')->table('statistics')
            ->select('borrowernumber', DB::raw('count(*) as total'))
            ->where('type', 'issue')
            ->whereBetween('datetime', [$start, $end])
            ->groupBy('borrowernumber')
            ->orderBy('total', 'desc')
            ->limit(2000)
            ->get();

        if ($rawLoans->isNotEmpty()) {
            $borrowerIds = $rawLoans->pluck('borrowernumber')->filter()->all();

            $borrowersLoan = DB::connection('mysql2')->table('borrowers')
                ->select('borrowernumber', 'cardnumber', 'surname', 'firstname', 'categorycode')
                ->whereIn('borrowernumber', $borrowerIds)
                ->get()
                ->keyBy('borrowernumber');

            $finalData = collect();
            foreach ($rawLoans as $stat) {
                $user = $borrowersLoan->get($stat->borrowernumber);
                if (!$user) continue;
                $kategori = $getKategori($user->categorycode);
                if (!$kategori) continue;
                if ($kategoriFilter && $kategori !== $kategoriFilter) continue;

                $finalData->push([
                    'kategori'   => $kategori,
                    'cardnumber' => $user->cardnumber,
                    'nama'       => $user->firstname . ' ' . $user->surname,
                    'jumlah'     => $stat->total
                ]);
            }

            $sortedData = $finalData->groupBy('kategori')->map(function ($items) {
                return $items->sortByDesc('jumlah')->take(10)->values();
            })->flatten(1)->sortBy([['kategori', 'asc'], ['jumlah', 'desc']]);

            $rank = 1;
            $prevCat = '';
            foreach ($sortedData as $row) {
                if ($row['kategori'] !== $prevCat) {
                    $rank = 1;
                    $prevCat = $row['kategori'];
                }
                fputcsv($file, [$rank++, $row['kategori'], $row['cardnumber'], $row['nama'], $row['jumlah']], ';');
            }
        } else {
            fputcsv($file, ['Tidak ada data transaksi peminjaman untuk periode ini'], ';');
        }

        rewind($file);
        $csvContent = stream_get_contents($file);
        fclose($file);

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
