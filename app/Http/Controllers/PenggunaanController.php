<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

class PenggunaanController extends Controller
{
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

        return view('pages.penggunaan.keterpakaian', compact(
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
            ->select('bb.title as judul_buku', 'i.barcode', 's.datetime as waktu_transaksi', 's.type as tipe_transaksi')
            ->join('items as i', 's.itemnumber', '=', 'i.itemnumber')
            ->join('biblio as bb', 'i.biblionumber', '=', 'bb.biblionumber')
            ->whereIn('s.type', ['issue', 'return', 'localuse'])
            ->whereNotNull('s.ccode')->where('s.ccode', '!=', '');


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
            ->whereIn('type', ['issue', 'return', 'localuse'])
            ->whereNotNull('ccode')
            ->where('ccode', '!=', '');
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

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            $headerRow = ['Periode'];
            foreach ($listKategori as $kategori) {
                $headerRow[] = $kategori;
            }
            $headerRow[] = 'Total';
            fputcsv($file, $headerRow, ';');

            // Body
            foreach ($dataTabel as $row) {
                $dataRow = [];
                $periodeFormatted = ($filterType == 'daily')
                    ? Carbon::parse($row['periode'])->format('d M Y')
                    : Carbon::parse($row['periode'])->format('M Y');
                $dataRow[] = $periodeFormatted;

                $totalPerRow = collect($row)->except('periode')->sum();

                foreach ($listKategori as $kategori) {
                    $dataRow[] = $row[$kategori] ?? 0;
                }
                $dataRow[] = $totalPerRow;
                fputcsv($file, $dataRow, ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function cekBuku(Request $request)
    {
        // Trim barcode di sisi aplikasi, BUKAN di query. Ini sangat penting.
        $barcode = trim($request->input('barcode'));
        $typeFilter = $request->input('type_filter', 'all');

        // Inisialisasi variabel
        $history = collect();
        $book = null;
        $errorMessage = null;
        $usageStats = (object)[
            'issue' => 0,
            'return' => 0,
            'localuse' => 0,
            'total' => 0,
        ];

        if ($barcode) {
            try {
                // Query 1: Ambil info buku & semua total penggunaan sekaligus.
                $bookAndStats = DB::connection('mysql2')->table('items as i')
                    ->select(
                        'b.title AS Judul',
                        'b.author AS Pengarang',
                        DB::raw("SUM(CASE WHEN s.type = 'issue' THEN 1 ELSE 0 END) as issue_count"),
                        DB::raw("SUM(CASE WHEN s.type = 'return' THEN 1 ELSE 0 END) as return_count"),
                        DB::raw("SUM(CASE WHEN s.type = 'localuse' THEN 1 ELSE 0 END) as localuse_count")
                    )
                    ->leftJoin('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                    ->leftJoin('statistics as s', 'i.itemnumber', '=', 's.itemnumber')
                    // Perubahan di sini: WHERE langsung ke kolom, tanpa TRIM().
                    ->where('i.barcode', $barcode)
                    ->groupBy('b.title', 'b.author')
                    ->first();

                if ($bookAndStats && $bookAndStats->Judul) {
                    $book = $bookAndStats;
                    $usageStats->issue = (int) $bookAndStats->issue_count;
                    $usageStats->return = (int) $bookAndStats->return_count;
                    $usageStats->localuse = (int) $bookAndStats->localuse_count;
                    $usageStats->total = $usageStats->issue + $usageStats->return + $usageStats->localuse;

                    // Query 2: Ambil histori transaksi (dengan pagination).
                    $historyQuery = DB::connection('mysql2')->table('statistics as s')
                        ->select('s.datetime', 's.type')
                        ->join('items as i', 's.itemnumber', '=', 'i.itemnumber')
                        // Perubahan di sini juga.
                        ->where('i.barcode', $barcode)
                        ->orderBy('s.datetime', 'desc');

                    if ($typeFilter !== 'all') {
                        $historyQuery->where('s.type', 'like', $typeFilter);
                    } else {
                        $historyQuery->whereIn('s.type', ['issue', 'return', 'localuse']);
                    }
                    $history = $historyQuery->paginate(10)->appends($request->query());
                }
            } catch (\Exception $e) {
                // \Log::error('Error saat query histori buku: ' . $e->getMessage());
                $errorMessage = 'Terjadi kesalahan pada server saat mengambil data.';
            }
        }

        return view('pages.penggunaan.cekBuku', [
            'barcode' => $barcode,
            'history' => $history,
            'book' => $book,
            'usageStats' => $usageStats,
            'errorMessage' => $errorMessage,
            'typeFilter' => $typeFilter,
        ]);
    }

    public function seringDibaca(Request $request)
    {
        // Ambil input filter
        $tahun = $request->input('tahun', date('Y'));
        $bulan = $request->input('bulan');

        $perPage = 10;

        // Inisialisasi Paginator kosong untuk Fiksi
        $dataFiksi = new LengthAwarePaginator(
            [],
            0,
            $perPage,
            Paginator::resolveCurrentPage('fiksi_page'),
            ['path' => $request->url(), 'pageName' => 'fiksi_page']
        );

        // Inisialisasi Paginator kosong untuk Non-Fiksi
        $dataNonFiksi = new LengthAwarePaginator(
            [],
            0,
            $perPage,
            Paginator::resolveCurrentPage('nonfiksi_page'),
            ['path' => $request->url(), 'pageName' => 'nonfiksi_page']
        );

        if ($request->has('tahun')) {
            try {
                if ($bulan) {
                    $start_date = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
                    $end_date = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
                } else {
                    $start_date = Carbon::createFromDate($tahun, 1, 1)->startOfYear();
                    $end_date = Carbon::createFromDate($tahun, 12, 31)->endOfYear();
                }

                $baseQuery = DB::connection('mysql2')->table('statistics as s')
                    ->select(
                        DB::raw("MAX(CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]'))) AS judul_buku"),
                        DB::raw("MAX(b.author) as pengarang"),
                        DB::raw('COUNT(s.itemnumber) as jumlah_penggunaan')
                    )
                    ->join('items as i', 's.itemnumber', '=', 'i.itemnumber')
                    ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                    ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                    ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                    ->whereIn('s.type', ['issue', 'return', 'localuse'])
                    ->whereBetween('s.datetime', [$start_date, $end_date]);

                if ($request->query('export') === 'fiksi') {
                    return $this->exportSeringDibacaCsv(clone $baseQuery, 'fiksi', $tahun, $bulan);
                }
                if ($request->query('export') === 'nonfiksi') {
                    return $this->exportSeringDibacaCsv(clone $baseQuery, 'nonfiksi', $tahun, $bulan);
                }

                // --- Query 1: Fiksi (Paginasi) ---
                $queryFiksi = (clone $baseQuery)
                    ->where(function ($q) {
                        $q->where('bi.cn_class', 'LIKE', '812%')
                            ->orWhere('bi.cn_class', 'LIKE', '813%')
                            ->orWhere('bi.cn_class', 'LIKE', '899%');
                    })
                    ->groupBy('i.biblionumber')
                    ->orderBy('jumlah_penggunaan', 'desc')
                    ->paginate($perPage, ['*'], 'fiksi_page')
                    ->onEachSide(1);

                // --- Query 2: Non-Fiksi (Paginasi) ---
                $queryNonFiksi = (clone $baseQuery)
                    ->where(function ($q) {
                        $q->where('bi.cn_class', 'NOT LIKE', '812%')
                            ->where('bi.cn_class', 'NOT LIKE', '813%')
                            ->where('bi.cn_class', 'NOT LIKE', '899%');
                    })
                    ->groupBy('i.biblionumber')
                    ->orderBy('jumlah_penggunaan', 'desc')
                    ->paginate($perPage, ['*'], 'nonfiksi_page')
                    ->onEachSide(1); // Gunakan paginate di sini

                $dataFiksi = $queryFiksi->appends($request->except('nonfiksi_page'));
                $dataNonFiksi = $queryNonFiksi->appends($request->except('fiksi_page'));
            } catch (\Exception $e) {
                Log::error('Error fetching seringDibaca: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                return redirect()->back()->with('error', 'Terjadi kesalahan saat mengambil data: ' . $e->getMessage());
            }
        }

        // Kirim data ke view
        return view('pages.penggunaan.sering_dibaca', compact(
            'dataFiksi',
            'dataNonFiksi',
            'tahun',
            'bulan'
        ));
    }

    private function exportSeringDibacaCsv($baseQuery, string $kategori, $tahun, $bulan)
    {
        $query = $baseQuery;
        $kategoriLabel = "";
        $kategoriTitle = "";

        if ($kategori === 'fiksi') {
            $query->where(function ($q) {
                $q->where('bi.cn_class', 'LIKE', '812%')
                    ->orWhere('bi.cn_class', 'LIKE', '813%')
                    ->orWhere('bi.cn_class', 'LIKE', '899%');
            });
            $kategoriLabel = "fiksi";
            $kategoriTitle = "Fiksi";
        } else { // Asumsi 'nonfiksi'
            $query->where(function ($q) {
                $q->where('bi.cn_class', 'NOT LIKE', '812%')
                    ->where('bi.cn_class', 'NOT LIKE', '813%')
                    ->where('bi.cn_class', 'NOT LIKE', '899%');
            });
            $kategoriLabel = "nonfiksi";
            $kategoriTitle = "Non-Fiksi";
        }

        $data = $query->groupBy('i.biblionumber')
            ->orderBy('jumlah_penggunaan', 'desc')
            ->get();

        $bulanTitle = $bulan ? \Carbon\Carbon::create()->month($bulan)->format('F') : "Satu Tahun Penuh";
        $laporanTitle = "Laporan Buku Sering Dibaca - Kategori: $kategoriTitle";
        $periodeTitle = "Periode: $bulanTitle $tahun";

        $fileName = "export_sering_dibaca_${kategoriLabel}_${tahun}";
        if ($bulan) {
            $fileName .= "_" . str_pad($bulan, 2, '0', STR_PAD_LEFT);
        }
        $fileName .= ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($data, $laporanTitle, $periodeTitle) {
            $delimiter = ';';
            $file = fopen('php://output', 'w');
            fputcsv($file, [$laporanTitle], $delimiter);
            fputcsv($file, [$periodeTitle], $delimiter);
            fputcsv($file, [], $delimiter);
            fputcsv($file, ['No', 'Judul Buku', 'Pengarang', 'Jumlah Penggunaan'], $delimiter);
            foreach ($data as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row->judul_buku,
                    $row->pengarang,
                    $row->jumlah_penggunaan
                ], $delimiter);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
