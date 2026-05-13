<?php

namespace App\Http\Controllers;

use App\Models\M_eprodi;
use Illuminate\Support\Facades\Log;
use App\Models\M_items;
use Illuminate\Http\Request;
use App\Helpers\CnClassHelper;
use App\Helpers\CnClassHelperr;
use App\Helpers\QueryHelper;
use App\Models\M_Auv;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatistikKoleksi extends Controller
{
    public function __construct()
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 600);
    }


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

    private function getProdiToFacultyMap($listprodi)
    {
        $map = [];
        foreach ($listprodi as $prodi) {
            $prodiCode = $prodi->authorised_value;
            $firstLetter = substr($prodiCode, 0, 1);
            $firstTwoLetters = substr($prodiCode, 0, 2);
            $firstThreeLetters = substr($prodiCode, 0, 3);

            if (in_array($firstThreeLetters, ['J53', 'J52'])) {
                $map[$prodiCode] = 'FKG - Fakultas Kedokteran Gigi';
            } else if ($firstTwoLetters === 'J5') {
                $map[$prodiCode] = 'FK - Fakultas Kedokteran';
            } else if ($firstLetter === 'J') {
                $map[$prodiCode] = 'FIK - Fakultas Ilmu Kesehatan';
            } else if (isset($this->facultyMapping[$firstLetter])) {
                $map[$prodiCode] = $this->facultyMapping[$firstLetter];
            } else {
                $map[$prodiCode] = 'Unknown';
            }

            if (in_array($prodiCode, ['A510', 'A610', 'KIP/PSKGJ PAUD', 'Q100', 'S400', 'Q200', 'Q300', 'S200'])) {
                $map[$prodiCode] = 'FKIP - Fakultas Keguruan dan Ilmu Pendidikan';
            }

            if (in_array($prodiCode, ['W100', 'P100'])) {
                $map[$prodiCode] = 'FEB - Fakultas Ekonomi dan Bisnis';
            }
            if (in_array($prodiCode, ['U200', 'U100', 'S100'])) {
                $map[$prodiCode] = 'FT - Fakultas Teknik';
            }
            if (in_array($prodiCode, ['S300', 'T100'])) {
                $map[$prodiCode] = 'FPsi - Fakultas Psikologi';
            }
            if (in_array($prodiCode, ['I000', 'O100', 'O300', 'O200', 'O000'])) {
                $map[$prodiCode] = 'FAI - Fakultas Agama Islam';
            }
            if (in_array($prodiCode, ['R100', 'R200'])) {
                $map[$prodiCode] = 'FHIP - Fakultas Hukum dan Ilmu Politik';
            }
            if (in_array($prodiCode, ['V100'])) {
                $map[$prodiCode] = 'FF - Fakultas Farmasi';
            }
        }
        return $map;
    }


    public function rekapPerFakultas(Request $request)
    {
        $request->validate([
            'fakultas' => 'string|nullable',
            'tahun' => 'numeric|nullable'
        ]);

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        // OPTIMASI: Gunakan cached prodi list (1 jam cache)
        $listprodi = M_Auv::getCachedProdiList();

        $prodiToFacultyMap = $this->getProdiToFacultyMap($listprodi);
        $prodiCodeToNameMap = $listprodi->pluck('lib', 'authorised_value');
        $faculties = array_unique(array_values($prodiToFacultyMap));
        sort($faculties);
        $selectedFaculty = $request->input('fakultas');

        $tahunTerakhir = $request->input('tahun', 'all');
        $rekapData = collect();
        $rekapDataPerProdi = [];

        if ($selectedFaculty) {
            $cacheKey = "rekap_fakultas:" . ($selectedFaculty ?: 'all') . ":" . $tahunTerakhir;
            
            $rekapData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodiToFacultyMap, $selectedFaculty, $prodiCodeToNameMap, $tahunTerakhir) {
                
                $targetProdiCodes = array_keys($prodiToFacultyMap, $selectedFaculty);
                
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

                $processCategory = function($category, $itypes, $isSerial = false, $isCcodeR = false, $isBarcodeJE = false, $isNotBarcodeJE = false) use (&$countsPerProdi, $targetProdiCodes, $tahunTerakhir) {
                    $q = M_items::query()
                        ->from('items')
                        ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                        ->join('biblio as b', 'items.biblionumber', '=', 'b.biblionumber')
                        ->select(
                            'items.biblionumber',
                            'items.enumchron',
                            'bi.cn_class',
                            'items.itemcallnumber'
                        )
                        ->where('items.itemlost', 0)
                        ->where('items.withdrawn', 0);

                     if (is_array($itypes)) {
                         $q->whereIn('items.itype', $itypes);
                     } else {
                         $q->where('items.itype', $itypes);
                     }

                     if ($isCcodeR) {
                         $q->whereRaw('LEFT(items.ccode, 1) = ?', ['R']);
                     } else {
                          if ($itypes == ['BKS', 'BKSA', 'BKSCA', 'BKSC']) {
                             $q->whereRaw('LEFT(items.ccode, 1) <> "R"');
                          }
                     }

                     if (in_array('JR', (array)$itypes)) {
                         $q->whereRaw("TRIM(items.enumchron) REGEXP '[0-9]{4}$'");
                     }

                     if ($isBarcodeJE) {
                         $q->where('items.barcode', 'like', 'JE%');
                     }
                     if ($isNotBarcodeJE) {
                         $q->where('items.barcode', 'not like', 'JE%');
                     }
                     if ($tahunTerakhir !== 'all') {
                         if (in_array('JR', (array)$itypes)) {
                             $q->whereRaw('RIGHT(items.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
                         } else {
                             $q->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                         }
                     }

                     // Eksekusi pakai cursor untuk hemat RAM
                     foreach ($q->cursor() as $row) {
                         $actualClass = !empty($row->cn_class) ? $row->cn_class : $row->itemcallnumber;
                         if (empty($actualClass)) continue;

                         $key = $isSerial ? $row->biblionumber . '_' . $row->enumchron : $row->biblionumber;

                         // Cek setiap prodi
                         foreach ($targetProdiCodes as $prodiCode) {
                             if (CnClassHelperr::isValidCnClass($prodiCode, $actualClass)) {
                                 $countsPerProdi[$prodiCode][$category]['judul'][$key] = true;
                                 $countsPerProdi[$prodiCode][$category]['eksemplar']++;
                             }
                         }
                     }
                };

                // Proses semua kategori
                $processCategory('Jurnal', ['JR', 'JRA', 'JRT'], true, false, false, true);
                $processCategory('E-Jurnal', ['JR', 'JRA', 'JRT'], true, false, true, false);
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
                        $order = [
                            'Textbook' => 1, 'E-Book' => 2, 'Jurnal' => 3,
                            'E-Jurnal' => 4, 'Prosiding' => 5, 'Referensi' => 6,
                        ];
                        uksort($finalCounts, function ($a, $b) use ($order) {
                            return ($order[$a] ?? 99) <=> ($order[$b] ?? 99);
                        });

                        $rekapDataPerProdi[] = [
                            'prodi_code' => $prodiCode,
                            'nama_prodi' => $namaProdi,
                            'counts'     => $finalCounts
                        ];
                    }
                }
                return collect($rekapDataPerProdi)->sortBy('nama_prodi')->values();
            }); // Cache Remember End
        }

        return view('pages.dapus.rekap_fakultas', compact('faculties', 'selectedFaculty', 'rekapData'));
    }

    public function prosiding(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->get();

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

            $cacheKey = "stats_prosiding:{$prodi}:{$tahunTerakhir}";
            $cachedResult = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodi, $tahunTerakhir) {
                // 1. Total Query
                $totalQuery = $this->getBaseCollectionTotalQuery($prodi, $tahunTerakhir);
                $totalQuery->whereIn('items.itype', ['EPR', 'PR']);
                if ($tahunTerakhir !== 'all') {
                    $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                }
                $totals = $totalQuery->selectRaw("
                    COUNT(DISTINCT items.biblionumber) as total_judul,
                    COUNT(items.itemnumber) as total_eksemplar
                ")->first();

                // 2. Main Query
                $query = $this->getBaseCollectionQuery($prodi, $tahunTerakhir);
                $query->whereIn('items.itype', ['EPR', 'PR']);

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                }

                $query->selectRaw("
                MAX(bi.cn_class) as Kelas,
                MAX(b.title) as Judul_a,
                MAX(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) as Judul_b,
                MAX(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"c\"]')) as Judul_c,
                MAX(b.author) as Pengarang,
                MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
                MAX(bi.publicationyear) AS TahunTerbit,
                items.enumchron AS Nomor,
                COUNT(DISTINCT items.itemnumber) AS Issue,
                COUNT(items.itemnumber) AS Eksemplar,
                MAX(IF(
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]') <> '',
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]'),
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]')
                )) AS Link_Prosiding,
                GROUP_CONCAT(DISTINCT CASE
                WHEN items.homebranch = 'PUSAT' THEN 'Perpustakaan Pusat'
                WHEN items.homebranch = 'GIZI' THEN 'Perpustakaan Gizi'
                WHEN items.homebranch = 'FKG' THEN 'Perpustakaan Kedokteran Gigi'
                WHEN items.homebranch = 'PSIKO' THEN 'Perpustakaan Psikologi'
                WHEN items.homebranch = 'INF' THEN 'Perpustakaan Informatika'
                WHEN items.homebranch = 'FIK' THEN 'Perpustakaan FIK'
                WHEN items.homebranch = 'MATH' THEN 'Perpustakaan Matematika FKIP'
                WHEN items.homebranch = 'LIPK' THEN 'LIPK'
                WHEN items.homebranch = 'TILIB' THEN 'Perpustakaan Teknik Industri'
                WHEN items.homebranch = 'MAPRO' THEN 'Perpustakaan Magister Psikologi'
                WHEN items.homebranch = 'MEDLIB' THEN 'Perpustakaan Kedokteran'
                WHEN items.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                WHEN items.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                WHEN items.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                WHEN items.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                WHEN items.homebranch = 'PASCA' THEN 'Perpustakaan Pasca Sarjana'
                WHEN items.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                WHEN items.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
                WHEN items.homebranch = 'FG' THEN 'Perpustakaan Fakultas Geografi'
                ELSE items.homebranch
                END SEPARATOR ', ') AS Lokasi"
                );

                $query->groupBy('items.biblionumber', 'items.enumchron');
                $query->orderBy('TahunTerbit', 'desc');

                $processedData = $query->get()->map(function ($row) {
                    $fullJudul = $row->Judul_a;
                    if (!empty($row->Judul_b)) {
                        $fullJudul .= ' : ' . $row->Judul_b;
                    }
                    if (!empty($row->Judul_c)) {
                        $fullJudul .= ' / ' . $row->Judul_c;
                    }
                    $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES, 'UTF-8');
                    $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    return $row;
                });

                $totals = $totalQuery->first();

                return [
                    'processedData' => $processedData,
                    'totalJudul' => $processedData->count(),
                    'totalEksemplar' => $totals->total_eksemplar ?? 0
                ];
            });

            $processedData = $cachedResult['processedData'];
            $totalJudul = $cachedResult['totalJudul'];
            $totalEksemplar = $cachedResult['totalEksemplar'];

            if ($request->has('export_csv')) {
                $headers = ['No', 'Judul', 'Pengarang', 'Penerbit', 'Tahun Terbit', 'Nomor', 'Issue', 'Eksemplar', 'Lokasi', 'Link'];
                return $this->streamCsvExport($processedData, $namaProdi, $tahunTerakhir, 'prosiding', 'Prosiding', $headers, function($row, &$i) {
                    return [
                        $i++,
                        $row->Judul,
                        $row->Pengarang,
                        $row->Penerbit,
                        (int) $row->TahunTerbit,
                        $row->Nomor,
                        (int) $row->Issue,
                        (int) $row->Eksemplar,
                        $row->Lokasi,
                        $row->Link_Prosiding ?? ''
                    ];
                });
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.prosiding', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    /**
     * Tampilkan data koleksi jurnal dan tangani ekspor CSV.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function jurnal(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->get();
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

            $cacheKey = "stats_jurnal:{$prodi}:{$tahunTerakhir}";
            $cachedResult = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodi, $tahunTerakhir) {
                // 1. Total Query
                $totalQuery = $this->getBaseCollectionTotalQuery($prodi, $tahunTerakhir);
                $totalQuery->whereIn('items.itype', ['JR', 'JRA', 'JRT'])
                    ->where('items.barcode', 'not like', 'JE%')
                    ->whereRaw("TRIM(items.enumchron) REGEXP '[0-9]{4}$'");
                if ($tahunTerakhir !== 'all') {
                    $totalQuery->whereRaw('RIGHT(items.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
                }
                $totals = $totalQuery->selectRaw("
                    COUNT(DISTINCT items.biblionumber) as total_judul,
                    COUNT(items.itemnumber) as total_eksemplar
                ")->first();

                // 2. Main Query
                $query = $this->getBaseCollectionQuery($prodi, $tahunTerakhir);
                
                $query->leftJoin('itemtypes as it', 'it.itemtype', '=', 'items.itype')
                    ->leftJoin('authorised_values as av', function ($join) {
                        $join->on('av.authorised_value', '=', 'items.ccode')
                            ->where('av.category', '=', 'CCODE');
                    })
                    ->whereIn('items.itype', ['JR', 'JRA', 'JRT'])
                    ->where('items.barcode', 'not like', 'JE%')
                    ->whereRaw("TRIM(items.enumchron) REGEXP '[0-9]{4}$'");

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('RIGHT(items.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
                }

                $query->select(
                    'bi.cn_class AS Kelas',
                    DB::raw("GROUP_CONCAT(items.barcode ORDER BY items.barcode ASC SEPARATOR ', ') as Barcode"),
                    DB::raw("MAX(CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]'))) AS Judul"),
                    DB::raw('MAX(bi.publishercode) AS Penerbit'),
                    'items.enumchron AS Nomor',
                    DB::raw('MAX(av.lib) AS Jenis_Koleksi'),
                    DB::raw('MAX(it.description) AS Jenis_Item_Tipe'),
                    DB::raw('COUNT(DISTINCT items.enumchron) AS Issue'),
                    DB::raw('COUNT(*) AS Eksemplar'),
                    DB::raw("GROUP_CONCAT(DISTINCT CASE
                        WHEN items.homebranch = 'PUSAT' THEN 'Perpustakaan Pusat'
                        WHEN items.homebranch = 'GIZI' THEN 'Perpustakaan Gizi'
                        WHEN items.homebranch = 'FKG' THEN 'Perpustakaan Kedokteran Gigi'
                        WHEN items.homebranch = 'PSIKO' THEN 'Perpustakaan Psikologi'
                        WHEN items.homebranch = 'INF' THEN 'Perpustakaan Informatika'
                        WHEN items.homebranch = 'FIK' THEN 'Perpustakaan FIK'
                        WHEN items.homebranch = 'MATH' THEN 'Perpustakaan Matematika FKIP'
                        WHEN items.homebranch = 'LIPK' THEN 'LIPK'
                        WHEN items.homebranch = 'TILIB' THEN 'Perpustakaan Teknik Industri'
                        WHEN items.homebranch = 'MAPRO' THEN 'Perpustakaan Magister Psikologi'
                        WHEN items.homebranch = 'MEDLIB' THEN 'Perpustakaan Kedokteran'
                        WHEN items.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                        WHEN items.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                        WHEN items.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                        WHEN items.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                        WHEN items.homebranch = 'PASCA' THEN 'Perpustakaan Pasca Sarjana'
                        WHEN items.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                        WHEN items.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
                        WHEN items.homebranch = 'FG' THEN 'Perpustakaan Fakultas Geografi'
                        ELSE items.homebranch
                    END SEPARATOR ', ') AS Lokasi"),
                    DB::raw("MAX(COALESCE(NULLIF(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]'), ''), NULLIF(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]'), ''))) as Link_Jurnal")
                );

                $query->groupBy('items.biblionumber', 'items.enumchron');
                $query->orderBy('Judul', 'asc');

                $processedData = $query->get();

                return [
                    'processedData' => $processedData,
                    'totalJudul' => $processedData->count(),
                    'totalEksemplar' => $totals->total_eksemplar ?? 0
                ];
            });

            $processedData = $cachedResult['processedData'];
            $totalJudul = $cachedResult['totalJudul'];
            $totalEksemplar = $cachedResult['totalEksemplar'];

            if ($request->has('export_csv')) {
                $headers = ['No', 'Judul', 'Penerbit', 'Nomor Edisi', 'Eksemplar', 'Jenis Koleksi', 'Jenis Item Tipe', 'Lokasi', 'Link'];
                return $this->streamCsvExport($processedData, $namaProdi, $tahunTerakhir, 'jurnal_detail', 'Jurnal Detail', $headers, function($row, &$i, &$previousJudul) {
                    if ($row->Judul !== $previousJudul) {
                        $rowData = [
                            $i++,
                            $row->Judul,
                            $row->Penerbit,
                            $row->Nomor,
                            $row->Eksemplar,
                            $row->Jenis_Koleksi,
                            $row->Jenis_Item_Tipe,
                            $row->Lokasi,
                            $row->Link_Jurnal ?? '',
                        ];
                        $previousJudul = $row->Judul;
                    } else {
                        $rowData = [
                            $i++,
                            '',
                            '',
                            $row->Nomor,
                            $row->Eksemplar,
                            $row->Jenis_Koleksi,
                            $row->Jenis_Item_Tipe,
                            $row->Lokasi,
                            $row->Link_Jurnal ?? '',
                        ];
                    }
                    return $rowData;
                });
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.jurnal', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    public function ejurnal(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->get();
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

            $cacheKey = "stats_ejurnal:{$prodi}:{$tahunTerakhir}";
            $cachedResult = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodi, $tahunTerakhir) {
                // 1. Total Query
                $totalQuery = $this->getBaseCollectionTotalQuery($prodi, $tahunTerakhir);
                $totalQuery->whereIn('items.itype', ['JR', 'JRA', 'JRT'])
                    ->where('items.barcode', 'like', 'JE%')
                    ->whereRaw("TRIM(items.enumchron) REGEXP '[0-9]{4}$'");
                if ($tahunTerakhir !== 'all') {
                    $totalQuery->whereRaw('RIGHT(items.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
                }
                $totals = $totalQuery->selectRaw("
                    COUNT(DISTINCT items.biblionumber) as total_judul,
                    COUNT(items.itemnumber) as total_eksemplar
                ")->first();

                // 2. Main Query
                $query = $this->getBaseCollectionQuery($prodi, $tahunTerakhir);
                
                $query->leftJoin('itemtypes as it', 'it.itemtype', '=', 'items.itype')
                    ->leftJoin('authorised_values as av', function ($join) {
                        $join->on('av.authorised_value', '=', 'items.ccode')
                            ->where('av.category', '=', 'CCODE');
                    })
                    ->whereIn('items.itype', ['JR', 'JRA', 'JRT'])
                    ->where('items.barcode', 'like', 'JE%') // Filter E-Jurnal (JE)
                    ->whereRaw("TRIM(items.enumchron) REGEXP '[0-9]{4}$'");

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('RIGHT(items.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
                }

                $query->select(
                    'bi.cn_class AS Kelas',
                    DB::raw("GROUP_CONCAT(items.barcode ORDER BY items.barcode ASC SEPARATOR ', ') as Barcode"),
                    DB::raw("MAX(CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]'))) AS Judul"),
                    DB::raw('MAX(bi.publishercode) AS Penerbit'),
                    'items.enumchron AS Nomor',
                    DB::raw('MAX(av.lib) AS Jenis_Koleksi'),
                    DB::raw('MAX(it.description) AS Jenis_Item_Tipe'),
                    DB::raw('COUNT(DISTINCT items.enumchron) AS Issue'),
                    DB::raw('COUNT(*) AS Eksemplar'),
                    DB::raw("GROUP_CONCAT(DISTINCT CASE
                        WHEN items.homebranch = 'PUSAT' THEN 'Perpustakaan Pusat'
                        WHEN items.homebranch = 'GIZI' THEN 'Perpustakaan Gizi'
                        WHEN items.homebranch = 'FKG' THEN 'Perpustakaan Kedokteran Gigi'
                        WHEN items.homebranch = 'PSIKO' THEN 'Perpustakaan Psikologi'
                        WHEN items.homebranch = 'INF' THEN 'Perpustakaan Informatika'
                        WHEN items.homebranch = 'FIK' THEN 'Perpustakaan FIK'
                        WHEN items.homebranch = 'MATH' THEN 'Perpustakaan Matematika FKIP'
                        WHEN items.homebranch = 'LIPK' THEN 'LIPK'
                        WHEN items.homebranch = 'TILIB' THEN 'Perpustakaan Teknik Industri'
                        WHEN items.homebranch = 'MAPRO' THEN 'Perpustakaan Magister Psikologi'
                        WHEN items.homebranch = 'MEDLIB' THEN 'Perpustakaan Kedokteran'
                        WHEN items.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                        WHEN items.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                        WHEN items.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                        WHEN items.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                        WHEN items.homebranch = 'PASCA' THEN 'Perpustakaan Pasca Sarjana'
                        WHEN items.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                        WHEN items.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
                        WHEN items.homebranch = 'FG' THEN 'Perpustakaan Fakultas Geografi'
                        ELSE items.homebranch
                    END SEPARATOR ', ') AS Lokasi"),
                    DB::raw("MAX(COALESCE(NULLIF(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]'), ''), NULLIF(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]'), ''))) as Link_Ejurnal")
                );

                $query->groupBy('items.biblionumber', 'items.enumchron');
                $query->orderBy('Judul', 'asc');

                $processedData = $query->get();

                return [
                    'processedData' => $processedData,
                    'totalJudul' => $processedData->count(),
                    'totalEksemplar' => $totals->total_eksemplar ?? 0
                ];
            });

            $processedData = $cachedResult['processedData'];
            $totalJudul = $cachedResult['totalJudul'];
            $totalEksemplar = $cachedResult['totalEksemplar'];

            if ($request->has('export_csv')) {
                $headers = ['No', 'Judul', 'Penerbit', 'Nomor Edisi', 'Eksemplar', 'Jenis Koleksi', 'Jenis Item Tipe', 'Lokasi', 'Link'];
                return $this->streamCsvExport($processedData, $namaProdi, $tahunTerakhir, 'e_jurnal_detail', 'E-Jurnal Detail', $headers, function($row, &$i, &$previousJudul) {
                    if ($row->Judul !== $previousJudul) {
                        $rowData = [
                            $i++,
                            $row->Judul,
                            $row->Penerbit,
                            $row->Nomor,
                            $row->Eksemplar,
                            $row->Jenis_Koleksi,
                            $row->Jenis_Item_Tipe,
                            $row->Lokasi,
                            $row->Link_Ejurnal ?? '',
                        ];
                        $previousJudul = $row->Judul;
                    } else {
                        $rowData = [
                            $i++,
                            '',
                            '',
                            $row->Nomor,
                            $row->Eksemplar,
                            $row->Jenis_Koleksi,
                            $row->Jenis_Item_Tipe,
                            $row->Lokasi,
                            $row->Link_Ejurnal ?? '',
                        ];
                    }
                    return $rowData;
                });
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.ejurnal', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    public function ebook(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->get();

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

            $cacheKey = "stats_ebook:{$prodi}:{$tahunTerakhir}";
            $cachedResult = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodi, $tahunTerakhir) {
                // 1. Total Query
                $totalQuery = $this->getBaseCollectionTotalQuery($prodi, $tahunTerakhir);
                $totalQuery->where('items.itype', 'EB');
                if ($tahunTerakhir !== 'all') {
                    $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                }
                $totals = $totalQuery->selectRaw("
                    COUNT(DISTINCT items.biblionumber) as total_judul,
                    COUNT(items.itemnumber) as total_eksemplar
                ")->first();

                // 2. Main Query
                $query = $this->getBaseCollectionQuery($prodi, $tahunTerakhir);
                $query->where('items.itype', 'EB');

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                }

                $query->selectRaw("
                    MAX(b.title) as Judul_a,
                    MAX(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) as Judul_b,
                    MAX(b.author) as Pengarang,
                    MAX(bi.place) AS Kota_Terbit,
                    MAX(bi.publishercode) AS Penerbit_Raw,
                    MAX(bi.place) AS Place_Raw,
                    MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
                    MAX(bi.publicationyear) AS Tahun_Terbit,
                    COUNT(items.itemnumber) AS Eksemplar,
                    MAX(IF(
                    EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]') <> '',
                    EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]'),
                    EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]')
                )) AS Link_Ebook,
                    MAX(items.biblionumber) as biblionumber
                ");

                $query->groupBy('items.biblionumber');
                $query->orderBy('Tahun_Terbit', 'desc')->orderBy('Judul_a', 'asc');

                $processedData = $query->get()->map(function ($row) {
                    $fullJudul = $row->Judul_a;
                    if (!empty($row->Judul_b)) {
                        $fullJudul .= ' ' . $row->Judul_b;
                    }
                    $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $row->Kota_Terbit = html_entity_decode($row->Kota_Terbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    return $row;
                });

                $totals = $totalQuery->first();

                return [
                    'processedData' => $processedData,
                    'totalJudul' => $processedData->count(),
                    'totalEksemplar' => $totals->total_eksemplar ?? 0
                ];
            });

            $processedData = $cachedResult['processedData'];
            $totalJudul = $cachedResult['totalJudul'];
            $totalEksemplar = $cachedResult['totalEksemplar'];

            if ($request->has('export_csv')) {
                $headers = ['No', 'Judul', 'Pengarang', 'Kota Terbit', 'Penerbit', 'Tahun Terbit', 'Eksemplar', 'Link'];
                return $this->streamCsvExport($processedData, $namaProdi, $tahunTerakhir, 'ebook', 'E-Book', $headers, function($row, &$i) {
                    return [
                        $i++,
                        $row->Judul,
                        $row->Pengarang,
                        $row->Kota_Terbit,
                        $row->Penerbit,
                        (int) $row->Tahun_Terbit,
                        (int) $row->Eksemplar,
                        $row->Link_Ebook
                    ];
                });
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.ebook', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    /**
     * Tampilkan data koleksi textbook dan tangani ekspor CSV.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
     */

    public function textbook(Request $request)
    {

        // OPTIMASI: Gunakan cached prodi list, sort di PHP
        $listprodi = M_Auv::getCachedProdiList()->sortBy('authorised_value')->values();

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

            $cacheKey = "stats_textbook:{$prodi}:{$tahunTerakhir}";
            $cachedResult = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodi, $tahunTerakhir) {
                // 1. Hitung total secara efisien
                $totalQuery = $this->getBaseCollectionTotalQuery($prodi, $tahunTerakhir);
                $totalQuery->whereIn('items.itype', ['BKS', 'BKSA', 'BKSCA', 'BKSC'])
                           ->whereRaw('LEFT(items.ccode, 1) <> "R"');

                if ($tahunTerakhir !== 'all') {
                    $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                }

                $totals = $totalQuery->selectRaw("
                    COUNT(DISTINCT items.biblionumber) as total_judul,
                    COUNT(items.itemnumber) as total_eksemplar
                ")->first();

                // 2. Ambil data detail dengan optimasi GROUP BY
                $query = $this->getBaseCollectionQuery($prodi, $tahunTerakhir);
                $query->whereIn('items.itype', ['BKS', 'BKSA', 'BKSCA', 'BKSC']);

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                }

                $query->selectRaw("
                    MAX(b.title) as Judul_a,
                    MAX(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) as Judul_b,
                    MAX(b.author) as Pengarang,
                    MAX(bi.place) AS Kota_Terbit,
                    MAX(bi.publishercode) AS Penerbit,
                    MAX(bi.publicationyear) AS Tahun_Terbit,
                    COUNT(items.itemnumber) AS Eksemplar,
                    GROUP_CONCAT(DISTINCT CASE
                    WHEN items.homebranch = 'PUSAT' THEN 'Perpustakaan Pusat'
                    WHEN items.homebranch = 'GIZI' THEN 'Perpustakaan Gizi'
                    WHEN items.homebranch = 'FKG' THEN 'Perpustakaan Kedokteran Gigi'
                    WHEN items.homebranch = 'PSIKO' THEN 'Perpustakaan Psikologi'
                    WHEN items.homebranch = 'INF' THEN 'Perpustakaan Informatika'
                    WHEN items.homebranch = 'FIK' THEN 'Perpustakaan FIK'
                    WHEN items.homebranch = 'MATH' THEN 'Perpustakaan Matematika FKIP'
                    WHEN items.homebranch = 'LIPK' THEN 'LIPK'
                    WHEN items.homebranch = 'TILIB' THEN 'Perpustakaan Teknik Industri'
                    WHEN items.homebranch = 'MAPRO' THEN 'Perpustakaan Magister Psikologi'
                    WHEN items.homebranch = 'MEDLIB' THEN 'Perpustakaan Kedokteran'
                    WHEN items.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                    WHEN items.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                    WHEN items.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                    WHEN items.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                    WHEN items.homebranch = 'PASCA' THEN 'Perpustakaan Postgraduate'
                    WHEN items.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                    WHEN items.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
                    WHEN items.homebranch = 'FG' THEN 'Perpustakaan Fakultas Geografi'
                    ELSE items.homebranch
                    END SEPARATOR ', ') AS Lokasi
                ");

                $query->groupBy('items.biblionumber');
                $query->orderBy('Tahun_Terbit', 'desc');

                $processedData = $query->get()->map(function ($row) {
                    $fullJudul = $row->Judul_a;
                    if (!empty($row->Judul_b)) {
                        $fullJudul .= ' ' . $row->Judul_b;
                    }

                    $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $row->Kota_Terbit = html_entity_decode($row->Kota_Terbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    return $row;
                });

                return [
                    'processedData' => $processedData,
                    'totalJudul' => $processedData->count(),
                    'totalEksemplar' => $totals->total_eksemplar ?? 0
                ];
            });

            $processedData = $cachedResult['processedData'];
            $totalJudul = $cachedResult['totalJudul'];
            $totalEksemplar = $cachedResult['totalEksemplar'];

            if ($request->has('export_csv')) {
                $headers = ['No', 'Judul', 'Pengarang', 'Kota Terbit', 'Penerbit', 'Tahun Terbit', 'Eksemplar', 'Lokasi'];
                return $this->streamCsvExport($processedData, $namaProdi, $tahunTerakhir, 'textbook', 'Buku Teks', $headers, function($row, &$i) {
                    return [
                        $i++,
                        $row->Judul,
                        $row->Pengarang,
                        $row->Kota_Terbit,
                        $row->Penerbit,
                        (int) $row->Tahun_Terbit,
                        (int) $row->Eksemplar,
                        $row->Lokasi
                    ];
                });
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.textbook', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }



    public function periodikal(Request $request)
    {
        // OPTIMASI: Gunakan cached prodi list, sort di PHP
        $listprodi = M_Auv::getCachedProdiList()->sortBy('authorised_value')->values();

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

            $cacheKey = "stats_periodikal:{$prodi}:{$tahunTerakhir}";
            $cachedResult = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodi, $tahunTerakhir) {
                $periodicalTypes = ['MJA', 'MJI', 'MJIP', 'MJP'];
                
                // 1. Total Query
                $totalQuery = $this->getBaseCollectionTotalQuery($prodi, $tahunTerakhir);
                $totalQuery->whereIn('items.itype', $periodicalTypes);

                if ($tahunTerakhir !== 'all') {
                    $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                }

                $totals = $totalQuery->selectRaw("
                    COUNT(DISTINCT items.biblionumber) as total_judul,
                    COUNT(items.itemnumber) as total_eksemplar
                ")->first();

                // 2. Main Query
                $query = $this->getBaseCollectionQuery($prodi, $tahunTerakhir);
                $query->whereIn('items.itype', $periodicalTypes);

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                }

                $query->select(
                    'items.itype AS Jenis_kode',
                    't.description AS Jenis',
                    'bi.publishercode AS Penerbit',
                    'bi.place AS Tempat_Terbit',
                    'bi.publicationyear AS Tahun_Terbit',
                    'bi.cn_class as Kelas',
                    'items.enumchron AS Nomor'
                )
                    ->selectRaw("GROUP_CONCAT(DISTINCT items.homebranch SEPARATOR ', ') as Lokasi")
                    ->selectRaw("MAX(b.title) as Judul_a")
                    ->selectRaw("MAX(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) as Judul_b")
                    ->selectRaw('COUNT(items.itemnumber) AS Issue')
                    ->selectRaw('SUM(items.copynumber) AS Eksemplar')
                    ->join('itemtypes as t', 'items.itype', '=', 't.itemtype')
                    ->groupBy('items.biblionumber', 'items.itype', 't.description', 'bi.publishercode', 'bi.place', 'bi.publicationyear', 'bi.cn_class', 'items.enumchron');

                $processedData = $query->get()->map(function ($row) {
                    $fullJudul = $row->Judul_a;
                    if (!empty($row->Judul_b)) {
                        $fullJudul .= ' ' . $row->Judul_b;
                    }
                    $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    $penerbit = $row->Penerbit;
                    if (!empty($row->Tempat_Terbit)) {
                        $penerbit .= ' : ' . $row->Tempat_Terbit;
                    }
                    $row->Penerbit_Lengkap = html_entity_decode($penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    if (empty($row->Tahun_Terbit) || $row->Tahun_Terbit == '0000') {
                        $row->Tahun_Terbit = 'n.d.';
                    }

                    return $row;
                });

                $totals = $totalQuery->first();

                return [
                    'processedData' => $processedData,
                    'totalJudul' => $processedData->count(),
                    'totalEksemplar' => $totals->total_eksemplar ?? 0
                ];
            });

            $processedData = $cachedResult['processedData'];
            $totalJudul = $cachedResult['totalJudul'];
            $totalEksemplar = $cachedResult['totalEksemplar'];

            if ($request->has('export_csv')) {
                $headers = ['No', 'Kelas', 'Jenis', 'Judul', 'Penerbit', 'Tahun Terbit', 'Nomor', 'Issue', 'Eksemplar', 'Lokasi'];
                return $this->streamCsvExport($processedData, $namaProdi, $tahunTerakhir, 'periodikal', 'Periodikal', $headers, function($row, &$i) {
                    return [
                        $i++,
                        $row->Kelas,
                        $row->Jenis,
                        $row->Judul,
                        $row->Penerbit_Lengkap,
                        $row->Tahun_Terbit,
                        $row->Nomor,
                        (int) $row->Issue,
                        (int) $row->Eksemplar,
                        $row->Lokasi,
                    ];
                });
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.periodikal', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }


    public function referensi(Request $request)
    {
        // OPTIMASI: Gunakan cached prodi list, sort di PHP
        $listprodi = M_Auv::getCachedProdiList()->sortBy('authorised_value')->values();

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

            $cacheKey = "stats_referensi:{$prodi}:{$tahunTerakhir}";
            $cachedResult = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prodi, $tahunTerakhir) {
                // 1. Hitung total secara efisien
                $totalQuery = $this->getBaseCollectionTotalQuery($prodi, $tahunTerakhir);
                $totalQuery->whereRaw('LEFT(items.itype, 3) = "BKS"')
                           ->whereRaw('LEFT(items.ccode, 1) = "R"');
                
                if ($tahunTerakhir !== 'all') {
                    $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                }
                
                $totals = $totalQuery->selectRaw("
                    COUNT(DISTINCT items.biblionumber) as total_judul,
                    COUNT(items.itemnumber) as total_eksemplar
                ")->first();

                // 2. Ambil data detail dengan optimasi GROUP BY
                $query = $this->getBaseCollectionQuery($prodi, $tahunTerakhir);
                $query->whereRaw('LEFT(items.itype, 3) = "BKS"')
                      ->whereRaw('LEFT(items.ccode, 1) = "R"');

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
                }

                $query->selectRaw("
                MAX(bi.cn_class) as Kelas,
                MAX(b.title) as Judul_a,
                MAX(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) as Judul_b,
                MAX(b.author) as Pengarang,
                MAX(bi.place) AS Kota_Terbit,
                MAX(bi.publishercode) AS Penerbit_Raw,
                MAX(bi.place) AS Place_Raw,
                MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
                MAX(bi.publicationyear) AS Tahun_Terbit,
                COUNT(items.itemnumber) AS Eksemplar,
                GROUP_CONCAT(DISTINCT CASE
                    WHEN items.homebranch = 'PUSAT' THEN 'Perpustakaan Pusat'
                    WHEN items.homebranch = 'GIZI' THEN 'Perpustakaan Gizi'
                    WHEN items.homebranch = 'FKG' THEN 'Perpustakaan Kedokteran Gigi'
                    WHEN items.homebranch = 'PSIKO' THEN 'Perpustakaan Psikologi'
                    WHEN items.homebranch = 'INF' THEN 'Perpustakaan Informatika'
                    WHEN items.homebranch = 'FIK' THEN 'Perpustakaan FIK'
                    WHEN items.homebranch = 'MATH' THEN 'Perpustakaan Matematika FKIP'
                    WHEN items.homebranch = 'LIPK' THEN 'LIPK'
                    WHEN items.homebranch = 'TILIB' THEN 'Perpustakaan Teknik Industri'
                    WHEN items.homebranch = 'MAPRO' THEN 'Perpustakaan Magister Psikologi'
                    WHEN items.homebranch = 'MEDLIB' THEN 'Perpustakaan Kedokteran'
                    WHEN items.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                    WHEN items.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                    WHEN items.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                    WHEN items.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                    WHEN items.homebranch = 'PASCA' THEN 'Perpustakaan Postgraduate'
                    WHEN items.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                    WHEN items.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
                    WHEN items.homebranch = 'FG' THEN 'Perpustakaan Fakultas Geografi'
                    ELSE items.homebranch
                    END SEPARATOR ', ') AS Lokasi
                ");

                $query->groupBy('items.biblionumber');
                $query->orderBy('Tahun_Terbit', 'desc');

                $processedData = $query->get()->map(function ($row) {
                    $fullJudul = $row->Judul_a;
                    if (!empty($row->Judul_b)) {
                        $fullJudul .= ' ' . $row->Judul_b;
                    }
                    $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $row->Kota_Terbit = html_entity_decode($row->Kota_Terbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    return $row;
                });

                return [
                    'processedData' => $processedData,
                    'totalJudul' => $processedData->count(),
                    'totalEksemplar' => $totals->total_eksemplar ?? 0
                ];
            });

            $processedData = $cachedResult['processedData'];
            $totalJudul = $cachedResult['totalJudul'];
            $totalEksemplar = $cachedResult['totalEksemplar'];

            if ($request->has('export_csv')) {
                $headers = ['No', 'Judul', 'Pengarang', 'Kota Terbit', 'Penerbit', 'Tahun Terbit', 'Eksemplar', 'Lokasi'];
                return $this->streamCsvExport($processedData, $namaProdi, $tahunTerakhir, 'referensi', 'Referensi', $headers, function($row, &$i) {
                    return [
                        $i++,
                        $row->Judul,
                        $row->Pengarang,
                        $row->Kota_Terbit,
                        $row->Penerbit,
                        (int) $row->Tahun_Terbit,
                        (int) $row->Eksemplar,
                        $row->Lokasi
                    ];
                });
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.referensi', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    /**
     * Tampilkan data koleksi per prodi.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
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
                    ->join('itemtypes as t', 'i.itype', '=', 't.itemtype')
                    ->where('i.itemlost', 0)
                    ->where('i.withdrawn', 0)
                    ->whereIn('bi.cn_class', $cnClasses);

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
                }

                $data = $query->groupBy('Jenis', 'Koleksi')
                    ->orderBy('Jenis', 'asc')
                    ->orderBy('Koleksi', 'asc')
                    ->get();
                $chartData = $data->map(function ($item) {
                    return [
                        'jenis' => $item->Jenis,
                        'judul' => $item->Judul,
                        'eksemplar' => $item->Eksemplar
                    ];
                })->values()->all();

                return [
                    'namaProdi' => $namaProdi,
                    'data' => $data,
                    'chartData' => $chartData
                ];
            });

            $namaProdi = $cachedResult['namaProdi'];
            $data = $cachedResult['data'];
            $chartData = $cachedResult['chartData'];
        }

        return view('pages.dapus.prodi', compact('namaProdi', 'listprodi', 'data', 'prodi', 'tahunTerakhir', 'chartData'));
    }

    /**
     * Tampilkan detail koleksi per prodi.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
                    DB::raw('MIN(bi.publicationyear) AS TahunTerbit'),
                    DB::raw('SUM(CASE WHEN i.itemlost = 0 AND i.withdrawn = 0 THEN 1 ELSE 0 END) AS Eksemplar'),
                    DB::raw('GROUP_CONCAT(DISTINCT i.homebranch ORDER BY i.homebranch SEPARATOR ", ") AS Lokasi')
                )
                    ->from('items as i')
                    ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                    ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                    ->join('itemtypes as t', 'i.itype', '=', 't.itemtype')
                    ->where('i.itemlost', 0)
                    ->where('i.withdrawn', 0)
                    ->whereIn('bi.cn_class', $cnClasses)
                    ->where('t.description', $jenis);

                if ($tahunTerakhir !== 'all') {
                    $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
                }

                return $query->groupBy('b.title')
                    ->orderBy('Kelas', 'asc')
                    ->paginate($perPage, ['*'], 'page', $page);
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
        if (empty($query)) {
            return response()->json(['error' => 'Query tidak boleh kosong'], 400);
        }

        $apiKey = config('services.scopus.api_key') ?: env('SCOPUS_API_KEY', '084a902b2b13bcebed5e401e22585d7e');
        $baseUrl = config('services.scopus.url') ?: env('SCOPUS_URL', 'https://api.elsevier.com/content/search/scopus');
        
        if (empty($apiKey)) {
            return response()->json(['error' => 'API Key Scopus belum dikonfigurasi'], 500);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-ELS-APIKey' => $apiKey,
                'Accept' => 'application/json'
            ])->get($baseUrl, [
                'query' => $query,
                'count' => $request->input('count', 10),
                'start' => $request->input('start', 0),
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Gagal mengambil data dari Scopus',
                'details' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Scopus search error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Terjadi kesalahan internal: ' . $e->getMessage()], 500);
        }
    }



    /**
     * Get Base Collection Query with common joins and filters.
     */
    private function getBaseCollectionQuery($prodi, $tahunTerakhir = 'all')
    {
        $query = M_items::query()
            ->from('items')
            ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
            ->join('biblio as b', 'b.biblionumber', '=', 'items.biblionumber')
            ->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'items.biblionumber')
            ->where('items.itemlost', 0)
            ->where('items.withdrawn', 0);

        if ($prodi !== 'all') {
            $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
            QueryHelper::applyCnClassRules($query, $cnClasses);
        }

        return $query;
    }

    /**
     * Get Base Collection Query WITHOUT metadata for fast counting.
     */
    private function getBaseCollectionTotalQuery($prodi, $tahunTerakhir = 'all')
    {
        $query = M_items::query()
            ->from('items')
            ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
            ->join('biblio as b', 'b.biblionumber', '=', 'items.biblionumber')
            ->where('items.itemlost', 0)
            ->where('items.withdrawn', 0);

        if ($prodi !== 'all') {
            $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
            QueryHelper::applyCnClassRules($query, $cnClasses);
        }

        return $query;
    }

    /**
     * Stream CSV Export helper to prevent code duplication.
     */
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
            if (ob_get_level()) {
                ob_end_clean();
            }
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
                if (!empty($rowData)) {
                    fputcsv($file, $rowData, ';');
                }
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
