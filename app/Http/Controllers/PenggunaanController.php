<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

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
        if ($request->query('export') === 'csv') {
            return $this->exportSeringDibacaCsv($request);
        }

        // Ambil input filter untuk dikirim kembali ke view (agar pilihan tetap ada)
        $tahun = $request->input('tahun', date('Y'));
        $bulan = $request->input('bulan');

        // Inisialisasi koleksi kosong untuk menampung data
        $dataBuku = collect();

        // Hanya jalankan query jika ada input 'tahun' dari form (artinya tombol 'Terapkan' sudah diklik)
        if ($request->has('tahun')) {
            // Buat rentang tanggal berdasarkan filter
            if ($bulan) {
                $start_date = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
                $end_date = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
            } else {
                $start_date = Carbon::createFromDate($tahun, 1, 1)->startOfYear();
                $end_date = Carbon::createFromDate($tahun, 12, 31)->endOfYear();
            }

            $query = DB::connection('mysql2')->table('statistics as s')
                ->select(
                    'b.title as judul_buku',
                    'b.author as pengarang',
                    DB::raw('COUNT(s.itemnumber) as jumlah_penggunaan')
                )
                ->join('items as i', 's.itemnumber', '=', 'i.itemnumber')
                ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                ->whereIn('s.type', ['issue', 'return', 'localuse'])
                ->whereBetween('s.datetime', [$start_date, $end_date]);

            // Jalankan query dan pagination, lalu masukkan hasilnya ke variabel
            $dataBuku = $query->groupBy('b.biblionumber', 'b.title', 'b.author')
                ->orderBy('jumlah_penggunaan', 'desc')
                ->paginate(20);
        }

        // Kirim data ke view
        return view('pages.penggunaan.sering_dibaca', compact('dataBuku', 'tahun', 'bulan'));
    }

    private function exportSeringDibacaCsv(Request $request)
    {
        // Logika query sama persis dengan fungsi utama, hanya tanpa pagination
        $tahun = $request->input('tahun', date('Y'));
        $bulan = $request->input('bulan');

        if ($bulan) {
            $start_date = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
            $end_date = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
            $periode = $start_date->format('F Y');
        } else {
            $start_date = Carbon::createFromDate($tahun, 1, 1)->startOfYear();
            $end_date = Carbon::createFromDate($tahun, 12, 31)->endOfYear();
            $periode = $start_date->format('Y');
        }

        $query = DB::connection('mysql2')->table('statistics as s')
            ->select('b.title as judul_buku', 'b.author as pengarang', DB::raw('COUNT(b.biblionumber) as jumlah_penggunaan'))
            ->join('items as i', 's.itemnumber', '=', 'i.itemnumber')
            ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
            ->whereIn('s.type', ['issue', 'return', 'localuse'])
            ->whereBetween('s.datetime', [$start_date, $end_date]);

        // Ambil semua data (tanpa limit/paginate) untuk di-export
        $dataExport = $query->groupBy('b.biblionumber', 'b.title')
            ->orderBy('jumlah_penggunaan', 'desc')
            ->get();

        // Logika untuk membuat & men-download file CSV
        $fileName = "buku_sering_dibaca_{$periode}.csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($dataExport) {
            $file = fopen('php://output', 'w');
            // Header
            fputcsv($file, ['No', 'Judul Buku', 'Pengarang', 'Jumlah Penggunaan'], ';');
            // Body
            foreach ($dataExport as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row->judul_buku,
                    $row->pengarang,
                    $row->jumlah_penggunaan
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
