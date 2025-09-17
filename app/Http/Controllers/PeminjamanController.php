<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class PeminjamanController extends Controller
{

    public function pertanggal(Request $request)
    {
        // Mengambil input dari request dengan nilai default
        $filterType = $request->input('filter_type', 'daily');
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $selectedYear = $request->input('selected_year', Carbon::now()->year);

        // Inisialisasi variabel total
        $totalBooks = 0;
        $totalReturns = 0;
        $totalBorrowers = 0;

        $statistics = new LengthAwarePaginator([], 0, 10);
        $fullStatisticsForChart = collect();

        if ($request->filled('start_date') || $request->filled('end_date') || $request->filled('selected_year')) {
            try {
                // Tentukan rentang tanggal/tahun terlebih dahulu
                $dateRange = [];
                if ($filterType == 'daily') {
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();
                    if ($start->greaterThan($end)) {
                        [$start, $end] = [$end, $start];
                    }
                    $dateRange = [$start, $end];
                } else { // monthly
                    $yearStart = Carbon::create($selectedYear)->startOfYear();
                    $yearEnd = Carbon::create($selectedYear)->endOfYear();
                    $dateRange = [$yearStart, $yearEnd];
                }
                $summaryData = DB::connection('mysql2')->table('statistics as s')
                    ->select(
                        DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN 1 END) as total_books'),
                        DB::raw('COUNT(CASE WHEN s.type = "return" THEN 1 END) as total_returns'),
                        DB::raw('COUNT(DISTINCT CASE WHEN s.type IN ("issue", "renew") THEN s.borrowernumber END) as total_borrowers')
                    )
                    ->whereBetween('s.datetime', $dateRange)
                    ->first();

                if ($summaryData) {
                    $totalBooks = $summaryData->total_books;
                    $totalReturns = $summaryData->total_returns;
                    $totalBorrowers = $summaryData->total_borrowers;
                }

                // --- Query utama untuk tabel & chart (tetap sama) ---
                $mainQuery = DB::connection('mysql2')->table('statistics as s')
                    ->whereIn('s.type', ['issue', 'renew'])
                    ->whereBetween('s.datetime', $dateRange);

                if ($filterType == 'daily') {
                    $mainQuery->select(
                        DB::raw('DATE(s.datetime) as periode'),
                        DB::raw('COUNT(s.itemnumber) as jumlah_peminjaman_buku'),
                        DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik')
                    )
                        ->groupBy('periode')
                        ->orderBy('periode', 'asc');
                } else { // monthly
                    $mainQuery->select(
                        DB::raw('DATE_FORMAT(s.datetime, "%Y-%m") as periode'),
                        DB::raw('COUNT(s.itemnumber) as jumlah_peminjaman_buku'),
                        DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik')
                    )
                        ->groupBy('periode')
                        ->orderBy('periode', 'asc');
                }

                $fullStatisticsForChart = $mainQuery->get();
                $statistics = (clone $mainQuery)->paginate(10)->withQueryString();
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Terjadi kesalahan saat mengambil data: ' . $e->getMessage());
            }
        }

        return view('pages.peminjaman.peminjamanRentangTanggal', compact(
            'statistics',
            'fullStatisticsForChart',
            'startDate',
            'endDate',
            'selectedYear',
            'filterType',
            'totalBooks',
            'totalReturns',
            'totalBorrowers'
        ));
    }

    public function getDetailPeminjaman(Request $request)
    {
        $periode = $request->input('periode');
        $filterType = $request->input('filter_type');

        // 1. Dapatkan halaman saat ini dari request, default-nya halaman 1
        $currentPage = $request->input('page', 1);
        $perPage = 10;

        if (!$periode) {
            return response()->json(['error' => 'Parameter periode tidak ditemukan.'], 400);
        }

        // Query dasar untuk mendapatkan transaksi dalam rentang waktu yang dipilih
        $baseQuery = DB::connection('mysql2')->table('statistics as s')
            ->join('borrowers as b', 's.borrowernumber', '=', 'b.borrowernumber')
            ->whereIn('s.type', ['issue', 'renew', 'return']);

        if ($filterType == 'daily') {
            $startOfDay = Carbon::parse($periode)->startOfDay();
            $endOfDay = Carbon::parse($periode)->endOfDay();
            $baseQuery->whereBetween('s.datetime', [$startOfDay, $endOfDay]);
        } else { // 'monthly'
            $startOfMonth = Carbon::parse($periode)->startOfMonth();
            $endOfMonth = Carbon::parse($periode)->endOfMonth();
            $baseQuery->whereBetween('s.datetime', [$startOfMonth, $endOfMonth]);
        }

        // 2. Hitung total PEMINJAM UNIK secara eksplisit dan akurat
        $totalUniqueBorrowers = (clone $baseQuery)->distinct()->count('b.borrowernumber');

        // 3. Ambil data PEMINJAM UNIK HANYA untuk halaman saat ini
        $borrowersOnPage = (clone $baseQuery)
            ->select('b.borrowernumber', 'b.cardnumber as nim', DB::raw("CONCAT_WS(' ', b.firstname, b.surname) as nama_peminjam"))
            ->distinct()
            ->orderBy('b.cardnumber')
            ->forPage($currentPage, $perPage)
            ->get();

        $borrowerNumbersOnPage = $borrowersOnPage->pluck('borrowernumber');

        $structuredData = collect(); // Siapkan koleksi kosong

        if ($borrowerNumbersOnPage->isNotEmpty()) {
            // 4. Ambil SEMUA transaksi HANYA untuk peminjam yang ada di halaman ini
            $allTransactions = (clone $baseQuery)
                ->select('bb.title as judul_buku', 's.borrowernumber', 's.datetime as waktu_transaksi', 's.type as tipe_transaksi')
                ->join('items as i', 's.itemnumber', '=', 'i.itemnumber')
                ->join('biblio as bb', 'i.biblionumber', '=', 'bb.biblionumber')
                ->whereIn('s.borrowernumber', $borrowerNumbersOnPage)
                ->orderBy('s.datetime', 'asc')
                ->get();

            // 5. Kelompokkan transaksi berdasarkan borrowernumber
            $groupedTransactions = $allTransactions->groupBy('borrowernumber');

            // 6. Gabungkan data peminjam dengan detail transaksinya
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

        // 7. Buat instance LengthAwarePaginator secara manual dengan total yang sudah benar
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

    public function peminjamanProdiChart(Request $request)
    {
        // Mengambil opsi program studi dari authorized_value
        $prodiOptions = DB::connection('mysql2')->table('authorised_values')
            ->select('authorised_value', 'lib')
            ->where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('lib', 'asc')
            ->get()
            ->map(function ($prodi) {
                $cleanedLib = $prodi->lib;
                if (str_starts_with($cleanedLib, 'FAI/ ')) {
                    $cleanedLib = substr($cleanedLib, 5);
                }
                $prodi->lib = trim($cleanedLib);
                return $prodi;
            });

        // Tentukan apakah filter telah diterapkan
        $hasFilter = $request->has('filter_type') || $request->has('selected_year') || $request->has('start_date') || $request->has('end_date') || $request->has('selected_prodi');

        // Inisialisasi nilai filter default
        $filterType = $request->input('filter_type', 'yearly');
        $selectedYear = $request->input('selected_year', Carbon::now()->year);
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $selectedProdiCode = $request->input('selected_prodi', $prodiOptions->first()->authorised_value ?? null);

        $perPage = 10;
        $page = $request->input('page', 1);

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
                    ->leftJoin('borrower_attributes as ba', 'ba.borrowernumber', '=', 'b.borrowernumber')
                    ->leftJoin('authorised_values as av', function ($join) {
                        $join->on('av.authorised_value', '=', 'ba.attribute')
                            ->where('av.category', '=', 'PRODI')
                            ->where('ba.code', '=', 'PRODI');
                    })
                    ->whereIn('s.type', ['issue', 'renew', 'return'])
                    ->where('av.authorised_value', $selectedProdiCode);

                $queryForTotals = clone $baseQuery;

                if ($filterType == 'daily') {
                    if (Carbon::parse($startDate)->greaterThan(Carbon::parse($endDate))) {
                        $temp = $startDate;
                        $startDate = $endDate;
                        $endDate = $temp;
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

                    $queryForTotals->select(
                        DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber ELSE NULL END) as total_buku'),
                        DB::raw('COUNT(DISTINCT s.borrowernumber) as total_peminjam'),
                        DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as total_kembali')
                    )
                        ->whereBetween(DB::raw('DATE(s.datetime)'), [$startDate, $endDate]);

                    $statistics = (clone $queryForBoth)->paginate($perPage, ['*'], 'page', $page);
                    $allStatistics = (clone $queryForBoth)->get();
                    $chartLabels = $allStatistics->pluck('periode')->map(fn($date) => Carbon::parse($date)->format('d M Y'));
                } elseif ($filterType == 'yearly') {
                    $queryForBoth = (clone $baseQuery)
                        ->select(
                            DB::raw('DATE_FORMAT(s.datetime, "%Y-%m") as periode'),
                            DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber ELSE NULL END) as jumlah_buku_terpinjam'),
                            DB::raw('COUNT(DISTINCT s.borrowernumber) as jumlah_peminjam_unik'),
                            DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as jumlah_buku_kembali')
                        )
                        ->whereYear('s.datetime', $selectedYear)
                        ->groupBy(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'))
                        ->orderBy(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'), 'ASC');

                    $queryForTotals->select(
                        DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN s.itemnumber ELSE NULL END) as total_buku'),
                        DB::raw('COUNT(DISTINCT s.borrowernumber) as total_peminjam'),
                        DB::raw('COUNT(CASE WHEN s.type = "return" THEN s.itemnumber ELSE NULL END) as total_kembali')
                    )
                        ->whereYear('s.datetime', $selectedYear);

                    $statistics = (clone $queryForBoth)->paginate($perPage, ['*'], 'page', $page);
                    $allStatistics = (clone $queryForBoth)->get();
                    $chartLabels = $allStatistics->pluck('periode')->map(fn($periode) => Carbon::createFromFormat('Y-m', $periode)->format('M Y'));
                }

                if ($allStatistics->isNotEmpty()) {
                    $dataExists = true;

                    $totals = $queryForTotals->first();
                    $totalBooks = $totals->total_buku;
                    $totalBorrowers = $totals->total_peminjam;
                    $totalReturns = $totals->total_kembali;

                    $chartDatasets[] = [
                        'label' => 'Jumlah Buku Terpinjam',
                        'data' => $allStatistics->pluck('jumlah_buku_terpinjam'),
                        'borderColor' => 'rgba(75, 192, 192, 1)',
                        'backgroundColor' => 'rgba(75, 192, 192, 0.4)',
                        'tension' => 0.1,
                        'fill' => true,
                    ];

                    $chartDatasets[] = [
                        'label' => 'Jumlah Buku Dikembalikan',
                        'data' => $allStatistics->pluck('jumlah_buku_kembali'),
                        'borderColor' => 'rgba(255, 99, 132, 1)',
                        'backgroundColor' => 'rgba(255, 99, 132, 0.4)',
                        'tension' => 0.1,
                        'fill' => true,
                    ];

                    $chartDatasets[] = [
                        'label' => 'Jumlah Peminjam',
                        'data' => $allStatistics->pluck('jumlah_peminjam_unik'),
                        'borderColor' => 'rgba(153, 102, 255, 1)',
                        'backgroundColor' => 'rgba(153, 102, 255, 0.4)',
                        'tension' => 0.1,
                        'fill' => true,
                    ];
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengambil data statistik: ' . $e->getMessage());
        }

        return view('pages.peminjaman.prodiChart', [
            'prodiOptions' => $prodiOptions,
            'selectedYear' => $selectedYear,
            'selectedProdiCode' => $selectedProdiCode,
            'filterType' => $filterType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statistics' => $statistics,
            'allStatistics' => $allStatistics,
            'chartLabels' => $chartLabels,
            'chartDatasets' => $chartDatasets,
            'dataExists' => $dataExists,
            'totalBooks' => $totalBooks,
            'totalBorrowers' => $totalBorrowers,
            'totalReturns' => $totalReturns,
            'hasFilter' => $hasFilter,
        ]);
    }

    public function getPeminjamDetail(Request $request)
    {
        $periode = $request->input('periode');
        $prodiCode = $request->input('prodi_code');
        $filterType = $request->input('filter_type');

        if (!$periode || !$prodiCode) {
            return response()->json(['success' => false, 'message' => 'Invalid parameters.'], 400);
        }
        // Tambahkan validasi untuk filterType
        $filterType = $request->input('filter_type');
        if (!in_array($filterType, ['daily', 'yearly'])) {
            return response()->json(['success' => false, 'message' => 'Invalid filter type.'], 400);
        }

        if (!in_array($filterType, ['daily', 'yearly'])) {
            return response()->json(['success' => false, 'message' => 'Invalid filter type.'], 400);
        }

        $perPage = 5;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        try {
            $baseQuery = DB::connection('mysql2')->table('statistics as s')
                ->select(
                    'b.cardnumber',
                    DB::raw("CONCAT(b.firstname, ' ', b.surname) as nama_peminjam"),
                    'bi.title',
                    DB::raw('s.datetime as waktu_transaksi'),
                    DB::raw('s.type')
                )
                ->leftJoin('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
                ->leftJoin('borrower_attributes as ba', 'ba.borrowernumber', '=', 'b.borrowernumber')
                ->leftJoin('authorised_values as av', function ($join) {
                    $join->on('av.authorised_value', '=', 'ba.attribute')
                        ->where('av.category', '=', 'PRODI')
                        ->where('ba.code', '=', 'PRODI');
                })
                ->leftJoin('items as i', 'i.itemnumber', '=', 's.itemnumber')
                ->leftJoin('biblio as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                ->whereIn('s.type', ['issue', 'renew', 'return'])
                ->where('av.authorised_value', $prodiCode);

            if ($filterType === 'daily') {
                $baseQuery->whereDate('s.datetime', $periode);
            } elseif ($filterType === 'yearly') {
                $baseQuery->where(DB::raw('DATE_FORMAT(s.datetime, "%Y-%m")'), $periode);
            }

            $borrowerDetails = $baseQuery->orderBy('s.datetime', 'asc')->get();

            $groupedData = [];
            foreach ($borrowerDetails as $detail) {
                if (!isset($groupedData[$detail->cardnumber])) {
                    $groupedData[$detail->cardnumber] = [
                        'nama_peminjam' => $detail->nama_peminjam,
                        'cardnumber' => $detail->cardnumber,
                        'buku' => [],
                    ];
                }
                if ($detail->title) {
                    $groupedData[$detail->cardnumber]['buku'][] = [
                        'title' => $detail->title,
                        'waktu_transaksi' => Carbon::parse($detail->waktu_transaksi)->format('d M Y H:i:s'),
                        'transaksi' => $detail->type,
                    ];
                }
            }

            $finalData = array_values($groupedData);

            $paginatedData = array_slice($finalData, $offset, $perPage);
            $totalItems = count($finalData);
            $totalPages = ceil($totalItems / $perPage);

            return response()->json([
                'success' => true,
                'data' => $paginatedData,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'perPage' => $perPage,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch data: ' . $e->getMessage()], 500);
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
                        // Menggunakan JOIN untuk memastikan hanya data dengan item yang valid yang tampil
                        ->join('items as i', 'i.itemnumber', '=', 's.itemnumber')
                        ->join('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                        ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                        ->where('s.borrowernumber', $borrower->borrowernumber)
                        ->whereIn('s.type', ['issue', 'renew'])
                        ->orderBy('s.datetime', 'desc')
                        ->paginate(5, ['*'], 'borrowing_page') // Beri nama unik untuk paginasi
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
                        // Menggunakan JOIN untuk memastikan hanya data dengan item yang valid yang tampil
                        ->join('items as i', 'i.itemnumber', '=', 's.itemnumber')
                        ->join('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                        ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                        ->where('s.borrowernumber', $borrower->borrowernumber)
                        ->where('s.type', 'return')
                        ->orderBy('s.datetime', 'desc')
                        ->paginate(5, ['*'], 'return_page') // Beri nama unik untuk paginasi
                        ->withQueryString();
                } else {
                    // Pesan ini akan ditampilkan di view jika $borrower tidak ditemukan
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
                's.type', // return
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
            ->orderBy('lib', 'asc')
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
                    'b.title AS JudulBuku',
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

            $activeLoans = $query->paginate(10)->withQueryString();
            $dataExists = $activeLoans->isNotEmpty();

            // Logika untuk export CSV
            if ($request->has('export_csv')) {
                $dataToExport = $query->get();
                return $this->exportCsvPeminjamanBerlangsung($dataToExport, $namaProdiFilter);
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            // \Log::error('Error fetching active loans: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            // return redirect()->back()->with('error', 'Terjadi kesalahan saat mengambil data peminjaman berlangsung: ' . $e->getMessage());
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
            ->leftJoin('borrower_attributes as ba', 'br.borrowernumber', '=', 'ba.borrowernumber')
            ->leftJoin('authorised_values as av', function ($join) {
                $join->on('av.category', '=', 'ba.code')
                    ->on('ba.attribute', '=', 'av.authorised_value');
            })
            ->whereRaw('i.date_due >= CURDATE()')
            ->orderBy('BukuDipinjamSaat', 'asc')
            ->orderBy('BatasWaktuPengembalian', 'asc');

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
                'KodeProdi' => $row->KodeProdi,
                'Peminjam' => $row->Peminjam,
                'BatasWaktuPengembalian' => Carbon::parse($row->BatasWaktuPengembalian)->format('d M Y'),
            ];
        });

        return response()->json([
            'data' => $exportData,
            'namaProdiFilter' => $namaProdiFilter,
        ]);
    }

    public function keterpakaianKoleksi(Request $request)
    {
        if ($request->query('export') === 'csv') {
            return $this->exportKeterpakaianCsv($request);
        }

        $filterType = $request->input('filter_type', 'monthly');
        $startMonth = $request->input('start_month', Carbon::now()->startOfYear()->format('Y-m'));
        $endMonth = $request->input('end_month', Carbon::now()->format('Y-m'));
        $startDate = $request->input('start_date', Carbon::now()->subDays(29)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $dataTabel = collect();
        $listKategori = [];
        $ccodeDescriptions = collect();
        $totalPenggunaan = 0;
        $kategoriPopuler = ['nama' => 'N/A', 'jumlah' => 0];
        $maxJumlah = 0;

        // PERUBAHAN: Kondisi 'if ($request->has('filter_type'))' dihapus dari sini
        // agar query selalu berjalan.

        try {
            $query = DB::connection('mysql2')->table('statistics')
                ->select(
                    DB::raw("CASE WHEN ccode LIKE 'R%' THEN 'Referensi' ELSE ccode END as kategori"),
                    DB::raw('COUNT(*) as jumlah')
                )
                ->whereIn('type', ['issue', 'return', 'localuse'])
                ->whereNotNull('ccode')
                ->where('ccode', '!=', '');

            if ($filterType == 'daily') {
                $query->addSelect(DB::raw('DATE(datetime) as periode'))
                    ->whereBetween('datetime', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
                    ->groupBy('periode', 'kategori');
            } else {
                $query->addSelect(DB::raw("LEFT(datetime, 7) as periode"))
                    ->whereRaw("LEFT(datetime, 7) BETWEEN ? AND ?", [$startMonth, $endMonth])
                    ->groupBy('periode', 'kategori');
            }

            $results = $query->orderBy('periode', 'asc')->get();

            $ccodeDescriptions = DB::connection('mysql2')->table('authorised_values')
                ->where('category', 'CCODE')
                ->pluck('lib', 'authorised_value');
            $ccodeDescriptions['Referensi'] = 'Gabungan semua koleksi referensi (R-...)';

            if (!$results->isEmpty()) {
                $listKategori = $results->pluck('kategori')->unique()->sort()->values()->all();
                $dataTabel = $results->groupBy('periode')->map(function ($items, $periode) use ($listKategori) {
                    $row = ['periode' => $periode];
                    foreach ($listKategori as $kategori) {
                        $row[$kategori] = $items->where('kategori', $kategori)->first()->jumlah ?? 0;
                    }
                    return $row;
                })->values();

                $totalPenggunaan = $dataTabel->sum(fn($row) => collect($row)->only($listKategori)->sum());
                $kategoriSums = $results->groupBy('kategori')->map(fn($items) => $items->sum('jumlah'));
                $kategoriPopuler['nama'] = $kategoriSums->sortDesc()->keys()->first();
                $kategoriPopuler['jumlah'] = $kategoriSums->sortDesc()->first();
                $maxJumlah = $dataTabel->max(fn($row) => collect($row)->only($listKategori)->max());
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }

        // Variabel $request sekarang tidak digunakan di sini, bisa dihapus jika mau
        return view('pages.peminjaman.keterpakaian', compact(
            'dataTabel',
            'listKategori',
            'ccodeDescriptions',
            'filterType',
            'startMonth',
            'endMonth',
            'startDate',
            'endDate',
            'totalPenggunaan',
            'kategoriPopuler',
            'maxJumlah'
        ));
    }

    public function getKeterpakaianDetail(Request $request)
    {
        $request->validate([
            'periode' => 'required|string',
            'kategori' => 'required|string',
            'filter_type' => 'required|in:daily,monthly',
        ]);

        $periode = $request->input('periode');
        $kategori = $request->input('kategori');
        $filterType = $request->input('filter_type');

        $query = DB::connection('mysql2')->table('statistics as s')
            ->select('bb.title as judul_buku', 's.datetime as waktu_transaksi', 's.type as tipe_transaksi')
            ->join('items as i', 's.itemnumber', '=', 'i.itemnumber')
            ->join('biblio as bb', 'i.biblionumber', '=', 'bb.biblionumber')
            ->whereIn('s.type', ['issue', 'return', 'localuse'])
            ->whereNotNull('s.ccode')->where('s.ccode', '!=', '');

        // ===============================================
        // ## PERBAIKAN LOGIKA FILTER TANGGAL DI SINI ##
        // ===============================================
        if ($filterType == 'daily') {
            $startOfDay = Carbon::parse($periode)->startOfDay();
            $endOfDay = Carbon::parse($periode)->endOfDay();
            $query->whereBetween('s.datetime', [$startOfDay, $endOfDay]);
        } else { // 'monthly'
            $startOfMonth = Carbon::parse($periode)->startOfMonth();
            $endOfMonth = Carbon::parse($periode)->endOfMonth();
            $query->whereBetween('s.datetime', [$startOfMonth, $endOfMonth]);
        }

        if ($kategori == 'Referensi') {
            $query->where('s.ccode', 'LIKE', 'R%');
        } else {
            $query->where('s.ccode', '=', $kategori);
        }

        $detailBuku = $query->orderBy('s.datetime', 'desc')
            ->paginate(10)
            ->withQueryString();

        return $detailBuku;
    }


    private function exportKeterpakaianCsv(Request $request)
    {
        // Logika query sama persis dengan di atas untuk memastikan data yang diekspor sesuai filter
        $filterType = $request->input('filter_type', 'monthly');
        $startMonth = $request->input('start_month');
        $endMonth = $request->input('end_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = DB::connection('mysql2')->table('statistics')
            ->select(
                DB::raw("CASE WHEN ccode LIKE 'R%' THEN 'Referensi' ELSE ccode END as kategori"),
                DB::raw('COUNT(*) as jumlah')
            )
            ->whereIn('type', ['issue', 'return', 'localuse']);

        if ($filterType == 'daily') {
            $query->addSelect(DB::raw('DATE(datetime) as periode'))
                ->whereBetween('datetime', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
                ->groupBy('periode', 'kategori');
        } else { // monthly
            $query->addSelect(DB::raw("LEFT(datetime, 7) as periode"))
                ->whereRaw("LEFT(datetime, 7) BETWEEN ? AND ?", [$startMonth, $endMonth])
                ->groupBy('periode', 'kategori');
        }

        $results = $query->orderBy('periode', 'asc')->get();

        // Proses data untuk CSV
        $listKategori = $results->pluck('kategori')->unique()->sort();
        $dataTabel = $results->groupBy('periode')->map(function ($items, $periode) use ($listKategori) {
            $row = ['periode' => $periode];
            foreach ($listKategori as $kategori) {
                $row[$kategori] = $items->where('kategori', $kategori)->first()->jumlah ?? 0;
            }
            return $row;
        })->values();

        // Logika untuk membuat file CSV dan men-downloadnya
        $fileName = 'keterpakaian_koleksi_';
        if ($filterType == 'daily') {
            $start = Carbon::parse($request->input('start_date'))->format('Ymd');
            $end = Carbon::parse($request->input('end_date'))->format('Ymd');
            $fileName .= "harian_{$start}-{$end}.csv";
        } else { // monthly
            $start = Carbon::parse($request->input('start_month'))->format('Y-m');
            $end = Carbon::parse($request->input('end_month'))->format('Y-m');
            $fileName .= "bulanan_{$start}-{$end}.csv";
        }

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function () use ($dataTabel, $listKategori, $filterType) {
            $file = fopen('php://output', 'w');

            // Tambahan: Untuk memastikan kompatibilitas encoding di Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            $headerRow = ['Periode'];
            foreach ($listKategori as $kategori) {
                $headerRow[] = $kategori;
            }
            fputcsv($file, $headerRow, ';');

            // Body
            foreach ($dataTabel as $row) {
                $dataRow = [];
                $periodeFormatted = ($filterType == 'daily')
                    ? Carbon::parse($row['periode'])->format('d M Y')
                    : Carbon::parse($row['periode'])->format('M Y');
                $dataRow[] = $periodeFormatted;

                foreach ($listKategori as $kategori) {
                    $dataRow[] = $row[$kategori] ?? 0;
                }
                fputcsv($file, $dataRow, ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
