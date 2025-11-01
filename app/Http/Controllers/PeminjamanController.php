<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

class PeminjamanController extends Controller
{
    public function pertanggal(Request $request)
    {
        $filterType = $request->input('filter_type', 'daily');
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $startYear = $request->input('start_year', Carbon::now()->year);
        $endYear = $request->input('end_year', Carbon::now()->year);

        // --- 2. Inisialisasi Variabel Kosong ---
        $totalBooks = 0;
        $totalReturns = 0;
        $totalBorrowers = 0;
        $fullStatisticsForChart = collect();
        $statistics = new LengthAwarePaginator([], 0, 10, 1, [
            'path' => Paginator::resolveCurrentPath(),
            'query' => $request->query(),
        ]);

        $hasFilter = $request->filled('start_date') || $request->filled('end_date') || $request->filled('start_year') || $request->filled('end_year');

        if ($hasFilter) {
            try {
                $dateRange = [];

                if ($filterType == 'daily') {
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();
                    if ($start->greaterThan($end)) {
                        [$start, $end] = [$end, $start];
                        $startDate = $start->format('Y-m-d');
                        $endDate = $end->format('Y-m-d');
                    }
                    $dateRange = [$start, $end];

                    // Query untuk total (daily)
                    $summaryData = DB::connection('mysql2')->table('statistics as s')
                        ->select(
                            DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN 1 END) as total_books'),
                            DB::raw('COUNT(CASE WHEN s.type = "return" THEN 1 END) as total_returns'),
                            DB::raw('COUNT(DISTINCT CASE WHEN s.type IN ("issue", "renew") THEN s.borrowernumber END) as total_borrowers')
                        )
                        ->whereBetween('s.datetime', $dateRange)
                        ->first();

                    // Query utama (daily)
                    $mainQuery = DB::connection('mysql2')->table('statistics as s')
                        ->whereIn('s.type', ['issue', 'renew'])
                        ->whereBetween('s.datetime', $dateRange)
                        ->select(
                            DB::raw('DATE(s.datetime) as periode'),
                            DB::raw('COUNT(s.itemnumber) as jumlah_peminjaman_buku'),
                            DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik')
                        )
                        ->groupBy('periode')
                        ->orderBy('periode', 'asc');
                } else { // filterType == 'yearly'
                    if ($startYear > $endYear) {
                        [$startYear, $endYear] = [$endYear, $startYear];
                    }

                    // Query untuk total (yearly range)
                    $summaryData = DB::connection('mysql2')->table('statistics as s')
                        ->select(
                            DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN 1 END) as total_books'),
                            DB::raw('COUNT(CASE WHEN s.type = "return" THEN 1 END) as total_returns'),
                            DB::raw('COUNT(DISTINCT CASE WHEN s.type IN ("issue", "renew") THEN s.borrowernumber END) as total_borrowers')
                        )
                        ->whereBetween(DB::raw('YEAR(s.datetime)'), [$startYear, $endYear])
                        ->first();

                    // Query utama (yearly range)
                    $mainQuery = DB::connection('mysql2')->table('statistics as s')
                        ->whereIn('s.type', ['issue', 'renew'])
                        ->whereBetween(DB::raw('YEAR(s.datetime)'), [$startYear, $endYear])
                        ->select(
                            DB::raw('DATE_FORMAT(s.datetime, "%Y-%m") as periode'),
                            DB::raw('COUNT(s.itemnumber) as jumlah_peminjaman_buku'),
                            DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik')
                        )
                        ->groupBy('periode')
                        ->orderBy('periode', 'asc');
                }

                if ($summaryData) {
                    $totalBooks = $summaryData->total_books;
                    $totalReturns = $summaryData->total_returns;
                    $totalBorrowers = $summaryData->total_borrowers;
                }

                $fullStatisticsForChart = $mainQuery->get();
                if ($fullStatisticsForChart->isNotEmpty()) {

                    $statistics = (clone $mainQuery)->paginate(10)->withQueryString();
                }
            } catch (\Exception $e) {
                //  Log::error('Error fetching pertanggal statistics: ' . $e->getMessage() . "\n"_ . $e->getTraceAsString());
                return redirect()->back()->with('error', 'Terjadi kesalahan saat mengambil data: ' . $e->getMessage());
            }
        }
        return view('pages.peminjaman.peminjamanRentangTanggal', compact(
            'statistics',
            'fullStatisticsForChart',
            'startDate',
            'endDate',
            'startYear',
            'endYear',
            'filterType',
            'totalBooks',
            'totalReturns',
            'totalBorrowers'
        ));
    }


    // public function getPeminjamDetail(Request $request)
    // {
    //     $periode = $request->input('periode');
    //     $filterType = $request->input('filter_type');

    //     $query = DB::connection('mysql2')->table('statistics as s')
    //         ->join('borrowers as b', 's.borrowernumber', '=', 'b.borrowernumber')
    //         ->join('items as i', 's.itemnumber', '=', 'i.itemnumber')
    //         ->join('biblio as bib', 'i.biblionumber', '=', 'bib.biblionumber')
    //         ->select('s.borrowernumber', 'b.cardnumber', DB::raw("TRIM(CONCAT(COALESCE(b.firstname, ''), ' ', COALESCE(b.surname, ''))) as nama_peminjam"), 's.type as tipe_transaksi', 's.datetime as waktu_transaksi', 'bib.title as judul_buku')
    //         ->whereIn('s.type', ['issue', 'renew', 'return']); // Ambil semua tipe transaksi untuk detail

    //     if ($filterType == 'daily') {
    //         $query->whereDate('s.datetime', $periode);
    //     } else { // yearly (periode adalah YYYY-MM)
    //         $query->where(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'), $periode);
    //     }

    //     // Query untuk data mentah
    //     $detailData = $query->orderBy('nama_peminjam', 'asc')->orderBy('s.datetime', 'asc')->get();

    //     // Kelompokkan di PHP
    //     $groupedData = $detailData->groupBy('borrowernumber')->map(function ($items) {
    //         $first = $items->first();
    //         return [
    //             'nama_peminjam' => $first->nama_peminjam,
    //             'cardnumber' => $first->cardnumber,
    //             'detail_buku' => $items->map(function ($item) {
    //                 return [
    //                     'judul_buku' => $item->judul_buku,
    //                     'tipe_transaksi' => $item->tipe_transaksi,
    //                     'waktu_transaksi' => Carbon::parse($item->waktu_transaksi)->format('Y-m-d H:i')
    //                 ];
    //             })
    //         ];
    //     })->values(); // Reset keys

    //     // Paginasi manual
    //     $currentPage = Paginator::resolveCurrentPage('page');
    //     $perPage = 5; // 5 peminjam per halaman modal
    //     $currentPageItems = $groupedData->slice(($currentPage - 1) * $perPage, $perPage);
    //     $paginatedData = new LengthAwarePaginator($currentPageItems, $groupedData->count(), $perPage, $currentPage, [
    //         'path' => $request->url(), // Gunakan URL request
    //         'query' => $request->query() // Pertahankan query string
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'data' => $paginatedData // Kirim objek paginator
    //     ]);
    // }

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
                'path' => $request->url(), // URL dasar untuk paginasi
                'query' => $request->query(), // Bawa serta parameter filter
            ]
        );

        return $paginatedResult;
    }

    // public function peminjamanProdiChart(Request $request)
    // {
    //     $prodiFromDb = DB::connection('mysql2')->table('authorised_values')
    //         ->select('authorised_value', 'lib')
    //         ->where('category', 'PRODI')
    //         ->whereRaw('CHAR_LENGTH(lib) >= 13')
    //         ->orderBy('lib', 'asc')
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

    //     $hasFilter = $request->hasAny(['filter_type', 'start_year', 'end_year', 'start_date', 'end_date', 'selected_prodi']);
    //     $filterType = $request->input('filter_type', 'yearly');
    //     $startYear = $request->input('start_year', Carbon::now()->year);
    //     $endYear = $request->input('end_year', Carbon::now()->year);

    //     $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
    //     $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
    //     $selectedProdiCode = $request->input('selected_prodi', 'DOSEN');

    //     $statistics = collect();
    //     $allStatistics = collect();
    //     $chartLabels = collect();
    //     $chartDatasets = [];
    //     $dataExists = false;
    //     $totalBooks = 0;
    //     $totalBorrowers = 0;
    //     $totalReturns = 0;

    //     try {
    //         if ($hasFilter) {
    //             $baseQuery = DB::connection('mysql2')->table('statistics as s')
    //                 ->leftJoin('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
    //                 ->whereIn('s.type', ['issue', 'renew', 'return']);

    //             switch (strtoupper($selectedProdiCode)) {
    //                 case 'DOSEN':
    //                     $baseQuery->where('b.categorycode', 'like', 'TC%');
    //                     break;
    //                 case 'STAFF':
    //                     $baseQuery->where('b.categorycode', 'like', 'STAF%');
    //                     break;
    //                 default:
    //                     $baseQuery->leftJoin('borrower_attributes as ba', 'ba.borrowernumber', '=', 'b.borrowernumber')
    //                         ->where('ba.code', '=', 'PRODI')
    //                         ->where('ba.attribute', '=', $selectedProdiCode);
    //                     break;
    //             }

    //             $queryForTotals = clone $baseQuery;

    //             if ($filterType == 'daily') {
    //                 if (Carbon::parse($startDate)->greaterThan(Carbon::parse($endDate))) {
    //                     [$startDate, $endDate] = [$endDate, $startDate];
    //                 }
    //                 $queryForBoth = (clone $baseQuery)
    //                     ->select(
    //                         DB::raw('DATE(s.datetime) as periode'),
    //                         DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber ELSE NULL END) as jumlah_buku_terpinjam'),
    //                         DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik'),
    //                         DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as jumlah_buku_kembali')
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
    //                         DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as jumlah_buku_kembali')
    //                     )
    //                     ->whereBetween(DB::raw('YEAR(s.datetime)'), [$startYear, $endYear])
    //                     ->groupBy(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'))
    //                     ->orderBy(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'), 'ASC');

    //                 $queryForTotals->whereYear('s.datetime', [$startYear, $endYear]);
    //             }

    //             $allStatistics = (clone $queryForBoth)->get();

    //             $allStatistics = (clone $queryForBoth)->get();

    //             if ($allStatistics->isNotEmpty()) {
    //                 $dataExists = true;
    //                 $totalsQuery = (clone $queryForTotals)->select(
    //                     DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber ELSE NULL END) as total_buku'),
    //                     DB::raw('COUNT(DISTINCT s.borrowernumber) as total_peminjam'),
    //                     DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as total_kembali')
    //                 );

    //                 $totals = $totalsQuery->first();
    //                 $totalBooks = $totals->total_buku;
    //                 $totalBorrowers = $totals->total_peminjam;
    //                 $totalReturns = $totals->total_kembali;
    //             }

    //             $statistics = (clone $queryForBoth)->paginate(10);

    //             $chartLabels = $allStatistics->pluck('periode')->map(function ($periode) use ($filterType) {
    //                 return $filterType == 'yearly'
    //                     ? Carbon::createFromFormat('Y-m', $periode)->format('M Y')
    //                     : Carbon::parse($periode)->format('d M Y');
    //             });

    //             $chartDatasets = [
    //                 ['label' => 'Jumlah Buku Terpinjam', 'data' => $allStatistics->pluck('jumlah_buku_terpinjam'), /* ... sisa data ... */],
    //                 ['label' => 'Jumlah Buku Dikembalikan', 'data' => $allStatistics->pluck('jumlah_buku_kembali'), /* ... sisa data ... */],
    //                 ['label' => 'Jumlah Peminjam', 'data' => $allStatistics->pluck('jumlah_peminjam_unik'), /* ... sisa data ... */]
    //             ];
    //         }
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    //     }

    //     return view('pages.peminjaman.prodiChart', compact('prodiOptions', 'startYear', 'endYear', 'selectedProdiCode', 'filterType', 'startDate', 'endDate', 'statistics', 'chartLabels', 'chartDatasets', 'dataExists', 'totalBooks', 'totalBorrowers', 'totalReturns', 'hasFilter', 'allStatistics'));
    // }

    public function peminjamanProdiChart(Request $request)
    {
        $prodiFromDb = DB::connection('mysql2')->table('authorised_values')
            ->select('authorised_value', 'lib')
            ->where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('authorised_value', 'asc')
            ->get()
            ->map(function ($prodi) {
                $cleanedLib = $prodi->lib;
                if (str_starts_with($cleanedLib, 'FAI/ ')) {
                    $cleanedLib = substr($cleanedLib, 5);
                }
                $prodi->lib = trim($cleanedLib);
                return (object) ['authorised_value' => $prodi->authorised_value, 'lib' => $prodi->lib];
            });

        $staticOptions = collect([
            (object) ['authorised_value' => 'DOSEN', 'lib' => 'Dosen'],
            (object) ['authorised_value' => 'STAFF', 'lib' => 'Tenaga Kependidikan (Staff)'],
        ]);

        $prodiOptions = $staticOptions->concat($prodiFromDb);

        $hasFilter = $request->hasAny(['filter_type', 'start_year', 'end_year', 'start_date', 'end_date', 'selected_prodi']);
        $filterType = $request->input('filter_type', 'yearly');
        $startYear = $request->input('start_year', Carbon::now()->year);
        $endYear = $request->input('end_year', Carbon::now()->year);

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $selectedProdiCode = $request->input('selected_prodi', 'DOSEN');

        $statistics = collect();
        $allStatistics = collect();
        $chartLabels = collect();
        $chartDatasets = [];
        $dataExists = false;
        $totalBooks = 0;
        $totalBorrowers = 0;
        $totalReturns = 0;

        try {
            if ($hasFilter) {
                $baseQuery = DB::connection('mysql2')->table('statistics as s')
                    ->leftJoin('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
                    ->whereIn('s.type', ['issue', 'renew', 'return']);

                switch (strtoupper($selectedProdiCode)) {
                    case 'DOSEN':
                        $baseQuery->where('b.categorycode', 'like', 'TC%');
                        break;
                    case 'STAFF':
                        $baseQuery->where('b.categorycode', 'like', 'STAF%');
                        break;
                    default:
                        $baseQuery->leftJoin('borrower_attributes as ba', 'ba.borrowernumber', '=', 'b.borrowernumber')
                            ->where('ba.code', '=', 'PRODI')
                            ->where('ba.attribute', '=', $selectedProdiCode);
                        break;
                }

                $queryForTotals = clone $baseQuery;

                if ($filterType == 'daily') {
                    if (Carbon::parse($startDate)->greaterThan(Carbon::parse($endDate))) {
                        [$startDate, $endDate] = [$endDate, $startDate];
                    }
                    $queryForBoth = (clone $baseQuery)
                        ->select(
                            DB::raw('DATE(s.datetime) as periode'),
                            DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber ELSE NULL END) as jumlah_buku_terpinjam'),
                            DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik'),
                            DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as jumlah_buku_kembali')
                        )
                        ->whereBetween(DB::raw('DATE(s.datetime)'), [$startDate, $endDate])
                        ->groupBy(DB::raw('DATE(s.datetime)'))
                        ->orderBy(DB::raw('DATE(s.datetime)'), 'ASC');

                    $queryForTotals->whereBetween(DB::raw('DATE(s.datetime)'), [$startDate, $endDate]);
                } elseif ($filterType == 'yearly') {
                    if ($startYear > $endYear) {
                        [$startYear, $endYear] = [$endYear, $startYear];
                    }
                    $queryForBoth = (clone $baseQuery)
                        ->select(
                            DB::raw('DATE_FORMAT(s.datetime, "%Y-%m") as periode'),
                            DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber ELSE NULL END) as jumlah_buku_terpinjam'),
                            DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik'),
                            DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as jumlah_buku_kembali')
                        )
                        ->whereBetween(DB::raw('YEAR(s.datetime)'), [$startYear, $endYear])
                        ->groupBy(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'))
                        ->orderBy(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'), 'ASC');


                    $queryForTotals->whereBetween(DB::raw('YEAR(s.datetime)'), [$startYear, $endYear]);
                }

                $allStatistics = (clone $queryForBoth)->get();

                if ($allStatistics->isNotEmpty()) {
                    $dataExists = true;
                    $totalsQuery = (clone $queryForTotals)->select(
                        DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber ELSE NULL END) as total_buku'),
                        DB::raw('COUNT(DISTINCT s.borrowernumber) as total_peminjam'),
                        DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as total_kembali')
                    );

                    $totals = $totalsQuery->first();
                    $totalBooks = $totals->total_buku;
                    $totalBorrowers = $totals->total_peminjam;
                    $totalReturns = $totals->total_kembali;
                }

                $statistics = (clone $queryForBoth)->paginate(10);

                $chartLabels = $allStatistics->pluck('periode')->map(function ($periode) use ($filterType) {
                    return $filterType == 'yearly'
                        ? Carbon::createFromFormat('Y-m', $periode)->format('M Y')
                        : Carbon::parse($periode)->format('d M Y');
                });

                $chartDatasets = [
                    ['label' => 'Jumlah Buku Terpinjam', 'data' => $allStatistics->pluck('jumlah_buku_terpinjam'), 'backgroundColor' => 'rgba(75, 192, 192, 0.8)', 'borderColor' => 'rgba(75, 192, 192, 1)'],
                    ['label' => 'Jumlah Buku Dikembalikan', 'data' => $allStatistics->pluck('jumlah_buku_kembali'), 'backgroundColor' => 'rgba(255, 99, 132, 0.8)', 'borderColor' => 'rgba(255, 99, 132, 1)'],
                    ['label' => 'Jumlah Peminjam', 'data' => $allStatistics->pluck('jumlah_peminjam_unik'), 'backgroundColor' => 'rgba(153, 102, 255, 0.8)', 'borderColor' => 'rgba(153, 102, 255, 1)']
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error in peminjamanProdiChart: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        return view('pages.peminjaman.prodiChart', compact('prodiOptions', 'startYear', 'endYear', 'selectedProdiCode', 'filterType', 'startDate', 'endDate', 'statistics', 'chartLabels', 'chartDatasets', 'dataExists', 'totalBooks', 'totalBorrowers', 'totalReturns', 'hasFilter', 'allStatistics'));
    }

    public function getPeminjamDetail(Request $request)
    {
        $periode = $request->input('periode');
        $prodiCode = $request->input('prodi_code');
        $filterType = $request->input('filter_type');
        $page = $request->input('page', 1);
        $perPage = 10;

        if (!$periode || !$prodiCode || !in_array($filterType, ['daily', 'yearly'])) {
            return response()->json(['success' => false, 'message' => 'Parameter tidak valid.'], 400);
        }

        try {
            $borrowersQuery = DB::connection('mysql2')->table('statistics as s')
                ->select('b.borrowernumber', 'b.cardnumber', DB::raw("CONCAT(b.firstname, ' ', b.surname) as nama_peminjam"))
                ->join('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
                ->whereIn('s.type', ['issue', 'renew', 'return']);

            switch (strtoupper($prodiCode)) {
                case 'DOSEN':
                    $borrowersQuery->where('b.categorycode', 'like', 'TC%');
                    break;
                case 'STAFF':
                    $borrowersQuery->where('b.categorycode', 'like', 'STAF%');
                    break;
                default:
                    $borrowersQuery->whereExists(function ($query) use ($prodiCode) {
                        $query->select(DB::raw(1))
                            ->from('borrower_attributes as ba')
                            ->whereColumn('ba.borrowernumber', 'b.borrowernumber')
                            ->where('ba.code', '=', 'PRODI')
                            ->where('ba.attribute', '=', $prodiCode);
                    });
                    break;
            }

            if ($filterType === 'daily') {
                $borrowersQuery->whereDate('s.datetime', $periode);
            } elseif ($filterType === 'yearly') {
                $borrowersQuery->where(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'), $periode);
            }

            $paginatedBorrowers = $borrowersQuery->groupBy('b.borrowernumber', 'b.cardnumber', 'nama_peminjam')
                ->paginate($perPage, ['*'], 'page', $page);

            $borrowerNumbersOnPage = $paginatedBorrowers->pluck('borrowernumber');

            $details = [];
            if ($borrowerNumbersOnPage->isNotEmpty()) {
                $detailsQuery = DB::connection('mysql2')->table('statistics as s')
                    ->select('s.borrowernumber', 'bi.title', 's.datetime as waktu_transaksi', 's.type as transaksi')
                    ->join('items as i', 'i.itemnumber', '=', 's.itemnumber')
                    ->join('biblio as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                    ->whereIn('s.borrowernumber', $borrowerNumbersOnPage)
                    ->whereIn('s.type', ['issue', 'renew', 'return']);

                if ($filterType === 'daily') {
                    $detailsQuery->whereDate('s.datetime', $periode);
                } elseif ($filterType === 'yearly') {
                    $detailsQuery->where(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'), $periode);
                }

                $details = $detailsQuery->orderBy('s.datetime', 'asc')->get()->groupBy('borrowernumber');
            }

            $finalData = $paginatedBorrowers->map(function ($borrower) use ($details) {
                $borrowerTransactions = $details->get($borrower->borrowernumber, collect());

                return [
                    'nama_peminjam' => $borrower->nama_peminjam,
                    'cardnumber' => $borrower->cardnumber,
                    'buku' => $borrowerTransactions->map(function ($detail) {
                        return [
                            'title' => $detail->title,
                            'waktu_transaksi' => Carbon::parse($detail->waktu_transaksi)->format('d M Y H:i:s'),
                            'transaksi' => $detail->transaksi,
                        ];
                    })->all(),
                ];
            });

            $finalPaginator = new LengthAwarePaginator(
                $finalData,
                $paginatedBorrowers->total(),
                $paginatedBorrowers->perPage(),
                $paginatedBorrowers->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return response()->json(['success' => true, 'data' => $finalPaginator]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data: ' . $e->getMessage()], 500);
        }
    }

    public function checkHistory(Request $request)
    {
        $cardnumber = $request->input('cardnumber');
        $borrower = null;
        $borrowingHistory = collect();
        $returnHistory = collect();
        $errorMessage = null;

        if ($cardnumber) {
            try {
                $borrower = DB::connection('mysql2')->table('borrowers')
                    ->select('borrowernumber', 'cardnumber', 'firstname', 'surname', 'email', 'phone')
                    ->where('cardnumber', $cardnumber)
                    ->first();

                if ($borrower) {
                    // Histori Peminjaman (Issue & Renew)
                    $borrowingHistory = DB::connection('mysql2')->table('statistics as s')
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
                        ->whereIn('s.type', ['issue', 'renew'])
                        ->orderBy('s.datetime', 'desc')
                        ->paginate(5, ['*'], 'borrowing_page')
                        ->withQueryString();

                    // Histori Pengembalian (Return)
                    $returnHistory = DB::connection('mysql2')->table('statistics as s')
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
                        ->where('s.type', 'return')
                        ->orderBy('s.datetime', 'desc')
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
            'borrower' => $borrower,
            'borrowingHistory' => $borrowingHistory,
            'returnHistory' => $returnHistory,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function getBorrowingHistoryExportData(Request $request)
    {
        $cardnumber = $request->input('cardnumber');

        if (!$cardnumber) {
            return response()->json(['error' => 'Nomor Kartu Anggota (Cardnumber) diperlukan.'], 400);
        }

        $borrower = DB::connection('mysql2')->table('borrowers')
            ->select('borrowernumber', 'cardnumber', 'firstname', 'surname')
            ->where('cardnumber', $cardnumber)
            ->first();

        if (!$borrower) {
            return response()->json(['error' => 'Nomor kartu peminjam tidak ditemukan.'], 404);
        }
        $borrowingHistory = DB::connection('mysql2')->table('statistics as s')
            ->select(
                's.datetime',
                's.type', // issue, renew
                'i.barcode',
                'b.title',
                'b.author'
            )
            ->leftJoin('items as i', 'i.itemnumber', '=', 's.itemnumber')
            ->leftJoin('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
            ->leftJoin('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
            ->where('s.borrowernumber', $borrower->borrowernumber)
            ->whereIn('s.type', ['issue', 'renew'])
            ->orderBy('s.datetime', 'desc')
            ->get();

        $exportData = $borrowingHistory->map(function ($history) {
            return [
                'tanggal_waktu' => Carbon::parse($history->datetime)->format('d M Y H:i:s'),
                'tipe' => ucfirst($history->type),
                'barcode_buku' => $history->barcode,
                'judul_buku' => $history->title,
                'pengarang' => $history->author,
            ];
        });

        return response()->json([
            'data' => $exportData,
            'cardnumber' => $cardnumber,
            'borrower_name' => $borrower->firstname . ' ' . $borrower->surname,
            'type' => 'peminjaman'
        ]);
    }

    public function getReturnHistoryExportData(Request $request)
    {
        $cardnumber = $request->input('cardnumber');

        if (!$cardnumber) {
            return response()->json(['error' => 'Nomor Kartu Anggota (Cardnumber) diperlukan.'], 400);
        }

        $borrower = DB::connection('mysql2')->table('borrowers')
            ->select('borrowernumber', 'cardnumber', 'firstname', 'surname')
            ->where('cardnumber', $cardnumber)
            ->first();

        if (!$borrower) {
            return response()->json(['error' => 'Nomor kartu peminjam tidak ditemukan.'], 404);
        }

        $returnHistory = DB::connection('mysql2')->table('statistics as s')
            ->select(
                's.datetime',
                's.type',
                'i.barcode',
                'b.title',
                'b.author'
            )
            ->leftJoin('items as i', 'i.itemnumber', '=', 's.itemnumber')
            ->leftJoin('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
            ->leftJoin('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
            ->where('s.borrowernumber', $borrower->borrowernumber)
            ->where('s.type', 'return')
            ->orderBy('s.datetime', 'desc')
            ->get();

        $exportData = $returnHistory->map(function ($history) {
            return [
                'tanggal_waktu' => Carbon::parse($history->datetime)->format('d M Y H:i:s'),
                'tipe' => ucfirst($history->type),
                'barcode_buku' => $history->barcode,
                'judul_buku' => $history->title,
                'pengarang' => $history->author,
            ];
        });

        return response()->json([
            'data' => $exportData,
            'cardnumber' => $cardnumber,
            'borrower_name' => $borrower->firstname . ' ' . $borrower->surname,
            'type' => 'pengembalian'
        ]);
    }

    public function peminjamanBerlangsung(Request $request)
    {
        $listProdi = DB::connection('mysql2')->table('authorised_values')
            ->select('authorised_value', 'lib')
            ->where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('authorised_value', 'asc')
            ->get()
            ->map(function ($prodi) {
                $cleanedLib = $prodi->lib;
                if (str_starts_with($cleanedLib, 'FAI/ ')) {
                    $cleanedLib = substr($cleanedLib, 5);
                }
                $prodi->lib = trim($cleanedLib);
                return $prodi;
            });

        $selectedProdiCode = $request->input('prodi', '');

        $namaProdiFilter = 'Semua Program Studi';

        try {
            $query = DB::connection('mysql2')->table('issues as i')
                ->select(
                    'i.issuedate AS BukuDipinjamSaat',
                    DB::raw("CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) AS JudulBuku"),
                    'it.barcode AS BarcodeBuku',
                    'av.authorised_value AS KodeProdi',
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

            if ($selectedProdiCode && $selectedProdiCode !== 'semua') {
                $query->whereRaw('LEFT(br.cardnumber, 4) = ?', [$selectedProdiCode]);

                $foundProdi = $listProdi->firstWhere('authorised_value', $selectedProdiCode);
                if ($foundProdi) {
                    $namaProdiFilter = $foundProdi->lib;
                }
            }

            $activeLoans = $query->paginate(10)->withQueryString();
            $dataExists = $activeLoans->isNotEmpty();

            // Logika untuk export CSV
            // if ($request->has('export_csv')) {
            //     $dataToExport = $query->get();
            //     return $this->exportCsvPeminjamanBerlangsung($dataToExport, $namaProdiFilter);
            // }
        } catch (\Exception $e) {
            // Log the error for debugging
            // \Log::error('Error fetching active loans: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengambil data peminjaman berlangsung: ' . $e->getMessage());
        }

        return view('pages.peminjaman.peminjamanBerlangsung', compact(
            'activeLoans',
            'listProdi',
            'selectedProdiCode',
            'namaProdiFilter',
            'dataExists'
        ));
    }

    public function getBerlangsungExportData(Request $request)
    {
        $selectedProdiCode = $request->input('prodi', '');
        $listProdi = DB::connection('mysql2')->table('authorised_values')
            ->select('authorised_value', 'lib')
            ->where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('lib', 'asc')
            ->get();

        $namaProdiFilter = 'Semua Program Studi';
        $query = DB::connection('mysql2')->table('issues as i')
            ->select(
                'i.issuedate AS BukuDipinjamSaat',
                'b.title AS JudulBuku',
                'it.barcode AS BarcodeBuku',
                // 'av.authorised_value AS KodeProdi',
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
            ->leftJoin('borrower_attributes as ba', 'br.borrowernumber', '=', 'ba.borrowernumber')
            ->leftJoin('authorised_values as av', function ($join) {
                $join->on('av.category', '=', 'ba.code')
                    ->on('ba.attribute', '=', 'av.authorised_value');
            })
            ->whereRaw('i.date_due >= CURDATE()')
            ->orderBy('BukuDipinjamSaat', 'desc')
            ->orderBy('BatasWaktuPengembalian', 'desc');

        if ($selectedProdiCode) {
            $query->whereRaw('LEFT(br.cardnumber, 4) = ?', [$selectedProdiCode]);
            $foundProdi = $listProdi->firstWhere('authorised_value', $selectedProdiCode);
            if ($foundProdi) {
                $namaProdiFilter = $foundProdi->lib;
            }
        }

        $data = $query->get();

        $exportData = $data->map(function ($row) {
            return [
                'BukuDipinjamSaat' => Carbon::parse($row->BukuDipinjamSaat)->format('d M Y H:i:s'),
                'JudulBuku' => $row->JudulBuku,
                'BarcodeBuku' => $row->BarcodeBuku,
                // 'KodeProdi' => $row->KodeProdi,
                'Peminjam' => $row->Peminjam,
                'BatasWaktuPengembalian' => Carbon::parse($row->BatasWaktuPengembalian)->format('d M Y'),
            ];
        });

        return response()->json([
            'data' => $exportData,
            'namaProdiFilter' => $namaProdiFilter,
        ]);
    }
}
