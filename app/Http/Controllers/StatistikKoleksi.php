<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\M_Auv;
use App\Models\M_eprodi;
use App\Models\M_items;
use App\Helpers\CnClassHelper;
use App\Helpers\CnClassHelperr;
use App\Helpers\QueryHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\CollectionStatisticsService;
use App\Services\ProdiService;

class StatistikKoleksi extends Controller
{
    public function __construct(
        private CollectionStatisticsService $collectionService,
        private ProdiService $prodiService
    ) {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');
    }

    public function rekapPerFakultas(Request $request)
    {
        $request->validate([
            'fakultas' => 'string|nullable',
            'tahun' => 'numeric|nullable'
        ]);

        $listprodi = M_Auv::getCachedProdiList();
        $prodiToFacultyMap = $this->prodiService->getProdiToFacultyMap($listprodi);
        $prodiCodeToNameMap = collect($this->prodiService->getFullProdiList());
        $faculties = array_unique(array_values($prodiToFacultyMap));
        $faculties = array_filter($faculties, fn($f) => !in_array($f, ['Dosen', 'Tendik', 'Lainnya']));
        sort($faculties);
        $selectedFaculty = $request->input('fakultas');

        $tahunTerakhir = $request->input('tahun', 'all');
        $rekapData = collect();

        if ($selectedFaculty) {
            $cacheKey = "rekap_fakultas_v3:" . ($selectedFaculty ?: 'all') . ":" . $tahunTerakhir;
            
            $rekapData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodiToFacultyMap, $selectedFaculty, $prodiCodeToNameMap, $tahunTerakhir) {
                $targetProdiCodes = array_keys($prodiToFacultyMap, $selectedFaculty);
                
                $combinedRules = [];
                foreach ($targetProdiCodes as $prodiCode) {
                    $rules = CnClassHelperr::getCnClassByProdi($prodiCode);
                    if ($rules) {
                        $combinedRules = array_merge($combinedRules, $rules);
                    }
                }
                $combinedRules = array_unique($combinedRules, SORT_REGULAR);
                
                $countsPerProdi = [];
                foreach ($targetProdiCodes as $prodiCode) {
                    $countsPerProdi[$prodiCode] = [
                        'Jurnal'    => ['judul' => [], 'eksemplar' => 0],
                        'E-Jurnal'  => ['judul' => [], 'eksemplar' => 0],
                        'Textbook'  => ['judul' => [], 'eksemplar' => 0],
                        'E-Book'    => ['judul' => [], 'eksemplar' => 0],
                        'Prosiding' => ['judul' => [], 'eksemplar' => 0],
                        'Referensi' => ['judul' => [], 'eksemplar' => 0],
                    ];
                }

                $processCategory = function($category, $itypes, $isSerial = false, $isCcodeR = false, $isBarcodeJE = false, $isNotBarcodeJE = false) use (&$countsPerProdi, $targetProdiCodes, $tahunTerakhir, $combinedRules) {
                    $q = M_items::query()
                        ->from('items')
                        ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                        ->join('biblio as b', 'items.biblionumber', '=', 'b.biblionumber')
                        ->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'items.biblionumber')
                        ->select('items.biblionumber', 'items.enumchron', 'bi.cn_class', 'items.itemcallnumber')
                        ->where('items.itemlost', 0)
                        ->where('items.withdrawn', 0);

                    if (is_array($itypes)) $q->whereIn('items.itype', $itypes);
                    else $q->where('items.itype', $itypes);

                    if ($isCcodeR) $q->whereRaw('LEFT(items.ccode, 1) = ?', ['R']);
                    else if ($itypes == ['BKS', 'BKSA', 'BKSCA', 'BKSC']) $q->whereRaw('LEFT(items.ccode, 1) <> "R"');

                    if (in_array('JR', (array)$itypes)) $q->whereRaw("TRIM(items.enumchron) REGEXP '[0-9]{4}$'");

                    if ($isBarcodeJE) $q->where('items.barcode', 'like', 'JE%');
                    if ($isNotBarcodeJE) $q->where('items.barcode', 'not like', 'JE%');

                    if ($tahunTerakhir !== 'all') {
                        if (in_array('JR', (array)$itypes)) {
                            $q->whereRaw('RIGHT(items.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
                        } else {
                            $q->whereRaw('CAST(ExtractValue(bm.metadata, \'//datafield[@tag="260"]/subfield[@code="c"]\') AS UNSIGNED) >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                        }
                    }

                    if (!empty($combinedRules)) {
                        QueryHelper::applyCnClassRules($q, $combinedRules);
                    }

                    foreach ($q->cursor() as $row) {
                        $actualClass = !empty($row->cn_class) ? $row->cn_class : $row->itemcallnumber;
                        if (empty($actualClass)) continue;

                        $key = $isSerial ? $row->biblionumber . '_' . $row->enumchron : $row->biblionumber;

                        foreach ($targetProdiCodes as $prodiCode) {
                            if (CnClassHelperr::isValidCnClass($prodiCode, $actualClass)) {
                                $countsPerProdi[$prodiCode][$category]['judul'][$key] = true;
                                $countsPerProdi[$prodiCode][$category]['eksemplar']++;
                            }
                        }
                    }
                };

                $processCategory('Jurnal', ['JR', 'JRA', 'JRT', 'EJ'], true, false, false, true);
                $processCategory('E-Jurnal', ['JR', 'JRA', 'JRT', 'EJ'], true, false, true, false);
                $processCategory('Textbook', ['BKS', 'BKSA', 'BKSCA', 'BKSC'], false, false, false, false);
                $processCategory('E-Book', 'EB', false, false, false, false);
                $processCategory('Prosiding', ['PR', 'EPR'], true, false, false, false);
                $processCategory('Referensi', ['BKS', 'BKSA', 'BKSCA', 'BKSC'], false, true, false, false);

                $rekapDataPerProdi = [];
                foreach ($targetProdiCodes as $prodiCode) {
                    $namaProdi = $prodiCodeToNameMap->get($prodiCode, 'Prodi Tidak Dikenal');
                    $finalCounts = [];
                    
                    foreach ($countsPerProdi[$prodiCode] as $cat => $data) {
                        $jCount = count($data['judul']);
                        $eCount = $data['eksemplar'];
                        if ($jCount > 0 || $eCount > 0) {
                            $finalCounts[$cat] = ['judul' => $jCount, 'eksemplar' => $eCount];
                        }
                    }

                    if (!empty($finalCounts)) {
                        $order = ['Textbook' => 1, 'E-Book' => 2, 'Jurnal' => 3, 'E-Jurnal' => 4, 'Prosiding' => 5, 'Referensi' => 6];
                        uksort($finalCounts, fn($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));

                        $rekapDataPerProdi[] = ['prodi_code' => $prodiCode, 'nama_prodi' => $namaProdi, 'counts' => $finalCounts];
                    }
                }
                return collect($rekapDataPerProdi)->sortBy('nama_prodi')->values();
            });
        }

        return view('pages.dapus.rekap_fakultas', compact('faculties', 'selectedFaculty', 'rekapData'));
    }

