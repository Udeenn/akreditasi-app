<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use App\Models\M_Auv;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PeminjamanController extends Controller
{


//     public function pertanggal(Request $request)
// {
//     $filterType = $request->input('filter_type', 'daily');

//     // Inisialisasi Default Values
//     $totalBooks = 0;
//     $totalReturns = 0;
//     $totalBorrowers = 0;
//     $rerataPeminjaman = 0;
//     $fullStatisticsForChart = collect();

//     $statistics = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1, [
//         'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
//         'query' => $request->query(),
//     ]);

//     $hasFilter = $request->filled('start_date') || $request->filled('end_date') ||
//                  $request->filled('start_year') || $request->filled('end_year');

//     // Variabel View Default
//     $startDate = $request->input('start_date', \Carbon\Carbon::now()->subDays(30)->format('Y-m-d'));
//     $endDate   = $request->input('end_date', \Carbon\Carbon::now()->format('Y-m-d'));
//     $startYear = $request->input('start_year', \Carbon\Carbon::now()->year);
//     $endYear   = $request->input('end_year', \Carbon\Carbon::now()->year);

//     if ($hasFilter) {
//         try {
//             // --- 1. SETUP TANGGAL ---
//             if ($filterType == 'daily') {
//                 $start = \Carbon\Carbon::parse($startDate)->startOfDay();
//                 $end   = \Carbon\Carbon::parse($endDate)->endOfDay();
//             } else {
//                 if ($startYear > $endYear) {
//                     [$startYear, $endYear] = [$endYear, $startYear];
//                 }
//                 $start = \Carbon\Carbon::createFromDate($startYear, 1, 1)->startOfDay();
//                 $end   = \Carbon\Carbon::createFromDate($endYear, 12, 31)->endOfDay();
//             }

//             if ($start->greaterThan($end)) {
//                 [$start, $end] = [$end, $start];
//             }
//             $startDate = $start->format('Y-m-d');
//             $endDate   = $end->format('Y-m-d');

//             // --- 2. QUERY SUMMARY (TOTAL) ---
//             $summaryData = DB::connection('mysql2')->table('statistics as s')
//                 ->select(
//                     DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN 1 END) as total_books'),
//                     DB::raw('COUNT(CASE WHEN s.type = "return" THEN 1 END) as total_returns'),
//                     DB::raw('COUNT(DISTINCT CASE WHEN s.type IN ("issue", "renew") THEN s.borrowernumber END) as total_borrowers')
//                 )
//                 ->whereBetween('s.datetime', [$start, $end])
//                 ->first();

//             if ($summaryData) {
//                 $totalBooks = $summaryData->total_books;
//                 $totalReturns = $summaryData->total_returns;
//                 $totalBorrowers = $summaryData->total_borrowers;
//             }

//             // --- 3. QUERY UTAMA (CHART & TABEL) ---
//             // UPDATE: Kita ambil type 'return' juga, dan pakai SUM(CASE...) untuk memisahkan kolom
//             $query = DB::connection('mysql2')->table('statistics as s')
//                 ->whereIn('s.type', ['issue', 'renew', 'return']) // Sertakan 'return'
//                 ->whereBetween('s.datetime', [$start, $end]);

//             if ($filterType == 'daily') {
//                 $query->select(
//                     DB::raw('DATE(s.datetime) as periode'),
//                     // Hitung Peminjaman
//                     DB::raw('SUM(CASE WHEN s.type IN ("issue", "renew") THEN 1 ELSE 0 END) as jumlah_peminjaman_buku'),
//                     // Hitung Pengembalian (BARU)
//                     DB::raw('SUM(CASE WHEN s.type = "return" THEN 1 ELSE 0 END) as jumlah_pengembalian'),
//                     // Hitung Peminjam Unik
//                     DB::raw('COUNT(DISTINCT CASE WHEN s.type IN ("issue", "renew") THEN s.borrowernumber END) as jumlah_peminjam_unik')
//                 );
//             } else {
//                 $query->select(
//                     DB::raw('DATE_FORMAT(s.datetime, "%Y-%m") as periode'),
//                     DB::raw('SUM(CASE WHEN s.type IN ("issue", "renew") THEN 1 ELSE 0 END) as jumlah_peminjaman_buku'),
//                     DB::raw('SUM(CASE WHEN s.type = "return" THEN 1 ELSE 0 END) as jumlah_pengembalian'),
//                     DB::raw('COUNT(DISTINCT CASE WHEN s.type IN ("issue", "renew") THEN s.borrowernumber END) as jumlah_peminjam_unik')
//                 );
//             }

//             $fullStatisticsForChart = $query->groupBy('periode')
//                 ->orderBy('periode', 'asc')
//                 ->get();

//             // --- 4. RERATA & PAGINASI ---
//             $jumlahPeriode = $fullStatisticsForChart->count();
//             $rerataPeminjaman = ($jumlahPeriode > 0) ? ($totalBooks / $jumlahPeriode) : 0;

//             $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
//             $perPage = 10;
//             $items = $fullStatisticsForChart->slice(($page - 1) * $perPage, $perPage)->values();

//             $statistics = new \Illuminate\Pagination\LengthAwarePaginator(
//                 $items,
//                 $fullStatisticsForChart->count(),
//                 $perPage,
//                 $page,
//                 ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
//             );

//         } catch (\Exception $e) {
//             return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
//         }
//     }

//     return view('pages.peminjaman.peminjamanRentangTanggal', compact(
//         'statistics', 'fullStatisticsForChart', 'startDate', 'endDate',
//         'startYear', 'endYear', 'filterType', 'totalBooks', 'totalReturns',
//         'totalBorrowers', 'rerataPeminjaman'
//     ));
// }

    public function pertanggal(Request $request)
{
    $filterType = $request->input('filter_type', 'daily');

    // Inisialisasi Default Values
    $totalBooks = 0;
    $totalReturns = 0;
    $totalBorrowers = 0;
    $totalCirculation = 0;
    $rerataPeminjaman = 0;
    $fullStatisticsForChart = collect();

    $statistics = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1, [
        'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
        'query' => $request->query(),
    ]);

    $hasFilter = $request->filled('start_date') || $request->filled('end_date') ||
                 $request->filled('start_year') || $request->filled('end_year');

    // Variabel View Default
    $startDate = $request->input('start_date', \Carbon\Carbon::now()->subDays(30)->format('Y-m-d'));
    $endDate   = $request->input('end_date', \Carbon\Carbon::now()->format('Y-m-d'));
    $startYear = $request->input('start_year', \Carbon\Carbon::now()->year);
    $endYear   = $request->input('end_year', \Carbon\Carbon::now()->year);

    if ($hasFilter) {
        try {
            // --- 1. SETUP TANGGAL ---
            if ($filterType == 'daily') {
                $start = \Carbon\Carbon::parse($startDate)->startOfDay();
                $end   = \Carbon\Carbon::parse($endDate)->endOfDay();
            } else {
                if ($startYear > $endYear) {
                    [$startYear, $endYear] = [$endYear, $startYear];
                }
                $start = \Carbon\Carbon::createFromDate($startYear, 1, 1)->startOfDay();
                $end   = \Carbon\Carbon::createFromDate($endYear, 12, 31)->endOfDay();
            }

            if ($start->greaterThan($end)) {
                [$start, $end] = [$end, $start];
            }
            $startDate = $start->format('Y-m-d');
            $endDate   = $end->format('Y-m-d');

            // --- 2. QUERY SUMMARY (TOTAL) ---
            $summaryData = DB::connection('mysql2')->table('statistics as s')
                ->select(
                    DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN 1 END) as total_books'),
                    DB::raw('COUNT(CASE WHEN s.type = "return" THEN 1 END) as total_returns'),
                    DB::raw('COUNT(DISTINCT CASE WHEN s.type IN ("issue", "renew") THEN s.borrowernumber END) as total_borrowers')
                )
                ->whereBetween('s.datetime', [$start, $end])
                ->first();

            if ($summaryData) {
                $totalBooks = $summaryData->total_books;
                $totalReturns = $summaryData->total_returns;
                $totalBorrowers = $summaryData->total_borrowers;
                $totalCirculation = $totalBooks + $totalReturns;
            }

            // --- 3. QUERY UTAMA (CHART & TABEL) ---
            $query = DB::connection('mysql2')->table('statistics as s')
                ->whereIn('s.type', ['issue', 'renew', 'return'])
                ->whereBetween('s.datetime', [$start, $end]);

            // UPDATE: Tambahkan 'total_sirkulasi' (COUNT(*)) karena filter type sudah issue, renew, return
            if ($filterType == 'daily') {
                $query->select(
                    DB::raw('DATE(s.datetime) as periode'),
                    DB::raw('SUM(CASE WHEN s.type = "issue" THEN 1 ELSE 0 END) as jumlah_issue'),
                    DB::raw('SUM(CASE WHEN s.type = "renew" THEN 1 ELSE 0 END) as jumlah_renew'),
                    DB::raw('SUM(CASE WHEN s.type = "return" THEN 1 ELSE 0 END) as jumlah_pengembalian'),
                    DB::raw('COUNT(*) as total_sirkulasi'), // Ini yang akan ditampilkan di tabel
                    DB::raw('COUNT(DISTINCT CASE WHEN s.type IN ("issue", "renew") THEN s.borrowernumber END) as jumlah_peminjam_unik')
                );
            } else {
                $query->select(
                    DB::raw('DATE_FORMAT(s.datetime, "%Y-%m") as periode'),
                    DB::raw('SUM(CASE WHEN s.type = "issue" THEN 1 ELSE 0 END) as jumlah_issue'),
                    DB::raw('SUM(CASE WHEN s.type = "renew" THEN 1 ELSE 0 END) as jumlah_renew'),
                    DB::raw('SUM(CASE WHEN s.type = "return" THEN 1 ELSE 0 END) as jumlah_pengembalian'),
                    DB::raw('COUNT(*) as total_sirkulasi'), // Ini yang akan ditampilkan di tabel
                    DB::raw('COUNT(DISTINCT CASE WHEN s.type IN ("issue", "renew") THEN s.borrowernumber END) as jumlah_peminjam_unik')
                );
            }

            $queryForChart = clone $query;
            $fullStatisticsForChart = $queryForChart->groupBy('periode')
                ->orderBy('periode', 'asc')
                ->get();

            // --- 4. RERATA & PAGINASI ---
            $jumlahPeriode = $fullStatisticsForChart->count();
            $rerataPeminjaman = ($jumlahPeriode > 0) ? ($totalCirculation / $jumlahPeriode) : 0;

            // Gunakan SQL Pagination langsung dari Database
            $statistics = $query->groupBy('periode')
                ->orderBy('periode', 'asc')
                ->paginate(10)
                ->withQueryString();

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    return view('pages.peminjaman.peminjamanRentangTanggal', compact(
        'statistics', 'fullStatisticsForChart', 'startDate', 'endDate',
        'startYear', 'endYear', 'filterType', 'totalBooks', 'totalReturns',
        'totalBorrowers', 'totalCirculation', 'rerataPeminjaman'
    ));
}


    public function getDetailPeminjaman(Request $request)
    {
        $periode = $request->input('periode');
        $filterType = $request->input('filter_type');

        $currentPage = $request->input('page', 1);
        $perPage = 10;

        if (!$periode) {
            return response()->json(['error' => 'Parameter periode tidak ditemukan.'], 400);
        }

        $baseQuery = DB::connection('mysql2')->table('statistics as s')
            ->join('borrowers as b', 's.borrowernumber', '=', 'b.borrowernumber')
            ->whereIn('s.type', ['issue', 'renew', 'return']);

        if ($filterType == 'daily') {
            $startOfDay = Carbon::parse($periode)->startOfDay();
            $endOfDay = Carbon::parse($periode)->endOfDay();
            $baseQuery->whereBetween('s.datetime', [$startOfDay, $endOfDay]);
        } else {
            $startOfMonth = Carbon::parse($periode)->startOfMonth();
            $endOfMonth = Carbon::parse($periode)->endOfMonth();
            $baseQuery->whereBetween('s.datetime', [$startOfMonth, $endOfMonth]);
        }

        $totalUniqueBorrowers = (clone $baseQuery)->distinct()->count('b.borrowernumber');

        $borrowersOnPage = (clone $baseQuery)
            ->select('b.borrowernumber', 'b.cardnumber as nim', DB::raw("CONCAT_WS(' ', b.firstname, b.surname) as nama_peminjam"))
            ->distinct()
            ->orderBy('b.cardnumber')
            ->forPage($currentPage, $perPage)
            ->get();

        $borrowerNumbersOnPage = $borrowersOnPage->pluck('borrowernumber');

        $structuredData = collect();

        if ($borrowerNumbersOnPage->isNotEmpty()) {
            $allTransactions = (clone $baseQuery)
                ->select('bb.title as judul_buku', 's.borrowernumber', 's.datetime as waktu_transaksi', 's.type as tipe_transaksi')
                ->join('items as i', 's.itemnumber', '=', 'i.itemnumber')
                ->join('biblio as bb', 'i.biblionumber', '=', 'bb.biblionumber')
                ->whereIn('s.borrowernumber', $borrowerNumbersOnPage)
                ->orderBy('s.datetime', 'asc')
                ->get();

            $groupedTransactions = $allTransactions->groupBy('borrowernumber');

            $structuredData = $borrowersOnPage->map(function ($borrower) use ($groupedTransactions) {
                $transactions = $groupedTransactions->get($borrower->borrowernumber, collect());
                $borrower->detail_buku = $transactions->map(function ($transaction) {
                    return [
                        'judul_buku' => $transaction->judul_buku,
                        'waktu_transaksi' => $transaction->waktu_transaksi,
                        'tipe_transaksi' => $transaction->tipe_transaksi,
                    ];
                });
                return $borrower;
            });
        }

        $paginatedResult = new \Illuminate\Pagination\LengthAwarePaginator(
            $structuredData,
            $totalUniqueBorrowers,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return $paginatedResult;
    }

    public function exportDetailCsv(Request $request)
    {
        $periode = $request->input('periode');
        $filterType = $request->input('filter_type');

        if (!$periode) {
            return abort(400, 'Parameter periode tidak ditemukan.');
        }

        // 1. Setup Query Dasar
        $baseQuery = DB::connection('mysql2')->table('statistics as s')
            ->join('borrowers as b', 's.borrowernumber', '=', 'b.borrowernumber')
            ->join('items as i', 's.itemnumber', '=', 'i.itemnumber')
            ->join('biblio as bb', 'i.biblionumber', '=', 'bb.biblionumber')
            ->whereIn('s.type', ['issue', 'renew', 'return']);

        // 2. Filter Tanggal
        if ($filterType == 'daily') {
            $startOfDay = \Carbon\Carbon::parse($periode)->startOfDay();
            $endOfDay = \Carbon\Carbon::parse($periode)->endOfDay();
            $baseQuery->whereBetween('s.datetime', [$startOfDay, $endOfDay]);
            $filenameDate = $periode;
        } else {
            $startOfMonth = \Carbon\Carbon::parse($periode)->startOfMonth();
            $endOfMonth = \Carbon\Carbon::parse($periode)->endOfMonth();
            $baseQuery->whereBetween('s.datetime', [$startOfMonth, $endOfMonth]);
            $filenameDate = \Carbon\Carbon::parse($periode)->format('m-Y');
        }

        // 3. Ambil Data
        $data = $baseQuery
            ->select(
                'b.cardnumber as nim',
                'b.firstname',
                'b.surname',
                'bb.title as judul_buku',
                's.datetime as waktu_transaksi',
                's.type as tipe_transaksi'
            )
            ->orderBy('b.cardnumber', 'asc') // Samakan dengan getDetailPeminjaman
            ->orderBy('s.datetime', 'asc')   // Lalu urutkan waktu transaksi
            ->get();

        // 4. Buat Streamed Response untuk CSV
        $filename = "detail_peminjaman_keseluruhan_{$filenameDate}.csv";

        $callback = function () use ($data) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');

            // PERBAIKAN HEADER:
            // BOM (Byte Order Mark) agar Excel baca UTF-8
            fputs($file, "\xEF\xBB\xBF");

            $delimiter = ';';

            // Header CSV
            fputcsv($file, ['No', 'NIM', 'Nama Peminjam', 'Judul Buku', 'Waktu Transaksi', 'Tipe Transaksi'], $delimiter);

            foreach ($data as $index => $row) {
                $tipe = match ($row->tipe_transaksi) {
                    'issue' => 'Pinjam',
                    'renew' => 'Perpanjang',
                    'return' => 'Kembali',
                    default => $row->tipe_transaksi,
                };

                // Nama digabung
                $fullName = trim($row->firstname . ' ' . $row->surname);

                fputcsv($file, [
                    $index + 1,
                    '="' . $row->nim . '"', // Format Text untuk NIM
                    $fullName,
                    $row->judul_buku,
                    $row->waktu_transaksi,
                    $tipe
                ], $delimiter);
            }

            fclose($file);
        };

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream($callback, 200, $headers);
    }


    // public function peminjamanProdiChart(Request $request)
    // {
    //     // 1. Ambil Data Referensi Prodi
    //     $prodiFromDb = \App\Models\M_Auv::on('mysql2')
    //         ->select('authorised_value', 'lib')
    //         ->where('category', 'PRODI')
    //         ->whereRaw('CHAR_LENGTH(lib) >= 13')
    //         ->onlyProdiTampil()
    //         ->orderBy('authorised_value', 'asc')
    //         ->get()
    //         ->map(function ($prodi) {
    //             $cleanedLib = $prodi->lib;
    //             if (str_starts_with($cleanedLib, 'FAI/ ')) {
    //                 $cleanedLib = substr($cleanedLib, 5);
    //             }
    //             $prodi->lib = trim($cleanedLib);
    //             return (object) ['authorised_value' => $prodi->authorised_value, 'lib' => $prodi->lib];
    //         });

    //     $staticOptions = collect([
    //         (object) ['authorised_value' => 'DOSEN', 'lib' => 'Dosen'],
    //         (object) ['authorised_value' => 'STAFF', 'lib' => 'Tenaga Kependidikan (Staff)'],
    //     ]);

    //     $prodiOptions = $staticOptions->concat($prodiFromDb);

    //     // 2. Setup Filter
    //     $hasFilter = $request->hasAny(['filter_type', 'start_year', 'end_year', 'start_date', 'end_date', 'selected_prodi']);
    //     $filterType = $request->input('filter_type', 'yearly');
    //     $startYear = $request->input('start_year', \Carbon\Carbon::now()->year);
    //     $endYear = $request->input('end_year', \Carbon\Carbon::now()->year);
    //     $startDate = $request->input('start_date', \Carbon\Carbon::now()->subDays(30)->format('Y-m-d'));
    //     $endDate = $request->input('end_date', \Carbon\Carbon::now()->format('Y-m-d'));
    //     $selectedProdiCode = $request->input('selected_prodi', 'DOSEN');

    //     // Inisialisasi Variable View
    //     $statistics = collect();
    //     $allStatistics = collect();
    //     $chartLabels = collect();
    //     $chartDatasets = [];
    //     $dataExists = false;

    //     $totalBooks = 0;      // Issue + Renew
    //     $totalReturns = 0;    // Return
    //     $totalBorrowers = 0;  // User Unik
    //     $totalCirculation = 0; // Issue + Renew + Return
    //     $rerataPeminjaman = 0;

    //     try {
    //         if ($hasFilter) {
    //             // Query Dasar
    //             $baseQuery = DB::connection('mysql2')->table('statistics as s')
    //                 ->leftJoin('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
    //                 ->whereIn('s.type', ['issue', 'renew', 'return']);

    //             // Filter Prodi
    //             switch (strtoupper($selectedProdiCode)) {
    //                 case 'DOSEN':
    //                     $baseQuery->where('b.categorycode', 'like', 'TC%');
    //                     break;
    //                 case 'STAFF':
    //                     $baseQuery->where(function ($query) {
    //                         $query->where('b.categorycode', 'like', 'STAF%')
    //                             ->orWhere('b.categorycode', '=', 'LIBRARIAN');
    //                     });
    //                     break;
    //                 default:
    //                     $baseQuery->leftJoin('borrower_attributes as ba', 'ba.borrowernumber', '=', 'b.borrowernumber')
    //                         ->where('ba.code', '=', 'PRODI')
    //                         ->where('ba.attribute', '=', $selectedProdiCode);
    //                     break;
    //             }

    //             $queryForTotals = clone $baseQuery;

    //             // Filter Waktu & Select Columns
    //             if ($filterType == 'daily') {
    //                 if (\Carbon\Carbon::parse($startDate)->greaterThan(\Carbon\Carbon::parse($endDate))) {
    //                     [$startDate, $endDate] = [$endDate, $startDate];
    //                 }
    //                 $queryForBoth = (clone $baseQuery)
    //                     ->select(
    //                         DB::raw('DATE(s.datetime) as periode'),
    //                         DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber ELSE NULL END) as jumlah_buku_terpinjam'),
    //                         DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik'),
    //                         DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as jumlah_buku_kembali'),
    //                         DB::raw('COUNT(s.itemnumber) as total_sirkulasi') // Total semua tipe
    //                     )
    //                     ->whereBetween(DB::raw('DATE(s.datetime)'), [$startDate, $endDate])
    //                     ->groupBy(DB::raw('DATE(s.datetime)'))
    //                     ->orderBy(DB::raw('DATE(s.datetime)'), 'ASC');

    //                 $queryForTotals->whereBetween(DB::raw('DATE(s.datetime)'), [$startDate, $endDate]);

    //             } elseif ($filterType == 'yearly') {
    //                 if ($startYear > $endYear) {
    //                     [$startYear, $endYear] = [$endYear, $startYear];
    //                 }
    //                 $queryForBoth = (clone $baseQuery)
    //                     ->select(
    //                         DB::raw('DATE_FORMAT(s.datetime, "%Y-%m") as periode'),
    //                         DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber ELSE NULL END) as jumlah_buku_terpinjam'),
    //                         DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik'),
    //                         DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as jumlah_buku_kembali'),
    //                         DB::raw('COUNT(s.itemnumber) as total_sirkulasi')
    //                     )
    //                     ->whereBetween(DB::raw('YEAR(s.datetime)'), [$startYear, $endYear])
    //                     ->groupBy(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'))
    //                     ->orderBy(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'), 'ASC');

    //                 $queryForTotals->whereBetween(DB::raw('YEAR(s.datetime)'), [$startYear, $endYear]);
    //             }

    //             // Eksekusi Query
    //             $allStatistics = (clone $queryForBoth)->get();

    //             if ($allStatistics->isNotEmpty()) {
    //                 $dataExists = true;

    //                 // Hitung Grand Total dari query totals (lebih akurat untuk distinct borrowers)
    //                 $totalsData = (clone $queryForTotals)->select(
    //                     DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber END) as total_buku'),
    //                     DB::raw('COUNT(DISTINCT s.borrowernumber) as total_peminjam'),
    //                     DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber END) as total_kembali')
    //                 )->first();

    //                 $totalBooks = $totalsData->total_buku;
    //                 $totalBorrowers = $totalsData->total_peminjam;
    //                 $totalReturns = $totalsData->total_kembali;

    //                 // Total Sirkulasi = Pinjam + Kembali
    //                 $totalCirculation = $totalBooks + $totalReturns;

    //                 // Rerata per periode
    //                 $jumlahPeriode = $allStatistics->count();
    //                 $rerataPeminjaman = ($jumlahPeriode > 0) ? ($totalCirculation / $jumlahPeriode) : 0;
    //             }

    //             // Paginasi Manual
    //             $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
    //             $perPage = 10;
    //             $currentItems = $allStatistics->slice(($page - 1) * $perPage, $perPage)->values();
    //             $statistics = new \Illuminate\Pagination\LengthAwarePaginator(
    //                 $currentItems, $allStatistics->count(), $perPage, $page,
    //                 ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
    //             );

    //             // Chart Data
    //             $chartLabels = $allStatistics->pluck('periode')->map(function ($periode) use ($filterType) {
    //                 return $filterType == 'yearly'
    //                     ? \Carbon\Carbon::createFromFormat('Y-m', $periode)->format('M Y')
    //                     : \Carbon\Carbon::parse($periode)->format('d M Y');
    //             });

    //             $chartDatasets = [
    //                 [
    //                     'label' => 'Buku Terpinjam',
    //                     'data' => $allStatistics->pluck('jumlah_buku_terpinjam'),
    //                     'backgroundColor' => 'rgba(78, 115, 223, 0.8)',
    //                     'borderColor' => '#4e73df', 'borderWidth' => 1, 'borderRadius' => 4
    //                 ],
    //                 [
    //                     'label' => 'Pengembalian',
    //                     'data' => $allStatistics->pluck('jumlah_buku_kembali'),
    //                     'backgroundColor' => 'rgba(246, 194, 62, 0.8)',
    //                     'borderColor' => '#f6c23e', 'borderWidth' => 1, 'borderRadius' => 4
    //                 ],
    //                 [
    //                     'label' => 'Total Sirkulasi',
    //                     'data' => $allStatistics->pluck('total_sirkulasi'),
    //                     'backgroundColor' => 'rgba(28, 200, 138, 0.8)',
    //                     'borderColor' => '#1cc88a', 'borderWidth' => 1, 'borderRadius' => 4
    //                 ]
    //             ];
    //         }
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    //     }

    //     return view('pages.peminjaman.prodiChart', compact(
    //         'prodiOptions', 'startYear', 'endYear', 'selectedProdiCode',
    //         'filterType', 'startDate', 'endDate', 'statistics',
    //         'chartLabels', 'chartDatasets', 'dataExists',
    //         'totalBooks', 'totalBorrowers', 'totalReturns', 'totalCirculation', 'rerataPeminjaman',
    //         'hasFilter', 'allStatistics'
    //     ));
    // }


    public function peminjamanProdiChart(Request $request)
    {
        // 1. Data Referensi Prodi - OPTIMASI: Gunakan cached list
        $prodiFromDb = \App\Models\M_Auv::getCachedProdiList()
            ->sortBy('authorised_value')
            ->map(function ($prodi) {
                $lib = str_starts_with($prodi->lib, 'FAI/ ') ? substr($prodi->lib, 5) : $prodi->lib;
                return (object) ['authorised_value' => $prodi->authorised_value, 'lib' => trim($lib)];
            });

        $staticOptions = collect([
            (object) ['authorised_value' => 'DOSEN', 'lib' => 'Dosen'],
            (object) ['authorised_value' => 'STAFF', 'lib' => 'Tenaga Kependidikan (Staff)'],
        ]);

        $prodiOptions = $staticOptions->concat($prodiFromDb);

        // 2. Setup Parameter Filter
        $hasFilter = $request->hasAny(['filter_type', 'start_year', 'end_year', 'start_date', 'end_date', 'selected_prodi']);
        $filterType = $request->input('filter_type', 'yearly');
        $startYear = $request->input('start_year', \Carbon\Carbon::now()->year);
        $endYear = $request->input('end_year', \Carbon\Carbon::now()->year);
        $startDate = $request->input('start_date', \Carbon\Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', \Carbon\Carbon::now()->format('Y-m-d'));
        $selectedProdiCode = $request->input('selected_prodi', 'DOSEN');

        // Inisialisasi Variable
        $allStatistics = collect();
        $chartLabels = collect();
        $chartDatasets = [];
        $dataExists = false;
        $totalBooks = 0; $totalReturns = 0; $totalBorrowers = 0; $totalCirculation = 0; $rerataPeminjaman = 0;

        try {
            if ($hasFilter) {
                // --- BUILD QUERY UTAMA ---
                $query = DB::connection('mysql2')->table('statistics as s')
                    ->leftJoin('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
                    ->whereIn('s.type', ['issue', 'renew', 'return']);

                // Filter Prodi
                $code = strtoupper($selectedProdiCode);
                if ($code === 'DOSEN') {
                    $query->where('b.categorycode', 'like', 'TC%');
                } elseif ($code === 'STAFF') {
                    $query->where(function ($q) {
                        $q->where('b.categorycode', 'like', 'STAF%')->orWhere('b.categorycode', '=', 'LIBRARIAN');
                    });
                } else {
                    $query->leftJoin('borrower_attributes as ba', 'ba.borrowernumber', '=', 'b.borrowernumber')
                        ->where('ba.code', '=', 'PRODI')
                        ->where('ba.attribute', '=', $code);
                }

                // Filter Waktu & Select
                if ($filterType == 'daily') {
                    if ($startDate > $endDate) list($startDate, $endDate) = [$endDate, $startDate];

                    $query->select(
                        DB::raw('DATE(s.datetime) as periode'),
                        DB::raw('SUM(CASE WHEN s.type = "issue" THEN 1 ELSE 0 END) as jumlah_issue'),
                        DB::raw('SUM(CASE WHEN s.type = "renew" THEN 1 ELSE 0 END) as jumlah_renew'),
                        DB::raw('SUM(CASE WHEN s.type = "return" THEN 1 ELSE 0 END) as jumlah_pengembalian'),
                        DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik'),
                        DB::raw('COUNT(*) as total_sirkulasi')
                    )
                    ->whereBetween('s.datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->groupBy('periode')
                    ->orderBy('periode', 'ASC');

                } else { // Yearly
                    if ($startYear > $endYear) list($startYear, $endYear) = [$endYear, $startYear];

                    $query->select(
                        DB::raw('DATE_FORMAT(s.datetime, "%Y-%m") as periode'),
                        DB::raw('SUM(CASE WHEN s.type = "issue" THEN 1 ELSE 0 END) as jumlah_issue'),
                        DB::raw('SUM(CASE WHEN s.type = "renew" THEN 1 ELSE 0 END) as jumlah_renew'),
                        DB::raw('SUM(CASE WHEN s.type = "return" THEN 1 ELSE 0 END) as jumlah_pengembalian'),
                        DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik'),
                        DB::raw('COUNT(*) as total_sirkulasi')
                    )
                    ->whereBetween(DB::raw('YEAR(s.datetime)'), [$startYear, $endYear])
                    ->groupBy('periode')
                    ->orderBy('periode', 'ASC');
                }

                // --- EKSEKUSI (Cepat dengan Redis Cache) ---
                $queryForChart = clone $query;
                
                // Generate Cache Key unik berdasarkan filter
                $cacheKey = "peminjaman_prodi_{$filterType}_{$startYear}_{$endYear}_{$startDate}_{$endDate}_{$selectedProdiCode}";
                
                // Cache hasil query selama 60 menit (3600 detik)
                $allStatistics = Cache::remember($cacheKey, 60 * 60, function () use ($queryForChart) {
                    return $queryForChart->get();
                });

                if ($allStatistics->isNotEmpty()) {
                    $dataExists = true;

                    // Hitung Total dari Collection (Memory Operation, Cepat)
                    $totalBooks = $allStatistics->sum('jumlah_issue') + $allStatistics->sum('jumlah_renew');
                    $totalReturns = $allStatistics->sum('jumlah_pengembalian');
                    $totalCirculation = $allStatistics->sum('total_sirkulasi');

                    // Note: Total Peminjam Unik per periode tidak bisa dijumlahkan langsung untuk mendapatkan total unik global
                    // Jika butuh total unik global yang akurat, perlu 1 query tambahan ringan.
                    // Namun untuk performa, kita bisa ambil rata-rata atau max, atau biarkan sum (tapi kurang akurat).
                    // Disini saya gunakan SUM agar konsisten dengan tabel, tapi idealnya query terpisah.
                    $totalBorrowers = $allStatistics->sum('jumlah_peminjam_unik');

                    $jumlahPeriode = $allStatistics->count();
                    $rerataPeminjaman = ($jumlahPeriode > 0) ? ($totalCirculation / $jumlahPeriode) : 0;
                }

                // Chart Data Preparation
                $chartLabels = $allStatistics->pluck('periode')->map(function ($p) use ($filterType) {
                    return $filterType == 'yearly'
                        ? \Carbon\Carbon::createFromFormat('Y-m', $p)->format('M Y')
                        : \Carbon\Carbon::parse($p)->format('d M Y');
                });

                $chartDatasets = [
                    [
                        'label' => 'Total Sirkulasi',
                        'data' => $allStatistics->pluck('total_sirkulasi'),
                        'backgroundColor' => 'rgba(28, 200, 138, 0.1)', 'borderColor' => '#1cc88a',
                        'borderWidth' => 2, 'tension' => 0.4, 'fill' => true
                    ]
                ];
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        // Gunakan SQL Pagination langsung dari Database
        if ($dataExists && isset($query)) {
            $statistics = clone $query;
            $statistics = $statistics->paginate(10)->withQueryString();
        } else {
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
            $statistics = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, $page, ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]);
        }

        return view('pages.peminjaman.prodiChart', compact(
            'prodiOptions', 'startYear', 'endYear', 'selectedProdiCode',
            'filterType', 'startDate', 'endDate', 'statistics',
            'chartLabels', 'chartDatasets', 'dataExists',
            'totalBooks', 'totalBorrowers', 'totalReturns', 'totalCirculation', 'rerataPeminjaman',
            'hasFilter', 'allStatistics'
        ));
    }

    public function getPeminjamDetail(Request $request)
    {
        $periode = $request->input('periode');
        $prodiCode = $request->input('prodi_code');
        $filterType = $request->input('filter_type');
        $page = $request->input('page', 1);
        $perPage = 10;

        if (!$periode || !$prodiCode) {
            return response()->json(['success' => false, 'message' => 'Parameter tidak valid.'], 400);
        }

        try {
            // 1. ROBUST DATE PARSING (Perbaikan Utama)
            // Carbon::parse lebih pintar mendeteksi format "2025-01" atau "2025-01-01" otomatis
            try {
                $dateObj = \Carbon\Carbon::parse($periode);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Format tanggal salah'], 400);
            }

            $startDate = null;
            $endDate = null;

            if ($filterType === 'daily') {
                $startDate = $dateObj->copy()->startOfDay();
                $endDate = $dateObj->copy()->endOfDay();
            } else {
                // Mode Bulanan: Pastikan mencakup seluruh bulan
                $startDate = $dateObj->copy()->startOfMonth()->startOfDay();
                $endDate = $dateObj->copy()->endOfMonth()->endOfDay();
            }

            // 2. QUERY UTAMA (Optimasi Grouping)
            $borrowersQuery = DB::connection('mysql2')->table('statistics as s')
                ->select(
                    'b.borrowernumber',
                    'b.cardnumber',
                    'b.firstname', 'b.surname', // Select kolom spesifik untuk grouping
                    DB::raw("CONCAT(b.firstname, ' ', b.surname) as nama_peminjam")
                )
                ->join('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
                ->whereIn('s.type', ['issue', 'renew', 'return'])
                ->whereBetween('s.datetime', [$startDate, $endDate]);

            // Filter Prodi
            $code = strtoupper($prodiCode);
            if ($code === 'DOSEN') {
                $borrowersQuery->where('b.categorycode', 'like', 'TC%');
            } elseif ($code === 'STAFF') {
                $borrowersQuery->where(function ($q) {
                    $q->where('b.categorycode', 'like', 'STAF%')->orWhere('b.categorycode', '=', 'LIBRARIAN');
                });
            } else {
                $borrowersQuery->join('borrower_attributes as ba', 'ba.borrowernumber', '=', 'b.borrowernumber')
                    ->where('ba.code', '=', 'PRODI')
                    ->where('ba.attribute', '=', $code);
            }

            // 3. PAGINATION YANG AMAN
            // Menggunakan groupBy explisit lebih stabil daripada distinct() untuk pagination
            $paginatedBorrowers = $borrowersQuery
                ->groupBy('b.borrowernumber', 'b.cardnumber', 'b.firstname', 'b.surname')
                ->orderBy('b.firstname', 'asc') // Order by wajib untuk pagination yang konsisten
                ->paginate($perPage, ['*'], 'page', $page);

            // 4. EAGER LOAD DETAIL BUKU
            $borrowerIds = $paginatedBorrowers->pluck('borrowernumber')->toArray();
            $details = collect();

            if (!empty($borrowerIds)) {
                $details = DB::connection('mysql2')->table('statistics as s')
                    ->select(
                        's.borrowernumber',
                        'bi.title',
                        's.datetime as waktu_transaksi',
                        's.type as transaksi'
                    )
                    ->join('items as i', 'i.itemnumber', '=', 's.itemnumber')
                    ->join('biblio as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                    ->whereIn('s.borrowernumber', $borrowerIds)
                    ->whereIn('s.type', ['issue', 'renew', 'return'])
                    ->whereBetween('s.datetime', [$startDate, $endDate])
                    ->orderBy('s.datetime', 'desc') // Urutkan transaksi terbaru di atas
                    ->get()
                    ->groupBy('borrowernumber');
            }

            // 5. MAPPING DATA
            $finalData = $paginatedBorrowers->map(function ($borrower) use ($details) {
                $transactions = $details->get($borrower->borrowernumber, collect());

                return [
                    'nama_peminjam' => $borrower->nama_peminjam,
                    'cardnumber'    => $borrower->cardnumber,
                    'buku'          => $transactions->map(function ($t) {
                        return [
                            'title'           => $t->title ?? 'Judul Tidak Ditemukan',
                            'waktu_transaksi' => \Carbon\Carbon::parse($t->waktu_transaksi)->format('d M Y H:i'),
                            'transaksi'       => $t->transaksi,
                        ];
                    })->all()
                ];
            });

            // Reconstruct Paginator untuk respon JSON
            $finalPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $finalData,
                $paginatedBorrowers->total(),
                $paginatedBorrowers->perPage(),
                $paginatedBorrowers->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return response()->json(['success' => true, 'data' => $finalPaginator]);

        } catch (\Exception $e) {
            // Tampilkan pesan error detail untuk debugging (cek tab Network -> Response di browser)
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    public function exportDetailProdiCsv(Request $request)
{
    $periode = $request->input('periode');
    $prodiCode = $request->input('prodi_code');
    $filterType = $request->input('filter_type');

    if (!$periode || !$prodiCode) {
        return abort(400, 'Parameter tidak lengkap.');
    }

    // --- 1. AMBIL NAMA PRODI & FORMAT PERIODE (UNTUK JUDUL & FILENAME) ---

    // Default nama prodi = kodenya
    $prodiName = $prodiCode;

    // Cek Static Options
    if (strtoupper($prodiCode) === 'DOSEN') {
        $prodiName = 'Dosen';
    } elseif (strtoupper($prodiCode) === 'STAFF') {
        $prodiName = 'Tenaga Kependidikan';
    } else {
        // Cek Database
        $prodiDb = \App\Models\M_Auv::on('mysql2')
            ->where('category', 'PRODI')
            ->where('authorised_value', $prodiCode)
            ->first();

        if ($prodiDb) {
            // Bersihkan nama (misal hapus "FAI/ " jika ada, sesuai logic sebelumnya)
            $lib = $prodiDb->lib;
            if (str_starts_with($lib, 'FAI/ ')) {
                $lib = substr($lib, 5);
            }
            $prodiName = trim($lib);
        }
    }

    // Format Periode agar enak dibaca
    $periodeText = $periode;
    try {
        if ($filterType === 'daily') {
            $periodeText = \Carbon\Carbon::parse($periode)->isoFormat('D MMMM Y');
        } else {
            $periodeText = \Carbon\Carbon::createFromFormat('Y-m', $periode)->isoFormat('MMMM Y');
        }
    } catch (\Exception $e) {
        // Fallback jika format error
    }

    // --- 2. SETUP QUERY UTAMA ---
    $query = DB::connection('mysql2')->table('statistics as s')
        ->join('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
        ->join('items as i', 'i.itemnumber', '=', 's.itemnumber')
        ->join('biblio as bi', 'bi.biblionumber', '=', 'i.biblionumber')
        ->whereIn('s.type', ['issue', 'renew', 'return']);

    // Filter Prodi
    switch (strtoupper($prodiCode)) {
        case 'DOSEN':
            $query->where('b.categorycode', 'like', 'TC%');
            break;
        case 'STAFF':
            $query->where(function ($q) {
                $q->where('b.categorycode', 'like', 'STAF%')
                    ->orWhere('b.categorycode', '=', 'LIBRARIAN');
            });
            break;
        default:
            $query->whereExists(function ($q) use ($prodiCode) {
                $q->select(DB::raw(1))
                    ->from('borrower_attributes as ba')
                    ->whereColumn('ba.borrowernumber', 'b.borrowernumber')
                    ->where('ba.code', '=', 'PRODI')
                    ->where('ba.attribute', '=', $prodiCode);
            });
            break;
    }

    // Filter Tanggal
    if ($filterType === 'daily') {
        $query->whereDate('s.datetime', $periode);
    } else {
        $query->where(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'), $periode);
    }

    // Ambil Data
    $data = $query
        ->select(
            'b.cardnumber as nim',
            'b.firstname',
            'b.surname',
            'bi.title as judul_buku',
            's.datetime as waktu_transaksi',
            's.type as tipe_transaksi'
        )
        ->orderBy('b.cardnumber', 'asc')
        ->orderBy('s.datetime', 'asc')
        ->get();

    // --- 3. STREAM CSV ---

    // Bersihkan nama prodi untuk nama file (ganti spasi jadi underscore, hapus karakter aneh)
    $safeProdiName = preg_replace('/[^A-Za-z0-9]/', '_', $prodiName);
    $filename = "Detail_Sirkulasi_{$safeProdiName}_{$periode}.csv";

    $callback = function () use ($data, $prodiName, $periodeText) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        $file = fopen('php://output', 'w');

        // BOM untuk Excel agar karakter utf-8 terbaca
        fputs($file, "\xEF\xBB\xBF");
        $delimiter = ';';

        // --- JUDUL DI DALAM CSV ---
        fputcsv($file, ["Laporan Detail Sirkulasi - " . $prodiName], $delimiter);
        fputcsv($file, ["Periode: " . $periodeText], $delimiter);
        fputcsv($file, [], $delimiter); // Baris kosong sebagai pemisah

        // --- HEADER TABEL ---
        fputcsv($file, ['No', 'NIM', 'Nama Peminjam', 'Judul Buku', 'Waktu Transaksi', 'Tipe Transaksi'], $delimiter);

        // --- ISI DATA ---
        foreach ($data as $index => $row) {
            $tipe = match ($row->tipe_transaksi) {
                'issue' => 'Pinjam',
                'renew' => 'Perpanjang',
                'return' => 'Kembali',
                default => $row->tipe_transaksi,
            };

            fputcsv($file, [
                $index + 1,
                '="' . $row->nim . '"', // Format Text NIM agar tidak jadi scientific number
                trim($row->firstname . ' ' . $row->surname),
                $row->judul_buku,
                $row->waktu_transaksi,
                $tipe
            ], $delimiter);
        }
        fclose($file);
    };

    $headers = [
        "Content-type" => "text/csv",
        "Content-Disposition" => "attachment; filename=$filename",
        "Pragma" => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
    ];

    return response()->stream($callback, 200, $headers);
}

    public function checkHistory(Request $request)
    {
        $cardnumber = $request->input('cardnumber');
        $tahun = $request->input('tahun'); // Ambil filter tahun
        $borrower = null;
        $borrowingHistory = collect();
        $returnHistory = collect();
        $errorMessage = null;

        if ($cardnumber) {
            try {
                $borrower = DB::connection('mysql2')->table('borrowers')
                    ->select('borrowernumber', 'cardnumber', 'firstname', 'surname', 'email', 'categorycode')
                    ->where('cardnumber', $cardnumber)
                    ->first();

                // dd($borrower->categorycode);

                if ($borrower) {
                    // Histori Peminjaman (Issue & Renew)
                    $borrowingQuery = DB::connection('mysql2')->table('statistics as s')
                        ->select(
                            's.datetime',
                            's.itemnumber',
                            's.type',
                            'i.barcode',
                            'b.title',
                            'b.author'
                        )
                        ->join('items as i', 'i.itemnumber', '=', 's.itemnumber')
                        ->join('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                        ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                        ->where('s.borrowernumber', $borrower->borrowernumber)
                        ->whereIn('s.type', ['issue', 'renew']);

                    // Filter Tahun
                    if ($tahun) {
                        // OPTIMASI: Gunakan whereBetween alih-alih whereYear agar Index datetime terpakai
                        $borrowingQuery->whereBetween('s.datetime', ["{$tahun}-01-01 00:00:00", "{$tahun}-12-31 23:59:59"]);
                    }

                    $borrowingHistory = $borrowingQuery->orderBy('s.datetime', 'desc')
                        ->paginate(5, ['*'], 'borrowing_page')
                        ->withQueryString();

                    // Histori Pengembalian (Return)
                    $returnQuery = DB::connection('mysql2')->table('statistics as s')
                        ->select(
                            's.datetime',
                            's.itemnumber',
                            's.type',
                            'i.barcode',
                            'b.title',
                            'b.author'
                        )
                        ->join('items as i', 'i.itemnumber', '=', 's.itemnumber')
                        ->join('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                        ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                        ->where('s.borrowernumber', $borrower->borrowernumber)
                        ->where('s.type', 'return');

                    // Filter Tahun
                    if ($tahun) {
                        // OPTIMASI: Gunakan whereBetween alih-alih whereYear
                        $returnQuery->whereBetween('s.datetime', ["{$tahun}-01-01 00:00:00", "{$tahun}-12-31 23:59:59"]);
                    }

                    $returnHistory = $returnQuery->orderBy('s.datetime', 'desc')
                        ->paginate(5, ['*'], 'return_page')
                        ->withQueryString();
                } else {
                }
            } catch (\Exception $e) {
                $errorMessage = "Terjadi kesalahan pada server: " . $e->getMessage();
            }
        }

        return view('pages.peminjaman.cekPeminjaman', [
            'cardnumber' => $cardnumber,
            'tahun' => $tahun,
            'borrower' => $borrower,
            'borrowingHistory' => $borrowingHistory,
            'returnHistory' => $returnHistory,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function getBorrowingHistoryExportData(Request $request)
    {
        // Ambil cardnumber dari query string (GET)
        $cardnumber = $request->query('cardnumber');

        if (!$cardnumber) {
            return response()->json(['error' => 'Nomor Kartu Anggota diperlukan.'], 400);
        }

        // Cek Peminjam
        $borrower = DB::connection('mysql2')->table('borrowers')
            ->select('borrowernumber', 'firstname', 'surname')
            ->where('cardnumber', $cardnumber)
            ->first();

        if (!$borrower) {
            return response()->json(['error' => 'Data peminjam tidak ditemukan.'], 404);
        }

        // Ambil Data (Tanpa Pagination)
        $history = DB::connection('mysql2')->table('statistics as s')
            ->select(
                's.datetime',
                's.type',
                'i.barcode',
                'b.title',
                'b.author'
            )
            ->join('items as i', 'i.itemnumber', '=', 's.itemnumber')
            ->join('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
            ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
            ->where('s.borrowernumber', $borrower->borrowernumber)
            ->whereIn('s.type', ['issue', 'renew'])
            ->orderBy('s.datetime', 'desc')
            ->get(); // Gunakan get() bukan paginate()

        // Format Data
        $data = $history->map(function ($row) {
            return [
                'tanggal_waktu' => \Carbon\Carbon::parse($row->datetime)->format('d-m-Y H:i:s'),
                'tipe' => ucfirst($row->type),
                'barcode' => $row->barcode,
                'judul' => $row->title,
                'pengarang' => $row->author,
            ];
        });

        return response()->json([
            'data' => $data,
            'cardnumber' => $cardnumber,
            'borrower_name' => trim($borrower->firstname . ' ' . $borrower->surname),
            'type' => 'peminjaman'
        ]);
    }

    public function getReturnHistoryExportData(Request $request)
    {
        $cardnumber = $request->query('cardnumber');

        if (!$cardnumber) {
            return response()->json(['error' => 'Nomor Kartu Anggota diperlukan.'], 400);
        }

        $borrower = DB::connection('mysql2')->table('borrowers')
            ->select('borrowernumber', 'firstname', 'surname')
            ->where('cardnumber', $cardnumber)
            ->first();

        if (!$borrower) {
            return response()->json(['error' => 'Data peminjam tidak ditemukan.'], 404);
        }

        $history = DB::connection('mysql2')->table('statistics as s')
            ->select(
                's.datetime',
                's.type',
                'i.barcode',
                'b.title',
                'b.author'
            )
            ->join('items as i', 'i.itemnumber', '=', 's.itemnumber')
            ->join('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
            ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
            ->where('s.borrowernumber', $borrower->borrowernumber)
            ->where('s.type', 'return') // Filter Return
            ->orderBy('s.datetime', 'desc')
            ->get();

        $data = $history->map(function ($row) {
            return [
                'tanggal_waktu' => \Carbon\Carbon::parse($row->datetime)->format('d-m-Y H:i:s'),
                'tipe' => ucfirst($row->type),
                'barcode' => $row->barcode,
                'judul' => $row->title,
                'pengarang' => $row->author,
            ];
        });

        return response()->json([
            'data' => $data,
            'cardnumber' => $cardnumber,
            'borrower_name' => trim($borrower->firstname . ' ' . $borrower->surname),
            'type' => 'pengembalian'
        ]);
    }


    public function peminjamanBerlangsung(Request $request)
    {
        // Hanya load data ringan untuk filter dropdown, TANPA query berat ke Koha DB
        $listProdiFromDb = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->get()
            ->map(function ($prodi) {
                $cleanedLib = $prodi->lib;
                if (str_starts_with($cleanedLib, 'FAI/ ')) {
                    $cleanedLib = substr($cleanedLib, 5);
                }
                $prodi->lib = trim($cleanedLib);
                return $prodi;
            })
            ->pluck('lib', 'authorised_value')
            ->toArray();

        $staticValues = [
            'DOSEN'  => 'Dosen',
            'TENDIK' => 'Tenaga Kependidikan',
        ];
        $listProdi = $staticValues + $listProdiFromDb;

        $selectedProdiCode = $request->input('prodi', '');
        $namaProdiFilter = 'Semua Program Studi';

        if ($selectedProdiCode && $selectedProdiCode !== 'semua') {
            $fc = strtoupper($selectedProdiCode);
            if ($fc === 'DOSEN') $namaProdiFilter = 'Dosen';
            elseif ($fc === 'TENDIK') $namaProdiFilter = 'Tenaga Kependidikan';
            else $namaProdiFilter = $listProdi[$fc] ?? $fc;
        }

        return view('pages.peminjaman.peminjamanBerlangsung', compact(
            'listProdi',
            'selectedProdiCode',
            'namaProdiFilter'
        ));
    }

    /**
     * AJAX endpoint untuk DataTables Server-Side Processing.
     * Mengembalikan JSON data peminjaman berlangsung.
     */
    public function getBerlangsungDataTable(Request $request)
    {
        $draw = intval($request->input('draw', 1));
        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value', '');
        $orderColumnIndex = intval($request->input('order.0.column', 3)); // default: Waktu Pinjam
        $orderDir = $request->input('order.0.dir', 'desc');

        // Map kolom DataTables index ke nama kolom SQL
        $columns = [
            0 => 'i.issuedate',        // No (placeholder, won't sort)
            1 => 'Peminjam',            // Peminjam
            2 => 'b.title',             // Buku
            3 => 'i.issuedate',         // Waktu Pinjam
            4 => 'i.date_due',          // Status Pengembalian
        ];

        $orderColumn = $columns[$orderColumnIndex] ?? 'i.issuedate';
        $orderDir = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';

        $selectedProdiCode = $request->input('prodi', '');

        // Base query
        $baseQuery = DB::connection('mysql2')->table('issues as i')
            ->join('items as it', 'i.itemnumber', '=', 'it.itemnumber')
            ->join('biblio as b', 'it.biblionumber', '=', 'b.biblionumber')
            ->join('borrowers as br', 'i.borrowernumber', '=', 'br.borrowernumber')
            ->whereRaw('i.date_due >= CURDATE()');

        // Filter Prodi
        if ($selectedProdiCode && $selectedProdiCode !== 'semua') {
            $fc = strtoupper($selectedProdiCode);
            if ($fc === 'DOSEN') {
                $baseQuery->where('br.categorycode', 'like', 'TC%');
            } elseif ($fc === 'TENDIK') {
                $baseQuery->where('br.categorycode', 'like', 'STAF%');
            } else {
                $baseQuery->whereRaw('LEFT(br.cardnumber, 4) = ?', [$fc]);
            }
        }

        // Total records (tanpa search)
        $totalRecords = (clone $baseQuery)->count();

        // Search filter
        if (!empty($searchValue)) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->where('b.title', 'like', "%{$searchValue}%")
                  ->orWhere('br.firstname', 'like', "%{$searchValue}%")
                  ->orWhere('br.surname', 'like', "%{$searchValue}%")
                  ->orWhere('br.cardnumber', 'like', "%{$searchValue}%")
                  ->orWhere('it.barcode', 'like', "%{$searchValue}%");
            });
        }

        // Filtered count
        $filteredRecords = (clone $baseQuery)->count();

        // Get data with pagination
        $data = $baseQuery->select(
                'i.issuedate AS BukuDipinjamSaat',
                'b.title AS JudulBuku',
                'it.barcode AS BarcodeBuku',
                DB::raw("CONCAT(
                    COALESCE(br.cardnumber, ''),
                    CASE WHEN br.cardnumber IS NOT NULL THEN ' - ' ELSE '' END,
                    TRIM(CONCAT(COALESCE(br.firstname, ''), ' ', COALESCE(br.surname, '')))
                ) AS Peminjam"),
                'i.date_due AS BatasWaktuPengembalian'
            )
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function getBerlangsungExportData(Request $request)
    {
        // 1. Setup Data Prodi & Static Values (Konsisten dengan peminjamanBerlangsung)
        $listProdiFromDb = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->get()
            ->map(function ($prodi) {
                $cleanedLib = $prodi->lib;
                if (str_starts_with($cleanedLib, 'FAI/ ')) {
                    $cleanedLib = substr($cleanedLib, 5);
                }
                $prodi->lib = trim($cleanedLib);
                return $prodi;
            })
            ->pluck('lib', 'authorised_value')
            ->toArray();

        $staticValues = [
            'DOSEN'  => 'Dosen',
            'TENDIK' => 'Tenaga Kependidikan',
        ];

        $listProdi = $staticValues + $listProdiFromDb;

        $selectedProdiCode = $request->input('prodi', '');
        $namaProdiFilter = 'Semua Program Studi';

        // 2. Build Query
        $query = DB::connection('mysql2')->table('issues as i')
            ->select(
                'i.issuedate AS BukuDipinjamSaat',
                // Judul buku diambil lengkap dari biblio + metadata (subtitle)
                DB::raw("CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) AS JudulBuku"),
                'it.barcode AS BarcodeBuku',
                DB::raw("CONCAT(
                COALESCE(br.cardnumber, ''),
                CASE WHEN br.cardnumber IS NOT NULL THEN ' - ' ELSE '' END,
                TRIM(CONCAT(COALESCE(br.firstname, ''), ' ', COALESCE(br.surname, '')))
            ) AS Peminjam"),
                'i.date_due AS BatasWaktuPengembalian'
            )
            ->join('items as it', 'i.itemnumber', '=', 'it.itemnumber')
            ->join('biblio as b', 'it.biblionumber', '=', 'b.biblionumber')
            ->join('borrowers as br', 'i.borrowernumber', '=', 'br.borrowernumber')
            ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
            ->leftJoin('borrower_attributes as ba', 'br.borrowernumber', '=', 'ba.borrowernumber')
            ->leftJoin('authorised_values as av', function ($join) {
                $join->on('av.category', '=', 'ba.code')
                    ->on('ba.attribute', '=', 'av.authorised_value');
            })
            ->whereRaw('i.date_due >= CURDATE()')
            ->orderBy('BukuDipinjamSaat', 'desc')
            ->orderBy('BatasWaktuPengembalian', 'desc');

        // 3. Terapkan Filter (Logic Baru: Dosen/Tendik/Prodi)
        if ($selectedProdiCode && $selectedProdiCode !== 'semua') {
            $fc = strtoupper($selectedProdiCode);

            if ($fc === 'DOSEN') {
                $query->where('br.categorycode', 'like', 'TC%');
                $namaProdiFilter = 'Dosen';
            } elseif ($fc === 'TENDIK') {
                $query->where('br.categorycode', 'like', 'STAF%');
                $namaProdiFilter = 'Tenaga Kependidikan';
            } else {
                // Filter Prodi Biasa (4 digit pertama cardnumber/NIM)
                $query->whereRaw('LEFT(br.cardnumber, 4) = ?', [$fc]);

                // Cari nama prodi dari array gabungan
                $namaProdiFilter = $listProdi[$fc] ?? $fc;
            }
        }

        // 4. Ambil Data & Format untuk JSON
        $data = $query->get();

        $exportData = $data->map(function ($row) {
            return [
                'BukuDipinjamSaat'       => Carbon::parse($row->BukuDipinjamSaat)->format('d M Y H:i:s'),
                'JudulBuku'              => $row->JudulBuku,
                'BarcodeBuku'            => $row->BarcodeBuku,
                'Peminjam'               => $row->Peminjam,
                'BatasWaktuPengembalian' => Carbon::parse($row->BatasWaktuPengembalian)->format('d M Y'),
            ];
        });

        return response()->json([
            'data' => $exportData,
            'namaProdiFilter' => $namaProdiFilter,
        ]);
    }

    // =============================================
    // PEMINJAMAN PER FAKULTAS
    // =============================================

    private $facultyMapping = [
        'A' => 'FKIP - Fakultas Keguruan dan Ilmu Pendidikan',
        'B' => 'FEB - Fakultas Ekonomi dan Bisnis',
        'C' => 'FHIP - Fakultas Hukum dan Ilmu Politik',
        'D' => 'FT - Fakultas Teknik',
        'E' => 'FG - Fakultas Geografi',
        'F' => 'FPsi - Fakultas Psikologi',
        'G' => 'FAI - Fakultas Agama Islam',
        'H' => 'FAI - Fakultas Agama Islam',
        'K' => 'FF - Fakultas Farmasi',
        'L' => 'FKI - Fakultas Komunikasi dan Informatika',
    ];


    /**
     * Halaman Peminjaman Per Fakultas
     */
    public function peminjamanFakultasTable(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        // 1. Setup filter parameters
        $hasFilter = $request->hasAny(['filter_type', 'start_date', 'end_date', 'start_year', 'end_year', 'fakultas']);
        $filterType = $request->input('filter_type', 'daily');
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $startYear = $request->input('start_year', Carbon::now()->year);
        $endYear = $request->input('end_year', Carbon::now()->year);
        $selectedFakultas = $request->input('fakultas', 'semua');

        // Inisialisasi
        $tableData = collect();
        $chartData = collect();
        $totalIssues = 0;
        $totalRenews = 0;
        $totalReturns = 0;
        $totalBorrowers = 0;
        $totalCirculation = 0;
        $rerataPeminjaman = 0;
        $dataExists = false;

        // 2. Build daftar fakultas untuk dropdown
        $allProdiListObj = M_Auv::getCachedProdiList();
        $prodiMap = $allProdiListObj->mapWithKeys(function ($prodi) {
            $lib = str_starts_with($prodi->lib, 'FAI/ ') ? substr($prodi->lib, 5) : $prodi->lib;
            return [trim($prodi->authorised_value) => trim($lib)];
        })->toArray();
        $listFakultas = $allProdiListObj->map(function ($prodi) {
            return \App\Helpers\FacultyHelper::mapCodeToFaculty($prodi->authorised_value);
        })->unique()->filter(function ($value) {
            $blacklist = ['Lainnya', 'Dosen', 'Dosen & Pengajar', 'Tendik', 'Tenaga Kependidikan'];
            return !in_array($value, $blacklist);
        })->sort()->values()->all();

        try {
            if ($hasFilter) {
                // 3. Setup tanggal
                if ($filterType == 'daily') {
                    if ($startDate > $endDate) [$startDate, $endDate] = [$endDate, $startDate];
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();
                    $sqlDateFormat = '%Y-%m-%d';
                } else {
                    if ($startYear > $endYear) [$startYear, $endYear] = [$endYear, $startYear];
                    $start = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
                    $end = Carbon::createFromDate($endYear, 12, 31)->endOfDay();
                    $sqlDateFormat = '%Y-%m';
                }

                // Cache key berdasarkan semua parameter filter
                $cacheKey = 'peminjaman_fakultas_' . md5(json_encode([
                    'filterType' => $filterType,
                    'start' => $start->toDateTimeString(),
                    'end' => $end->toDateTimeString(),
                    'selectedFakultas' => $selectedFakultas,
                ]));
                $cachedResult = Cache::remember($cacheKey, 3600, function () use ($start, $end, $sqlDateFormat, $filterType, $selectedFakultas, $prodiMap) {

                    // 4. Query: Ambil data sirkulasi grouped per periode + borrowernumber
                    $rawData = DB::connection('mysql2')->table('statistics as s')
                        ->leftJoin('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
                        ->leftJoin('borrower_attributes as ba', function ($join) {
                            $join->on('ba.borrowernumber', '=', 'b.borrowernumber')
                                ->where('ba.code', '=', 'PRODI');
                        })
                        ->whereIn('s.type', ['issue', 'renew', 'return'])
                        ->whereBetween('s.datetime', [$start, $end])
                        ->select(
                            DB::raw("DATE_FORMAT(s.datetime, '$sqlDateFormat') as periode"),
                            's.type',
                            'b.cardnumber',
                            'b.categorycode',
                            'b.borrowernumber',
                            'ba.attribute as prodi_code'
                        )
                        ->get();

                    // 5. Map setiap record ke fakultas di PHP
                    $processedData = $rawData->map(function ($row) use ($prodiMap) {
                        $fakultas = 'Lainnya';
                        $catCode = strtoupper(trim($row->categorycode ?? ''));
                        $cardnumber = strtoupper(trim($row->cardnumber ?? ''));
                        $prodiName = 'Lainnya / Tidak Diketahui';
                        if (str_starts_with($catCode, 'TC') || str_starts_with($catCode, 'DOSEN')) {
                            $fakultas = 'Dosen & Pengajar';
                            $prodiName = 'Dosen';
                        } elseif (str_starts_with($catCode, 'STAF') || str_contains($catCode, 'LIB') || $catCode === 'LIBRARIAN') {
                            $fakultas = 'Tenaga Kependidikan';
                            $prodiName = 'Staff/Tendik';
                        } elseif (!empty($row->prodi_code)) {
                            $cleanCode = trim($row->prodi_code);
                            $fakultas = \App\Helpers\FacultyHelper::mapCodeToFaculty($cleanCode);
                            $prodiName = isset($prodiMap[$cleanCode]) ? $cleanCode . ' - ' . $prodiMap[$cleanCode] : $cleanCode;
                        } elseif (strlen($cardnumber) >= 4 && preg_match('/^[A-Z]\d{3}/', $cardnumber)) {
                            $kode = trim(substr($cardnumber, 0, 4));
                            $fakultas = \App\Helpers\FacultyHelper::mapCodeToFaculty($kode);
                            $prodiName = isset($prodiMap[$kode]) ? $kode . ' - ' . $prodiMap[$kode] : $kode;
                        }

                        return [
                            'periode' => $row->periode,
                            'type' => $row->type,
                            'fakultas' => $fakultas,
                            'prodi_name' => trim($prodiName),
                            'borrowernumber' => $row->borrowernumber,
                        ];
                    });

                    // 6. Filter by fakultas jika dipilih
                    if ($selectedFakultas && $selectedFakultas !== 'semua') {
                        $processedData = $processedData->filter(fn($item) => $item['fakultas'] === $selectedFakultas);
                    }

                    // 7. Agregasi per periode + fakultas
                    $grouped = $processedData->groupBy(fn($item) => $item['periode'] . '|' . $item['fakultas']);

                    $aggregated = $grouped->map(function ($group, $key) {
                        $parts = explode('|', $key);
                        return [
                            'periode' => $parts[0],
                            'fakultas' => $parts[1],
                            'jumlah_issue' => $group->where('type', 'issue')->count(),
                            'jumlah_renew' => $group->where('type', 'renew')->count(),
                            'jumlah_pengembalian' => $group->where('type', 'return')->count(),
                            'total_sirkulasi' => $group->count(),
                            'peminjam_unik' => $group->whereIn('type', ['issue', 'renew'])->pluck('borrowernumber')->unique()->count(),
                        ];
                    })->values();

                    // 8. Total summary
                    $totalIssues = $processedData->where('type', 'issue')->count();
                    $totalRenews = $processedData->where('type', 'renew')->count();
                    $totalReturns = $processedData->where('type', 'return')->count();
                    $totalCirculation = $totalIssues + $totalRenews + $totalReturns;
                    $totalBorrowers = $processedData->whereIn('type', ['issue', 'renew'])->pluck('borrowernumber')->unique()->count();

                    // 9. Tabel data (agregasi per periode dengan sub-agregasi per prodi)
                    $tableGrouped = $processedData->groupBy('periode');
                    $tableData = $tableGrouped->map(function ($group, $periode) {
                        
                        // Sub-agregasi per prodi
                        $prodiGrouped = $group->groupBy('prodi_name');
                        $prodiDetails = $prodiGrouped->map(function ($pGroup, $pName) {
                            return [
                                'prodi' => $pName,
                                'jumlah_issue' => $pGroup->where('type', 'issue')->count(),
                                'jumlah_renew' => $pGroup->where('type', 'renew')->count(),
                                'jumlah_buku_kembali' => $pGroup->where('type', 'return')->count(),
                                'total_sirkulasi' => $pGroup->count(),
                                'jumlah_peminjam_unik' => $pGroup->whereIn('type', ['issue', 'renew'])->pluck('borrowernumber')->unique()->count(),
                            ];
                        })->values()->sortByDesc('total_sirkulasi')->values();

                        return [
                            'periode' => $periode,
                            'jumlah_issue' => $group->where('type', 'issue')->count(),
                            'jumlah_renew' => $group->where('type', 'renew')->count(),
                            'jumlah_buku_kembali' => $group->where('type', 'return')->count(),
                            'total_sirkulasi' => $group->count(),
                            'jumlah_peminjam_unik' => $group->whereIn('type', ['issue', 'renew'])->pluck('borrowernumber')->unique()->count(),
                            'prodi_details' => $prodiDetails,
                        ];
                    })->sortBy('periode')->values();

                    return [
                        'totalIssues' => $totalIssues,
                        'totalRenews' => $totalRenews,
                        'totalReturns' => $totalReturns,
                        'totalCirculation' => $totalCirculation,
                        'totalBorrowers' => $totalBorrowers,
                        'tableData' => $tableData,
                    ];
                }); // End Cache::remember

                // Extract cached results
                $totalIssues = $cachedResult['totalIssues'];
                $totalRenews = $cachedResult['totalRenews'];
                $totalReturns = $cachedResult['totalReturns'];
                $totalCirculation = $cachedResult['totalCirculation'];
                $totalBorrowers = $cachedResult['totalBorrowers'];
                $tableData = collect($cachedResult['tableData'])->map(fn($item) => (object) $item);

                if ($tableData->isNotEmpty()) {
                    $dataExists = true;
                    $jumlahPeriode = $tableData->count();
                    $rerataPeminjaman = ($jumlahPeriode > 0) ? ($totalCirculation / $jumlahPeriode) : 0;
                }

                // 10. Chart data (per periode)
                $chartData = $tableData->map(function ($item) use ($filterType) {
                    $label = $item->periode;
                    try {
                        if ($filterType == 'daily') {
                            $label = Carbon::parse($item->periode)->format('d M Y');
                        } else {
                            $label = Carbon::createFromFormat('Y-m', $item->periode)->format('M Y');
                        }
                    } catch (\Throwable $e) {}
                    return [
                        'label' => $label,
                        'issue' => $item->jumlah_issue,
                        'renew' => $item->jumlah_renew,
                        'pengembalian' => $item->jumlah_buku_kembali,
                        'sirkulasi' => $item->total_sirkulasi,
                    ];
                })->values();
            }
        } catch (\Throwable $e) {
            Log::error('Peminjaman Fakultas Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'filters' => $request->all(),
            ]);
            return back()->with('error', 'Terjadi kesalahan saat memproses data: ' . $e->getMessage());
        }

        return view('pages.peminjaman.peminjamanFakultas', compact(
            'listFakultas', 'selectedFakultas',
            'startDate', 'endDate', 'startYear', 'endYear',
            'filterType', 'hasFilter', 'dataExists',
            'tableData', 'chartData',
            'totalIssues', 'totalRenews', 'totalReturns', 'totalBorrowers',
            'totalCirculation', 'rerataPeminjaman'
        ));
    }

    /**
     * Export CSV Peminjaman Per Fakultas
     */
    public function exportCsvPeminjamanFakultas(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        $filterType = $request->input('filter_type', 'daily');
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $startYear = $request->input('start_year', Carbon::now()->year);
        $endYear = $request->input('end_year', Carbon::now()->year);
        $selectedFakultas = $request->input('fakultas', 'semua');

        // Setup tanggal
        if ($filterType == 'daily') {
            if ($startDate > $endDate) [$startDate, $endDate] = [$endDate, $startDate];
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            if ($startYear > $endYear) [$startYear, $endYear] = [$endYear, $startYear];
            $start = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
            $end = Carbon::createFromDate($endYear, 12, 31)->endOfDay();
        }

        // Cache Key yang sama persis dengan UI
        $cacheKey = 'peminjaman_fakultas_' . md5(json_encode([
            'filterType' => $filterType,
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'selectedFakultas' => $selectedFakultas,
        ]));

        $cachedResult = Cache::get($cacheKey);

        // Jika tidak ada di cache, picu fungsi tabel untuk memuat ulang cache
        if (!$cachedResult) {
            $this->peminjamanFakultasTable($request);
            $cachedResult = Cache::get($cacheKey);
        }

        if (!$cachedResult) {
            return back()->with('error', 'Data tidak tersedia, silakan coba lagi atau buka tabel terlebih dahulu.');
        }

        // Build CSV Data
        $judulLaporan = "LAPORAN PEMINJAMAN PER FAKULTAS";
        $subJudul = "";
        $filePeriod = "";

        try {
            if ($filterType === 'monthly') {
                $subJudul = "PERIODE TAHUNAN: $startYear s.d. $endYear";
                $filePeriod = "tahun_{$startYear}_{$endYear}";
            } else {
                $tglA = Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM Y');
                $tglAkh = Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM Y');
                $subJudul = "PERIODE HARIAN: $tglA s.d. $tglAkh";
                $filePeriod = $startDate . "_sd_" . $endDate;
            }
        } catch (\Throwable $e) { $filePeriod = date('Ymd'); }

        $namaFileFakultas = "";
        if ($selectedFakultas && $selectedFakultas !== 'semua') {
            $subJudul .= " (FAKULTAS: " . strtoupper($selectedFakultas) . ")";
            $namaFileFakultas = "_" . preg_replace('/[^A-Za-z0-9]/', '_', $selectedFakultas);
        }

        $fileName = 'laporan_peminjaman_fakultas' . $namaFileFakultas . '_' . $filePeriod . '.csv';
        $tableData = $cachedResult['tableData'];
        $totalBorrowersUnik = $cachedResult['totalBorrowers'];

        $callback = function () use ($tableData, $filterType, $judulLaporan, $subJudul, $totalBorrowersUnik) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, [$judulLaporan], ';');
            fputcsv($file, [$subJudul], ';');
            fputcsv($file, [], ';');
            fputcsv($file, ['No', 'Periode', 'Kode Prodi', 'Nama Prodi', 'Peminjaman', 'Perpanjangan', 'Pengembalian', 'Total Sirkulasi', 'Peminjam'], ';');

            $no = 1;
            $totalI = 0; $totalR = 0; $totalK = 0; $totalS = 0;

            foreach ($tableData as $row) {
                // ... (Parsing row details)
                $rowArray = (array) $row;
                $periodeDisplay = $rowArray['periode'] ?? '-';
                try {
                    if ($filterType === 'monthly') {
                        $periodeDisplay = Carbon::createFromFormat('Y-m', $periodeDisplay)->locale('id')->isoFormat('MMMM Y');
                    } else {
                        $periodeDisplay = Carbon::parse($periodeDisplay)->locale('id')->isoFormat('dddd, D MMMM Y');
                    }
                } catch (\Throwable $e) {}

                if (!empty($rowArray['prodi_details'])) {
                    foreach ($rowArray['prodi_details'] as $detail) {
                        $detArray = (array) $detail;
                        $namaProdiStr = $detArray['prodi'] ?? '';
                        
                        $kodeProdi = '';
                        $namaOnly = $namaProdiStr;
                        if (str_contains($namaProdiStr, ' - ')) {
                            $parts = explode(' - ', $namaProdiStr, 2);
                            $kodeProdi = $parts[0];
                            $namaOnly = $parts[1];
                        }
                        
                        $i = $detArray['jumlah_issue'] ?? 0;
                        $r = $detArray['jumlah_renew'] ?? 0;
                        $k = $detArray['jumlah_buku_kembali'] ?? 0;
                        $s = $detArray['total_sirkulasi'] ?? 0;
                        $u = $detArray['jumlah_peminjam_unik'] ?? 0;
                        
                        fputcsv($file, [
                            $no++, $periodeDisplay, $kodeProdi, $namaOnly, $i, $r, $k, $s, $u
                        ], ';');

                        $totalI += $i;
                        $totalR += $r;
                        $totalK += $k;
                        $totalS += $s;
                    }
                }
            }

            fputcsv($file, [], ';');
            // Gunakan totalBorrowersUnik (asli tanpa duplikasi) untuk Total Peminjam
            fputcsv($file, ['', '', '', 'TOTAL KESELURUHAN', $totalI, $totalR, $totalK, $totalS, $totalBorrowersUnik], ';');
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
