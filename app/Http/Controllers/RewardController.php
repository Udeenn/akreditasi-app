<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
            // Query ini melakukan agregasi di Database (Sangat Cepat)
            // UNION ALL dilakukan di dalam subquery SQL, bukan di PHP
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

            // Eksekusi Raw Query
            $rawVisits = DB::connection('mysql')->select($sqlVisitor, [$start, $end, $start, $end]);

            // Ambil daftar cardnumber dari hasil query di atas
            $cardNumbers = collect($rawVisits)->pluck('cardnumber')->map(fn($c) => trim(strtolower($c)))->all();

            // Ambil Data Profil User dari DB Koha (Batch Query)
            $borrowers = DB::connection('mysql2')->table('borrowers')
                ->select('cardnumber', 'surname', 'firstname', 'categorycode')
                ->whereIn('cardnumber', $cardNumbers) // Menggunakan index database
                ->get()
                // Normalisasi Key agar masalah 'spasi' dan 'huruf besar' teratasi (Denny Aman)
                ->mapWithKeys(function ($item) {
                    return [trim(strtolower($item->cardnumber)) => $item];
                });

            // Gabungkan Data (Mapping)
            $pengunjungData = collect();
            foreach ($rawVisits as $visit) {
                $key = trim(strtolower($visit->cardnumber));
                $user = $borrowers->get($key);

                if (!$user) continue;

                $kategori = $getKategori($user->categorycode);

                // Filter Kategori (PHP Side)
                if ($kategoriFilter && $kategori !== $kategoriFilter) continue;

                if ($kategori) {
                    $pengunjungData->push((object)[
                        'kategori'   => $kategori,
                        'cardnumber' => $user->cardnumber, // Pakai yang asli dari tabel user
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
            // Menggunakan Eloquent tapi dipaksa Group By di Database
            $rawLoans = DB::connection('mysql2')->table('statistics')
                ->select('borrowernumber', DB::raw('count(*) as total'))
                ->where('type', 'issue')
                ->whereBetween('datetime', [$start, $end])
                ->groupBy('borrowernumber')
                ->orderBy('total', 'desc')
                ->limit(2000) // Batasi hanya 2000 data teratas agar memori hemat
                ->get();

            $borrowerIds = $rawLoans->pluck('borrowernumber')->filter()->all();

            // Ambil profil peminjam
            $borrowersLoan = DB::connection('mysql2')->table('borrowers')
                ->select('borrowernumber', 'cardnumber', 'surname', 'firstname', 'categorycode')
                ->whereIn('borrowernumber', $borrowerIds)
                ->get()
                ->keyBy('borrowernumber'); // Pakai ID Integer, aman dari spasi

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
     * Mengekspor data top 5 pengunjung ke CSV.
     */


    public function exportCsvPemustakaTeraktif(Request $request): StreamedResponse
    {
        // 1. Ambil Input & Setup Tanggal
        $tahun = $request->input('tahun', Carbon::now()->year);
        $kategoriFilter = $request->input('kategori');

        $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
        $end   = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

        // 2. Buat Judul Laporan & Nama File
        $judulLaporan = "LAPORAN PEMUSTAKA TERAKTIF TAHUN " . $tahun;
        $suffixFile   = "_Semua_Kategori";

        if ($kategoriFilter) {
            $judulLaporan .= " - KATEGORI: " . strtoupper($kategoriFilter);
            $suffixFile    = "_" . $kategoriFilter;
        } else {
            $judulLaporan .= " - SEMUA KATEGORI";
        }

        $fileName = 'top_pengunjung_' . $tahun . $suffixFile . '.csv';

        // 3. Helper Penentuan Kategori
        $getKategori = function ($cat) {
            if (!$cat) return null;
            if (str_starts_with($cat, 'STD') && !str_starts_with($cat, 'LIB')) return 'Mahasiswa';
            if (str_starts_with($cat, 'TC') && !str_starts_with($cat, 'LIB')) return 'Dosen';
            if ((str_starts_with($cat, 'STAF') || $cat === 'LIBRARIAN') && !str_starts_with($cat, 'LIB')) return 'Tendik';
            return null;
        };

        // 4. Callback Stream (Proses Data)
        $callback = function () use ($start, $end, $getKategori, $kategoriFilter, $judulLaporan) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');

            // Tambahkan BOM agar bisa dibaca Excel dengan benar (UTF-8)
            fwrite($file, "\xEF\xBB\xBF");

            // --- BARIS 1: JUDUL LAPORAN ---
            fputcsv($file, [$judulLaporan], ';');

            // --- BARIS 2: KOSONG (Jeda) ---
            fputcsv($file, [], ';');

            // --- BARIS 3: HEADER TABEL ---
            fputcsv($file, ['Rank', 'Kategori', 'Cardnumber', 'Nama Lengkap', 'Jumlah Kunjungan'], ';');

            // --- STEP A: Query Agregat (Ambil Kandidat Top 2000) ---
            // Kita ambil banyak dulu (2000) untuk memastikan stok data cukup sebelum difilter kategori
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

            if (empty($rawVisits)) {
                fputcsv($file, ['Tidak ada data ditemukan untuk periode ini'], ';');
                fclose($file);
                return;
            }

            // --- STEP B: Ambil Detail User ---
            $cardNumbers = collect($rawVisits)->pluck('cardnumber')->map(fn($c) => trim(strtolower($c)))->all();

            $borrowers = DB::connection('mysql2')->table('borrowers')
                ->select('cardnumber', 'surname', 'firstname', 'categorycode')
                ->whereIn('cardnumber', $cardNumbers)
                ->get()
                ->mapWithKeys(function ($item) {
                    return [trim(strtolower($item->cardnumber)) => $item];
                });

            // --- STEP C: Gabung & Filter ---
            $finalData = collect();

            foreach ($rawVisits as $visit) {
                $key = trim(strtolower($visit->cardnumber));
                $user = $borrowers->get($key);

                if (!$user) continue;

                $kategori = $getKategori($user->categorycode);
                if (!$kategori) continue;

                // Terapkan Filter Kategori (Jika user memilih kategori tertentu)
                if ($kategoriFilter && $kategori !== $kategoriFilter) {
                    continue;
                }

                $finalData->push([
                    'kategori'   => $kategori,
                    'cardnumber' => $user->cardnumber,
                    'nama'       => $user->firstname . ' ' . $user->surname,
                    'jumlah'     => $visit->total_kunjungan
                ]);
            }

            // --- STEP D: Sorting & LIMIT 20 FINAL ---
            // Urutkan data, lalu ambil 20 teratas saja untuk diexport
            $sortedData = $finalData
                ->sortBy([['kategori', 'asc'], ['jumlah', 'desc']])
                ->take(20); // <--- INI BATASAN 20 DATA

            // --- STEP E: Tulis ke File ---
            $rank = 1;
            $prevCat = '';

            foreach ($sortedData as $row) {
                // Reset ranking tiap ganti kategori (opsional, biar rapi per kategori)
                if ($row['kategori'] !== $prevCat) {
                    $rank = 1;
                    $prevCat = $row['kategori'];
                }

                fputcsv($file, [
                    $rank++,
                    $row['kategori'],
                    $row['cardnumber'],
                    $row['nama'],
                    $row['jumlah']
                ], ';');
            }

            fclose($file);
        };

        $headers = [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return new StreamedResponse($callback, 200, $headers);
    }

    public function exportCsvPeminjamTeraktif(Request $request): StreamedResponse
    {
        // 1. Ambil Input & Setup Tanggal
        $tahun = $request->input('tahun', Carbon::now()->year);
        $kategoriFilter = $request->input('kategori');

        $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
        $end   = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

        // 2. Buat Judul Laporan & Nama File
        $judulLaporan = "LAPORAN PEMINJAM TERAKTIF TAHUN " . $tahun;
        $suffixFile   = "_Semua_Kategori";

        if ($kategoriFilter) {
            $judulLaporan .= " - KATEGORI: " . strtoupper($kategoriFilter);
            $suffixFile    = "_" . $kategoriFilter;
        } else {
            $judulLaporan .= " - SEMUA KATEGORI";
        }

        $fileName = 'top_peminjam_' . $tahun . $suffixFile . '.csv';

        // 3. Helper Penentuan Kategori (Sama persis dengan Controller View)
        $getKategori = function ($cat) {
            if (!$cat) return null;
            if (str_starts_with($cat, 'STD') && !str_starts_with($cat, 'LIB')) return 'Mahasiswa';
            if (str_starts_with($cat, 'TC') && !str_starts_with($cat, 'LIB')) return 'Dosen';
            if ((str_starts_with($cat, 'STAF') || $cat === 'LIBRARIAN') && !str_starts_with($cat, 'LIB')) return 'Tendik';
            return null;
        };

        // 4. Callback Stream
        $callback = function () use ($start, $end, $getKategori, $kategoriFilter, $judulLaporan) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // --- BARIS 1: JUDUL LAPORAN ---
            fputcsv($file, [$judulLaporan], ';');
            fputcsv($file, [], ';'); // Baris kosong

            // --- BARIS 3: HEADER ---
            fputcsv($file, ['Rank', 'Kategori', 'Cardnumber', 'Nama Lengkap', 'Jumlah Buku Dipinjam'], ';');

            // --- STEP A: Query Agregat (Ambil Kandidat Top 2000 dari Statistik) ---
            // Menggunakan borrowernumber (INT) agar lebih cepat daripada string join
            $rawLoans = DB::connection('mysql2')->table('statistics')
                ->select('borrowernumber', DB::raw('count(*) as total'))
                ->where('type', 'issue')
                ->whereBetween('datetime', [$start, $end])
                ->groupBy('borrowernumber')
                ->orderBy('total', 'desc')
                ->limit(2000) // Ambil stok data yang cukup banyak dulu
                ->get();

            if ($rawLoans->isEmpty()) {
                fputcsv($file, ['Tidak ada data transaksi peminjaman untuk periode ini'], ';');
                fclose($file);
                return;
            }

            // --- STEP B: Ambil Detail User ---
            $borrowerIds = $rawLoans->pluck('borrowernumber')->filter()->all();

            $borrowersLoan = DB::connection('mysql2')->table('borrowers')
                ->select('borrowernumber', 'cardnumber', 'surname', 'firstname', 'categorycode')
                ->whereIn('borrowernumber', $borrowerIds)
                ->get()
                ->keyBy('borrowernumber'); // Key pakai ID Integer

            // --- STEP C: Gabung & Filter ---
            $finalData = collect();

            foreach ($rawLoans as $stat) {
                $user = $borrowersLoan->get($stat->borrowernumber);

                if (!$user) continue;

                $kategori = $getKategori($user->categorycode);
                if (!$kategori) continue;

                // Terapkan Filter Kategori
                if ($kategoriFilter && $kategori !== $kategoriFilter) {
                    continue;
                }

                $finalData->push([
                    'kategori'   => $kategori,
                    'cardnumber' => $user->cardnumber,
                    'nama'       => $user->firstname . ' ' . $user->surname,
                    'jumlah'     => $stat->total
                ]);
            }

            // --- STEP D: Sorting & LIMIT 20 FINAL ---
            $sortedData = $finalData
                ->sortBy([['kategori', 'asc'], ['jumlah', 'desc']])
                ->take(20);

            // --- STEP E: Tulis ke File ---
            $rank = 1;
            $prevCat = '';

            foreach ($sortedData as $row) {
                if ($row['kategori'] !== $prevCat) {
                    $rank = 1;
                    $prevCat = $row['kategori'];
                }

                fputcsv($file, [
                    $rank++,
                    $row['kategori'],
                    $row['cardnumber'],
                    $row['nama'],
                    $row['jumlah']
                ], ';');
            }

            fclose($file);
        };

        $headers = [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return new StreamedResponse($callback, 200, $headers);
    }
}