    private function handleCollectionRequest(Request $request, string $type, string $viewName, string $title)
    {
        $listprodi = M_Auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')->onlyProdiTampil()->orderBy('authorised_value', 'asc')->get();
        
        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all';
        $prodiOptionAll->lib = 'Semua Program Studi';
        $listprodi->prepend($prodiOptionAll);

        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            $result = $this->collectionService->getCollectionData($type, $prodi, $tahunTerakhir);
            $processedData = $result['processedData'];
            $totalJudul = $result['totalJudul'];
            $totalEksemplar = $result['totalEksemplar'];

            if ($request->has('export_csv')) {
                $headers = $this->getCsvHeaders($type);
                return $this->streamCsvExport($processedData, $namaProdi, $tahunTerakhir, $type, $title, $headers, function($row, &$i, &$previousJudul) use ($type) {
                    return $this->mapCsvRow($row, $i, $type, $previousJudul);
                });
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view($viewName, compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    public function prosiding(Request $request) { return $this->handleCollectionRequest($request, 'prosiding', 'pages.dapus.prosiding', 'Prosiding'); }
    public function jurnal(Request $request) { return $this->handleCollectionRequest($request, 'jurnal', 'pages.dapus.jurnal', 'Jurnal'); }
    public function ejurnal(Request $request) { return $this->handleCollectionRequest($request, 'ejurnal', 'pages.dapus.ejurnal', 'E-Jurnal'); }
    public function ebook(Request $request) { return $this->handleCollectionRequest($request, 'ebook', 'pages.dapus.ebook', 'E-Book'); }
    public function textbook(Request $request) { return $this->handleCollectionRequest($request, 'textbook', 'pages.dapus.textbook', 'Textbook'); }
    public function periodikal(Request $request) { return $this->handleCollectionRequest($request, 'periodikal', 'pages.dapus.periodikal', 'Periodikal'); }
    public function referensi(Request $request) { return $this->handleCollectionRequest($request, 'referensi', 'pages.dapus.referensi', 'Referensi'); }

    public function trenPertambahan(Request $request)
    {
        $startYear = (int) $request->input('start_year', date('Y') - 10);
        $endYear   = (int) $request->input('end_year', date('Y'));

        // Swap otomatis jika start_year lebih besar dari end_year
        $yearSwapped = false;
        if ($startYear > $endYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
            $yearSwapped = true;
        }

        // Fix 5: Ambil tahun tertua dari DB untuk filter dropdown
        $minYear = (int) \Illuminate\Support\Facades\Cache::remember('tren_pertambahan_min_year', 86400, function () {
            return DB::connection('mysql2')
                ->table('items')
                ->whereNotNull('dateaccessioned')
                ->selectRaw('MIN(YEAR(dateaccessioned)) as min_year')
                ->value('min_year') ?? 2000;
        });

        $cacheKey = "statistik_koleksi_tren_pertambahan_{$startYear}_{$endYear}";
        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($startYear, $endYear) {
            return DB::connection('mysql2')
                ->table('items')
                ->selectRaw('YEAR(dateaccessioned) as year, COUNT(itemnumber) as total_items, COUNT(DISTINCT biblionumber) as total_titles')
                ->whereNotNull('dateaccessioned')
                ->whereRaw('YEAR(dateaccessioned) >= ?', [$startYear])
                ->whereRaw('YEAR(dateaccessioned) <= ?', [$endYear])
                ->groupByRaw('YEAR(dateaccessioned)')
                ->orderByRaw('YEAR(dateaccessioned) ASC')
                ->get();
        });

        if ($request->has('export_csv')) {
            $filename = "Tren_Pertambahan_Koleksi_{$startYear}_{$endYear}.csv";
            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $callback = function() use($data, $startYear, $endYear) {
                $file = fopen('php://output', 'w');
                
                fputcsv($file, ["Data Pengadaan Koleksi ($startYear - $endYear)"]);
                fputcsv($file, []);
                
                fputcsv($file, ['No', 'Tahun Masuk (Accessioned)', 'Judul Baru', 'Eksemplar Baru']);
                
                $i = 1;
                foreach ($data as $row) {
                    fputcsv($file, [
                        $i++,
                        $row->year,
                        $row->total_titles,
                        $row->total_items
                    ]);
                }
                
                fputcsv($file, [
                    '', 'TOTAL', $data->sum('total_titles'), $data->sum('total_items')
                ]);
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return view('pages.dapus.trenPertambahan', compact('data', 'startYear', 'endYear', 'yearSwapped', 'minYear'));
    }

    public function exportPdfTrenPertambahan(Request $request)
    {
        $startYear = (int) $request->input('start_year', date('Y') - 10);
        $endYear   = (int) $request->input('end_year', date('Y'));
        if ($startYear > $endYear) [$startYear, $endYear] = [$endYear, $startYear];

        $cacheKey = "statistik_koleksi_tren_pertambahan_{$startYear}_{$endYear}";
        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($startYear, $endYear) {
            return DB::connection('mysql2')
                ->table('items')
                ->selectRaw('YEAR(dateaccessioned) as year, COUNT(itemnumber) as total_items, COUNT(DISTINCT biblionumber) as total_titles')
                ->whereNotNull('dateaccessioned')
                ->whereRaw('YEAR(dateaccessioned) >= ?', [$startYear])
                ->whereRaw('YEAR(dateaccessioned) <= ?', [$endYear])
                ->groupByRaw('YEAR(dateaccessioned)')
                ->orderByRaw('YEAR(dateaccessioned) ASC')
                ->get();
        });

        $logoPath = public_path('img/ums.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.dapus.pdf.tren_pertambahan', compact(
            'data', 'startYear', 'endYear', 'logoBase64'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("Tren_Pertambahan_Koleksi_{$startYear}_{$endYear}.pdf");
    }

    public function detailPertambahan(Request $request)
    {
        $year   = (int)$request->input('year', date('Y'));
        $search = trim($request->input('search', ''));
        $itype  = trim($request->input('itype', ''));
        $export = $request->has('export_csv');

        $query = DB::connection('mysql2')
            ->table('items')
            ->join('biblio as b', 'b.biblionumber', '=', 'items.biblionumber')
            ->leftJoin('biblioitems as bi', 'bi.biblionumber', '=', 'items.biblionumber')
            ->select(
                'b.biblionumber',
                // Judul lengkap: 245$a dari biblio + 245$b via scalar subquery (tidak butuh privilege ANY_VALUE)
                DB::raw("CONCAT_WS(' ', TRIM(b.title), NULLIF(TRIM(
                    (SELECT EXTRACTVALUE(bm2.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')
                     FROM biblio_metadata bm2
                     WHERE bm2.biblionumber = b.biblionumber
                     LIMIT 1)
                ), '')) AS title"),
                'b.author',
                'bi.publishercode',
                'bi.publicationyear',
                // MIN agar tidak masuk GROUP BY — tiap biblionumber hanya 1 baris
                DB::raw('MIN(items.itemcallnumber) as itemcallnumber'),
                DB::raw('COUNT(items.itemnumber) as total_eksemplar'),
                DB::raw('MIN(DATE(items.dateaccessioned)) as tgl_masuk')
            )
            ->whereNotNull('items.dateaccessioned')
            ->whereRaw('YEAR(items.dateaccessioned) = ?', [$year])
            ->where('items.itemlost', 0)
            ->where('items.withdrawn', 0);

        // Filter jenis koleksi — mapping nama group ke itype DB sesuai sistem
        if ($itype !== '') {
            $itypeGroups = [
                'textbook'  => ['itypes' => ['BKS', 'BKSA', 'BKSCA', 'BKSC'], 'ccode_not_r' => true,  'ccode_r' => false],
                'jurnal'    => ['itypes' => ['JR', 'JRA', 'JRT', 'EJ'],         'ccode_not_r' => false, 'ccode_r' => false],
                'ebook'     => ['itypes' => ['EB'],                              'ccode_not_r' => false, 'ccode_r' => false],
                'prosiding' => ['itypes' => ['PR', 'EPR'],                       'ccode_not_r' => false, 'ccode_r' => false],
                'referensi' => ['itypes' => ['BKS', 'BKSA', 'BKSCA', 'BKSC'],  'ccode_not_r' => false, 'ccode_r' => true],
            ];

            if (isset($itypeGroups[$itype])) {
                $group = $itypeGroups[$itype];
                $query->whereIn('items.itype', $group['itypes']);
                if ($group['ccode_r'])     $query->whereRaw('LEFT(items.ccode, 1) = ?', ['R']);
                if ($group['ccode_not_r']) $query->whereRaw('LEFT(items.ccode, 1) <> "R"');
            }
        }

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('b.title', 'LIKE', "%{$search}%")
                  ->orWhere('b.author', 'LIKE', "%{$search}%")
                  ->orWhere('bi.publishercode', 'LIKE', "%{$search}%")
                  ->orWhere('items.itemcallnumber', 'LIKE', "%{$search}%");
            });
        }

        $query->groupBy(
            'b.biblionumber',
            'b.title',   
            'b.author',
            'bi.publishercode',
            'bi.publicationyear'
        )
        ->orderByRaw('CASE WHEN b.author IS NOT NULL AND TRIM(b.author) != "" THEN 0 ELSE 1 END ASC')
        ->orderBy('total_eksemplar', 'DESC')
        ->orderBy('b.title', 'ASC');

        // Cache hanya saat tidak ada filter search atau itype (filter dinamis = no cache)
        $useCache = ($search === '' && $itype === '');
        $cacheKeyDetail = "detail_pertambahan_{$year}";

        if ($export) {
            $data = $useCache
                ? \Illuminate\Support\Facades\Cache::remember($cacheKeyDetail, 3600, fn() => $query->get())
                : $query->get();
            $filename = "Detail_Pertambahan_Buku_Tahun_{$year}.csv";
            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $callback = function() use($data, $year) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ["Detail Pengadaan Koleksi - Tahun Pengadaan: $year"]);
                fputcsv($file, []);
                fputcsv($file, ['No', 'Judul Buku', 'Pengarang', 'Penerbit', 'Tahun Terbit', 'Call Number', 'Jumlah Eksemplar', 'Tanggal Masuk']);
                $i = 1;
                foreach ($data as $row) {
                    fputcsv($file, [
                        $i++,
                        $row->title,
                        $row->author,
                        $row->publishercode,
                        $row->publicationyear,
                        $row->itemcallnumber,
                        $row->total_eksemplar,
                        $row->tgl_masuk
                    ]);
                }
                fputcsv($file, ['', '', '', '', '', 'TOTAL EKSEMPLAR', $data->sum('total_eksemplar'), '']);
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // Cache full result saat search & itype kosong, lalu paginate dari Collection di memori
        if ($useCache) {
            $allData = \Illuminate\Support\Facades\Cache::remember($cacheKeyDetail, 3600, fn() => $query->get());
            $details = (new \Illuminate\Pagination\LengthAwarePaginator(
                $allData->forPage(request()->input('page', 1), 25),
                $allData->count(),
                25,
                request()->input('page', 1),
                ['path' => request()->url(), 'query' => request()->query()]
            ));
            $totalEksemplarAll = (int) $allData->sum('total_eksemplar');
        } else {
            $details = $query->paginate(25)->appends($request->all());
            // Wrap query sebagai subquery untuk sum aggregate alias
            $totalEksemplarAll = (int) DB::connection('mysql2')
                ->table(DB::raw('(' . (clone $query)->toSql() . ') as sub'))
                ->mergeBindings(clone $query)
                ->sum('total_eksemplar');
        }

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'year' => $year,
                'html' => view('pages.dapus.partials.detail_pertambahan_table', compact('details', 'year', 'search'))->render(),
                'total_titles' => $details->total(),
                'total_items' => $totalEksemplarAll
            ]);
        }

        return view('pages.dapus.detailPertambahan', compact('details', 'year', 'search', 'itype'));
    }

    private function getCsvHeaders(string $type): array
    {
        if (in_array($type, ['prosiding', 'jurnal', 'ejurnal'])) return ['No', 'Judul', 'Pengarang', 'Penerbit', 'Tahun Terbit', 'Nomor', 'Issue', 'Eksemplar', 'Lokasi', 'Link'];
        if ($type === 'ebook') return ['No', 'Judul', 'Pengarang', 'Penerbit', 'Tahun Terbit', 'Eksemplar', 'Lokasi', 'Link'];
        if ($type === 'periodikal') return ['No', 'Jenis', 'Koleksi', 'Judul', 'Pengarang', 'Penerbit', 'Tahun Terbit', 'Nomor', 'Issue', 'Eksemplar', 'Lokasi'];
        if ($type === 'referensi') return ['No', 'Jenis', 'Koleksi', 'Judul', 'Pengarang', 'Penerbit', 'Tahun Terbit', 'Eksemplar', 'Lokasi'];
        return ['No', 'Judul', 'Pengarang', 'Penerbit', 'Tahun Terbit', 'Eksemplar', 'Lokasi'];
    }

    private function mapCsvRow($row, &$i, string $type, &$previousJudul = null): array
    {
        if (in_array($type, ['textbook', 'ebook'])) {
            if ($previousJudul === $row->Judul) return [];
            $previousJudul = $row->Judul;
        }

        $base = [
            $i++,
            $row->Judul,
            $row->Pengarang,
            $row->Penerbit,
            (int) $row->TahunTerbit
        ];

        if ($type === 'prosiding') {
            return array_merge($base, [$row->Nomor, (int) $row->Issue, (int) $row->Eksemplar, $row->Lokasi, $row->Link_Prosiding ?? '']);
        }
        if (in_array($type, ['jurnal', 'ejurnal'])) {
            return array_merge($base, [$row->Nomor, (int) $row->Issue, (int) $row->Eksemplar, $row->Lokasi, '']);
        }
        if ($type === 'ebook') {
            return array_merge($base, [(int) $row->Eksemplar, $row->Lokasi, $row->Link_Ebook ?? '']);
        }
        if ($type === 'periodikal') {
            return [
                $i - 1, $row->Jenis_Koleksi, $row->Koleksi, $row->Judul, $row->Pengarang, $row->Penerbit,
                (int) $row->TahunTerbit, $row->Nomor, (int) $row->Issue, (int) $row->Eksemplar, $row->Lokasi
            ];
        }
        if ($type === 'referensi') {
            return [
                $i - 1, $row->Jenis_Koleksi, $row->Koleksi, $row->Judul, $row->Pengarang, $row->Penerbit,
                (int) $row->TahunTerbit, (int) $row->Eksemplar, $row->Lokasi
            ];
        }
        
        return array_merge($base, [(int) $row->Eksemplar, $row->Lokasi]);
    }

    public function koleksiPerprodi(Request $request)
    {
        $listprodi = M_eprodi::all();
        
        $prodi = $request->input('prodi');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = 'Pilih Program Studi';
        $chartData = [];

        if ($prodi) {
            $cacheKey = "koleksi_prodi:{$prodi}:{$tahunTerakhir}";
            $cachedResult = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodi, $tahunTerakhir, $listprodi) {
                $prodiMapping = $listprodi->pluck('nama', 'kode')->toArray();
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

                $query = M_items::select('t.description AS Jenis', 'i.ccode AS Koleksi')
                    ->selectRaw('COUNT(DISTINCT i.biblionumber) AS Judul')
                    ->selectRaw('COUNT(i.itemnumber) AS Eksemplar')
                    ->from('items as i')
                    ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                    ->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'i.biblionumber')
                    ->join('itemtypes as t', 'i.itype', '=', 't.itemtype')
                    ->where('i.itemlost', 0)
                    ->where('i.withdrawn', 0)
                    ->where(function($q) use ($cnClasses) {
                        QueryHelper::applyCnClassRules($q, $cnClasses);
                    });

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('CAST(ExtractValue(bm.metadata, \'//datafield[@tag="260"]/subfield[@code="c"]\') AS UNSIGNED) >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
                }

                $data = $query->groupBy('Jenis', 'Koleksi')->orderBy('Jenis', 'asc')->orderBy('Koleksi', 'asc')->get();
                $chartData = $data->map(fn ($item) => ['jenis' => $item->Jenis, 'judul' => $item->Judul, 'eksemplar' => $item->Eksemplar])->values()->all();

                return compact('namaProdi', 'data', 'chartData');
            });

            $namaProdi = $cachedResult['namaProdi'];
            $data = $cachedResult['data'];
            $chartData = $cachedResult['chartData'];
        }

        return view('pages.dapus.prodi', compact('namaProdi', 'listprodi', 'data', 'prodi', 'tahunTerakhir', 'chartData'));
    }

    public function getDetailKoleksi(Request $request)
    {
        $prodi = $request->input('prodi');
        $jenis = $request->input('jenis');
        $tahunTerakhir = $request->input('tahun', 'all');
        $page = $request->input('page', 1);
        $perPage = 10;

        $detailData = collect();

        if ($prodi && $jenis) {
            $cacheKey = "detail_koleksi:{$prodi}:{$jenis}:{$tahunTerakhir}:{$page}";
            $detailData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodi, $jenis, $tahunTerakhir, $page, $perPage) {
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);

                $query = M_items::select(
                    'b.title AS Judul',
                    DB::raw('MIN(bi.cn_class) AS Kelas'),
                    DB::raw("MIN(CASE WHEN ExtractValue(bm.metadata, '//datafield[@tag=\"260\"]/subfield[@code=\"c\"]') REGEXP '[0-9]{4}' THEN ExtractValue(bm.metadata, '//datafield[@tag=\"260\"]/subfield[@code=\"c\"]') ELSE bi.publicationyear END) AS TahunTerbit"),
                    DB::raw('SUM(CASE WHEN i.itemlost = 0 AND i.withdrawn = 0 THEN 1 ELSE 0 END) AS Eksemplar'),
                    DB::raw('GROUP_CONCAT(DISTINCT i.homebranch ORDER BY i.homebranch SEPARATOR ", ") AS Lokasi')
                )
                    ->from('items as i')
                    ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                    ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                    ->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'i.biblionumber')
                    ->join('itemtypes as t', 'i.itype', '=', 't.itemtype')
                    ->where('i.itemlost', 0)
                    ->where('i.withdrawn', 0)
                    ->where(function($q) use ($cnClasses) { QueryHelper::applyCnClassRules($q, $cnClasses); })
                    ->where('t.description', $jenis);

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('CAST(ExtractValue(bm.metadata, \'//datafield[@tag="260"]/subfield[@code="c"]\') AS UNSIGNED) >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
                }

                return $query->groupBy('b.title')->orderBy('Kelas', 'asc')->paginate($perPage, ['*'], 'page', $page);
            });
        }

        return response()->json($detailData);
    }

    public function eresource(Request $request)
    {
        return view('pages.dapus.eresource');
    }

    public function searchScopus(Request $request)
    {
        $query = $request->input('query', '');
        if (empty($query)) return response()->json(['error' => 'Query tidak boleh kosong'], 400);

        $apiKey = config('services.scopus.api_key') ?: env('SCOPUS_API_KEY', '084a902b2b13bcebed5e401e22585d7e');
        $baseUrl = config('services.scopus.url') ?: env('SCOPUS_URL', 'https://api.elsevier.com/content/search/scopus');
        
        if (empty($apiKey)) return response()->json(['error' => 'API Key Scopus belum dikonfigurasi'], 500);

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-ELS-APIKey' => $apiKey,
                'Accept' => 'application/json'
            ])->get($baseUrl, [
                'query' => $query,
                'count' => $request->input('count', 10),
                'start' => $request->input('start', 0),
            ]);

            if ($response->successful()) return response()->json($response->json());
            return response()->json(['error' => 'Gagal mengambil data dari Scopus', 'details' => $response->json()], $response->status());

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Scopus search error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Terjadi kesalahan internal: ' . $e->getMessage()], 500);
        }
    }

    private function streamCsvExport($data, $namaProdi, $tahunTerakhir, $filePrefix, $titleText, $headers, $rowMapper)
    {
        $filename = "koleksi_" . $filePrefix;
        if ($namaProdi && $namaProdi !== 'Pilih Program Studi' && $namaProdi !== 'Semua Program Studi') {
            $cleanProdiName = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $namaProdi));
            $filename .= "_" . $cleanProdiName;
        }
        $filename .= "_" . ($tahunTerakhir !== 'all' ? $tahunTerakhir . "_tahun_terakhir" : "semua_tahun");
        $filename .= "_" . Carbon::now()->format('Ymd_His') . ".csv";

        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir, $titleText, $rowMapper) {
            if (ob_get_level()) ob_end_clean();
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            $judulProdi = 'Daftar Koleksi ' . $titleText . ' - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Filter: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun';
            
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';');
            fputcsv($file, $headers, ';');

            $i = 1;
            $previousJudul = null; 

            foreach ($data as $row) {
                $rowData = $rowMapper($row, $i, $previousJudul);
                if (!empty($rowData)) fputcsv($file, $rowData, ';');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
